<?php
namespace App\Services;

final class MarkdownRenderer
{
    public function render(string $markdown): string
    {
        $html = $markdown;
        $codeBlocks=[];
        $html = $this->renderCodeBlocks($html,$codeBlocks);
        $html = $this->renderHeadings($html);
        $html = $this->renderHorizontalRules($html);
        $html = $this->renderTables($html);
        $html = $this->renderUnorderedLists($html);
        $html = $this->renderOrderedLists($html);
        $html = $this->renderInlineFormatting($html);
        $html = $this->renderParagraphs($html);
        foreach ($codeBlocks as $token=>$block) $html=str_replace(['<p>'.$token.'</p>',$token],$block,$html);
        $html = $this->sanitize($html);
        return $html;
    }


    /**
     * GitHub-style pipe tables. Runs after headings and rules but before lists and
     * paragraphs, so a table block is already wrapped in <table> by the time
     * renderParagraphs() sees it and is left alone.
     *
     *   | Name | Price |
     *   |------|------:|
     *   | Mala |   499 |
     *
     * The delimiter row sets alignment: :--- left, ---: right, :---: centre.
     */
    private function renderTables(string $html): string
    {
        $lines = explode("\n", $html);
        $out = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $header = trim($lines[$i]);
            $delimiter = trim($lines[$i + 1] ?? '');

            $looksLikeTable = $header !== '' && str_contains($header, '|')
                && $delimiter !== '' && preg_match('/^\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?$/', $delimiter) === 1;

            if (!$looksLikeTable) { $out[] = $lines[$i]; continue; }

            $headers = $this->splitTableRow($header);
            $aligns = array_map(static function (string $spec): string {
                $spec = trim($spec);
                $left = str_starts_with($spec, ':');
                $right = str_ends_with($spec, ':');
                if ($left && $right) return ' style="text-align:center"';
                if ($right) return ' style="text-align:right"';
                return '';
            }, $this->splitTableRow($delimiter));

            if (count($headers) === 0) { $out[] = $lines[$i]; continue; }

            $body = [];
            $j = $i + 2;
            while ($j < $count && trim($lines[$j]) !== '' && str_contains($lines[$j], '|')) {
                $body[] = $this->splitTableRow(trim($lines[$j]));
                $j++;
            }

            $table = '<table><thead><tr>';
            foreach ($headers as $index => $cell) {
                $table .= '<th' . ($aligns[$index] ?? '') . '>' . $cell . '</th>';
            }
            $table .= '</tr></thead><tbody>';
            foreach ($body as $row) {
                $table .= '<tr>';
                foreach ($headers as $index => $_) {
                    $table .= '<td' . ($aligns[$index] ?? '') . '>' . ($row[$index] ?? '') . '</td>';
                }
                $table .= '</tr>';
            }
            $table .= '</tbody></table>';

            $out[] = $table;
            $i = $j - 1;
        }

        return implode("\n", $out);
    }

    /** Splits a pipe row into cells, ignoring the optional leading and trailing pipes. */
    private function splitTableRow(string $row): array
    {
        $row = preg_replace('/^\||\|$/', '', trim($row)) ?? $row;
        return array_map('trim', explode('|', $row));
    }

    /**
     * Rebuild the parsed fragment, never trust strip_tags attribute preservation.
     * Only explicitly allowed nodes and attributes enter the returned document.
     */
    private function sanitize(string $html): string
    {
        if (!class_exists(\DOMDocument::class)) {
            // Fail closed on an incomplete PHP installation.
            return '<p>' . e(strip_tags($html)) . '</p>';
        }
        $source=new \DOMDocument('1.0','UTF-8');
        $previous=libxml_use_internal_errors(true);
        try {
            $loaded=$source->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
            if (!$loaded) return '<p>'.e(strip_tags($html)).'</p>';
            $target=new \DOMDocument('1.0','UTF-8');
            $root=$target->createElement('div');
            $target->appendChild($root);
            $body=$source->getElementsByTagName('body')->item(0);
            if (!$body) return '';
            foreach ($body->childNodes as $child) $this->copySafeNode($child,$root,$target);
            $result='';
            foreach ($root->childNodes as $child) $result.=$target->saveHTML($child);
            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function copySafeNode(\DOMNode $node,\DOMNode $parent,\DOMDocument $target,int $depth=0): void
    {
        if ($depth>64) return;
        if ($node instanceof \DOMText) {
            $parent->appendChild($target->createTextNode($node->nodeValue??''));
            return;
        }
        if (!$node instanceof \DOMElement) return;
        $tag=strtolower($node->tagName);
        $allowed=['p','br','strong','em','b','i','u','s','ol','ul','li','h1','h2','h3','h4','h5','h6','pre','code','blockquote','hr','a','img','table','thead','tbody','tr','th','td','div','span','sub','sup','del','ins','mark','figure','figcaption','cite','q','dl','dt','dd','abbr','address'];
        if (in_array($tag,['script','style','iframe','object','embed','svg','math','template','form','textarea','input','button','select','link','meta','base','noscript'],true)) return;
        if (!in_array($tag,$allowed,true)) {
            foreach ($node->childNodes as $child) $this->copySafeNode($child,$parent,$target,$depth+1);
            return;
        }
        $safe=$target->createElement($tag);
        if ($tag==='a') {
            $href=self::safeUrl($node->getAttribute('href'));
            if ($href===null) {
                foreach ($node->childNodes as $child) $this->copySafeNode($child,$parent,$target,$depth+1);
                return;
            }
            $safe->setAttribute('href',$href);
            if (preg_match('#^https?://#i',$href)) {
                $safe->setAttribute('target','_blank'); $safe->setAttribute('rel','noopener noreferrer');
            }
            if ($node->hasAttribute('title')) $safe->setAttribute('title',mb_substr($node->getAttribute('title'),0,300));
        }
        if ($tag==='img') {
            $src=self::safeUrl($node->getAttribute('src'),true);
            if ($src===null) return;
            $safe->setAttribute('src',$src);
            $safe->setAttribute('alt',mb_substr($node->getAttribute('alt'),0,500));
            $safe->setAttribute('loading','lazy');
            foreach (['width','height'] as $dimension) {
                $value=$node->getAttribute($dimension);
                if (preg_match('/^[1-9][0-9]{0,3}$/D',$value)) $safe->setAttribute($dimension,$value);
            }
        }
        if (preg_match('/^h[1-6]$/D',$tag)) {
            $id=$node->getAttribute('id');
            if (preg_match('/^heading-[a-z0-9-]{1,200}$/D',$id)) $safe->setAttribute('id',$id);
        }
        if ($tag==='code' && preg_match('/^language-[a-zA-Z0-9_-]{1,40}$/D',$node->getAttribute('class')))
            $safe->setAttribute('class',$node->getAttribute('class'));
        if (in_array($tag,['th','td'],true) && preg_match('/^text-align\s*:\s*(left|center|right)\s*;?$/iD',trim($node->getAttribute('style')),$match))
            $safe->setAttribute('style','text-align:'.strtolower($match[1]));
        $parent->appendChild($safe);
        if (!in_array($tag,['br','hr','img'],true))
            foreach ($node->childNodes as $child) $this->copySafeNode($child,$safe,$target,$depth+1);
    }

    private static function safeUrl(string $url,bool $image=false): ?string
    {
        $url=trim($url);
        if ($url===''||strlen($url)>2048||preg_match('/[\x00-\x20\x7f\\\\]/',$url)||str_starts_with($url,'//')) return null;
        if (preg_match('#^https?://#i',$url)) {
            $parts=parse_url($url);
            return is_array($parts)&&!empty($parts['host'])&&!isset($parts['user'])&&!isset($parts['pass'])?$url:null;
        }
        if (!$image && preg_match('/^mailto:[^@\s?]+@[^@\s?]+$/iD',$url)) return $url;
        if (!$image && preg_match('/^tel:\+?[0-9]{3,15}$/iD',$url)) return $url;
        if (str_contains($url,':') || ($image&&str_starts_with($url,'#'))) return null;
        return $url;
    }

    private function renderCodeBlocks(string $html,array &$blocks): string
    {
        return preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) use (&$blocks) {
            $lang = $m[1] ? ' class="language-' . e($m[1]) . '"' : '';
            $code = e($m[2]);
            $token='NEBOCODE'.bin2hex(random_bytes(16)).'END';
            $blocks[$token]='<pre><code' . $lang . '>' . $code . '</code></pre>';
            return $token;
        }, $html);
    }

    private function renderHeadings(string $html): string
    {
        return preg_replace_callback('/^(#{1,6})\s+(.+)$/m', function ($m) {
            $level = strlen($m[1]);
            $text = trim($m[2]);
            $id = 'heading-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($text));
            $id = trim($id, '-');
            return '<h' . $level . ' id="' . $id . '">' . e($text) . '</h' . $level . '>';
        }, $html);
    }

    private function renderHorizontalRules(string $html): string
    {
        return preg_replace('/^---$/m', '<hr>', $html);
    }

    private function renderUnorderedLists(string $html): string
    {
        $html = preg_replace_callback('/^(?:[-*+]\s+.*(?:\n|$))+/m', function ($m) {
            $items = preg_split('/\n[-*+]\s+/', trim($m[0]));
            $lis = '';
            foreach ($items as $i => $item) {
                if ($i === 0) {
                    $item = preg_replace('/^[-*+]\s+/', '', $item);
                }
                $lis .= '<li>' . e(trim($item)) . '</li>';
            }
            return '<ul>' . $lis . '</ul>';
        }, $html);
        return $html;
    }

    private function renderOrderedLists(string $html): string
    {
        $html = preg_replace_callback('/^(?:\d+\.\s+.*(?:\n|$))+/m', function ($m) {
            $items = preg_split('/\n\d+\.\s+/', trim($m[0]));
            $lis = '';
            foreach ($items as $i => $item) {
                if ($i === 0) {
                    $item = preg_replace('/^\d+\.\s+/', '', $item);
                }
                $lis .= '<li>' . e(trim($item)) . '</li>';
            }
            return '<ol>' . $lis . '</ol>';
        }, $html);
        return $html;
    }

    private function renderInlineFormatting(string $html): string
    {
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        $html = preg_replace_callback('/(?<!!)\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
            $url = $m[2];
            if (str_starts_with($url, 'http')) {
                return '<a href="' . e($url) . '" target="_blank" rel="noopener">' . e($m[1]) . '</a>';
            }
            return '<a href="' . e($url) . '">' . e($m[1]) . '</a>';
        }, $html);
        $html = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($m) {
            $alt = e($m[1] ?: '');
            $src = e($m[2]);
            if (str_starts_with($src, 'http')) {
                return '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
            }
            return '<img src="' . e($this->resolveAssetPath($src)) . '" alt="' . $alt . '" loading="lazy">';
        }, $html);
        return $html;
    }

    private function renderParagraphs(string $html): string
    {
        $blocks = preg_split('/\n{2,}/', $html);
        $result = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;
            if (preg_match('/^<(h[1-6]|ul|ol|li|pre|blockquote|hr|div|table|figure|p)/', $block)) {
                $result[] = $block;
            } else {
                $result[] = '<p>' . $block . '</p>';
            }
        }
        return implode("\n", $result);
    }

    private function resolveAssetPath(string $path): string
    {
        return $path;
    }
}
