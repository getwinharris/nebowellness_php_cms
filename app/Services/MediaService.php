<?php
namespace App\Services;

final class MediaService {
    private array $allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
    private string $storageDir;

    public function __construct(
        private ImageOptimizerService $optimizer = new ImageOptimizerService(),
        private DatabaseService $store = new DatabaseService()
    ) {
        $this->storageDir = storage_path('media');
    }

    /** Contexts the library knows about. 'blog' was missing, so blog uploads vanished. */
    private const CONTEXTS = ['shared', 'products', 'campaigns', 'consultants', 'blog'];

    /** Asset folders scanned so images shipped with the site appear alongside uploads. */
    private const ASSET_DIRS = [
        'products'    => 'assets/images/products',
        'campaigns'   => 'assets/images/campaigns',
        'consultants' => 'assets/images/consultants',
        'blog'        => 'assets/images/blog',
        'shared'      => 'assets/images/media',
    ];

    public function all(?string $context = null, bool $includeArchived = false): array {
        $contexts = $context ? [$context] : self::CONTEXTS;
        $all = [];
        foreach ($contexts as $ctx) {
            $all = array_merge($all, $this->readWithAliases($ctx));
        }
        foreach ($this->store->read('media_files') as $record) {
            if ($context !== null && ($record['context'] ?? '') !== $context) continue;
            $record['path'] = $record['url'] ?? '';
            $all[] = $record;
        }
        // The catalogue only records files uploaded through the admin, so product and
        // campaign artwork that ships with the site never appeared in the library and
        // could not be picked when editing a post. Scan the folders too and merge on
        // path, with catalogue entries winning so descriptions and usage survive.
        $byPath = [];
        foreach ($all as $item) {
            $key = (string)($item['url'] ?? $item['path'] ?? '');
            if ($key !== '') $byPath[$key] = $item;
        }
        foreach ($this->scanAssetFiles($context) as $file) {
            if (!isset($byPath[$file['url']])) $byPath[$file['url']] = $file;
        }
        $merged = array_values($includeArchived ? $byPath : array_filter($byPath, static fn(array $record) => empty($record['archived_at'])));
        usort($merged, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $merged;
    }

    /** Image files present on disk, as library entries. */
    private function scanAssetFiles(?string $context = null): array {
        $dirs = $context ? array_intersect_key(self::ASSET_DIRS, [$context => true]) : self::ASSET_DIRS;
        $out = [];
        foreach ($dirs as $ctx => $relative) {
            $dir = app_path($relative);
            if (!is_dir($dir)) continue;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            $paths = [];
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                if (!in_array(strtolower($file->getExtension()), ['jpg','jpeg','png','webp','gif','svg'], true)) continue;
                $paths[] = $file->getPathname();
            }
            sort($paths, SORT_STRING);   // stable order across platforms
            foreach ($paths as $path) {
                $url = '/' . ltrim(str_replace(app_path(), '', $path), '/');
                if (str_starts_with($url, '/assets/images/media/uploads/')) continue;  // MySQL owns managed uploads, including archived ones.
                $out[] = [
                    'id'          => 'file:' . $url,
                    'url'         => $url,
                    'path'        => $url,
                    'filename'    => basename($path),
                    'context'     => $ctx,
                    'description' => '',
                    'size'        => @filesize($path) ?: 0,
                    'created_at'  => date('c', @filemtime($path) ?: time()),
                    'source'      => 'file',
                ];
            }
        }
        return $out;
    }

    public function upload(array $files, string $context = 'shared', ?string $description = null, array $usedIn = []): array {
        if (empty($files['name']) || !is_array($files['name'])) return [];
        if (!in_array($context, self::CONTEXTS, true)) throw new \InvalidArgumentException('Unknown media context.');
        $folder = 'assets/images/media/uploads/' . date('Y/m');
        $dir = app_path($folder);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $uploaded = [];
        foreach ($files['name'] as $i => $original) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo((string)$original, PATHINFO_EXTENSION));
            if (!isset($this->allowed[$ext])) continue;
            $tmp = (string)($files['tmp_name'][$i] ?? '');
            $mime = is_file($tmp) ? (mime_content_type($tmp) ?: '') : '';
            if ($mime !== '' && $mime !== $this->allowed[$ext]) continue;
            $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', pathinfo((string)$original, PATHINFO_FILENAME)), '-')) ?: 'media';
            $filename = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $dir . '/' . $filename;
            if (!move_uploaded_file($tmp, $destPath)) continue;
            $webpPath = $this->optimizer->optimize($destPath, $dir, ['max_width' => 1920, 'max_height' => 1920, 'quality' => 80]);
            $url = '/' . $folder . '/';
            if ($webpPath && is_file($webpPath)) {
                if ($webpPath !== $destPath) unlink($destPath);
                $webpFilename = basename($webpPath);
                $url .= $webpFilename;
                $record = [
                    'id' => bin2hex(random_bytes(8)),
                    'filename' => $webpFilename,
                    'original_name' => (string)$original,
                    'url' => $url,
                    'description' => $description ?? '',
                    'context' => $context,
                    'mime' => 'image/webp',
                    'used_in' => $usedIn,
                    'created_at' => date('c'),
                ];
            } else {
                $url .= $filename;
                $record = [
                    'id' => bin2hex(random_bytes(8)),
                    'filename' => $filename,
                    'original_name' => (string)$original,
                    'url' => $url,
                    'description' => $description ?? '',
                    'context' => $context,
                    'mime' => $this->allowed[$ext],
                    'used_in' => $usedIn,
                    'created_at' => date('c'),
                ];
            }
            $record['source'] = 'upload';
            $this->store->upsert('media_files', $record);
            $uploaded[] = $record;
        }
        return $uploaded;
    }

    /** Only uploads created by this service may be archived. Legacy catalogues can include bundled files. */
    public static function isManagedUpload(array $record): bool {
        return ($record['source'] ?? '') === 'upload' && is_string($record['url'] ?? null)
            && (BrandAssetService::isManagedUrl($record['url']) || preg_match('~^/assets/images/media/uploads/[0-9]{4}/(?:0[1-9]|1[0-2])/[a-z0-9-]+-[0-9a-f]{8}\.(?:png|webp|jpg|jpeg|gif)$~D', $record['url'] ?? '') === 1);
    }

    public function library(bool $archived): array {
        $items = array_values(array_filter($this->all(includeArchived: true), static fn(array $item): bool =>
            $archived === !empty($item['archived_at'])));
        $references = [];
        if (array_filter($items, self::isManagedUpload(...))) $references = $this->references();
        foreach ($items as &$item) {
            $managed = self::isManagedUpload($item);
            $current = $references[$item['url'] ?? ''] ?? [];
            if ($managed) {
                // Upload-time labels are historical. Show them only while the same entity still uses the URL.
                $item['used_in'] = array_values(array_filter($item['used_in'] ?? [], static fn(array $usage): bool =>
                    isset($current[$usage['type'] ?? ''][$usage['id'] ?? ''])));
            }
            $item['in_use'] = $managed ? $current !== [] : !empty($item['used_in']);
            $item['selectable'] = $managed && ($archived || !$item['in_use']);
        }
        return $items;
    }

    private function references(): array {
        $references = [];
        $schema = require app_path('storage/schema/collections.php');
        foreach ($schema['collections'] as $collection => $definition) {
            if (empty($definition['media_fields']) && !in_array($collection, ['settings', 'orders'], true)) continue;
            // An uninstalled page collection has no media references. Read failures still block archiving.
            if ($collection==='cms_pages' && !$this->store->collectionExists($collection)) continue;
            foreach ($this->store->read($collection) as $record) {
                self::collectReferences($references, $record, $collection, (string)($record['id'] ?? ''));
            }
        }
        foreach (new \DirectoryIterator(app_path('content/blog/posts')) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'md') continue;
            $text = file_get_contents($file->getPathname());
            if ($text === false) throw new \RuntimeException('Unable to check blog image usage.');
            self::collectReferences($references, $text, 'blog', $file->getBasename('.md'));
        }

        return $references;
    }

    public static function archiveIds(array $ids): array {
        $ids=array_values(array_unique(array_filter($ids,'is_string')));
        if (!$ids||count($ids)>50) throw new \InvalidArgumentException('Select between one and 50 uploads.');
        return $ids;
    }

    public function setArchived(array $ids,bool $archive): array {
        return $this->store->archiveMedia($ids,$archive);
    }

    /** Internal serving-database operation; DatabaseService holds the CMS/media lock. */
    public function archiveWhileLocked(array $ids, bool $archive): array {
        $ids=self::archiveIds($ids);
        $references = $archive ? $this->references() : [];
        $changed = [];
        $blocked = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $record = $this->store->find('media_files', $id);
                if (!$record || !self::isManagedUpload($record) || $archive === !empty($record['archived_at'])
                    || ($archive && isset($references[$record['url']]))) {
                    ++$blocked;
                    continue;
                }
                // Keep the bytes at their original URL. Legacy non-CMS editors and
                // external pages are outside the native CMS/media lock.
                $this->store->upsertIfRevision('media_files', ['id'=>$id, 'archived_at'=>$archive ? date('c') : null], ResourceService::revision($record));
                $changed[] = $id;
            } catch (\Throwable) {
                ++$failed;
            }
        }
        return ['changed'=>$changed, 'blocked'=>$blocked, 'failed'=>$failed];
    }

    private static function referencePaths(mixed $value): array {
        $references=[];
        self::collectReferences($references,$value,'cms_pages','candidate');
        return array_keys($references);
    }

    /** Do not add a new managed reference or promote an archived draft image. */
    public function assertCmsReferences(array $before,array $after): void {
        $added=array_diff(self::referencePaths($after),self::referencePaths($before));
        $published=array_diff(self::referencePaths($after['published']??null),self::referencePaths($before['published']??null));
        $needed=array_unique(array_merge($added,$published));
        if ($needed===[]) return;
        $byUrl=[];
        foreach ($this->store->read('media_files') as $row) {
            if (self::isManagedUpload($row)) $byUrl[$row['url']][]=$row;
        }
        foreach ($needed as $url) {
            $matches=$byUrl[$url]??[];
            if (count($matches)!==1||!empty($matches[0]['archived_at'])) {
                throw new \DomainException('A selected managed image is archived or missing from the media library. Restore or replace it before saving.');
            }
        }
    }

    private static function collectReferences(array &$references, mixed $value, string $collection, string $id): void {
        if (is_array($value)) {
            foreach ($value as $child) self::collectReferences($references, $child, $collection, $id);
        } elseif (is_string($value)) {
            preg_match_all('~(?:^|/|\b)(assets/images/media/uploads/[0-9]{4}/[0-9]{2}/[a-z0-9-]+\.(?:png|webp|jpe?g|gif))~i', rawurldecode($value), $matches);
            foreach ($matches[1] as $path) $references['/' . $path][$collection][$id] = true;
            preg_match_all('~(?:^|/|\b)(assets/images/brand/(?:logo|favicon)-[0-9a-f]{32}\.(?:png|jpg|ico))~',rawurldecode($value),$brand);
            foreach ($brand[1] as $path) $references['/'.$path][$collection][$id]=true;
        }
    }

    public function recordUsage(array $mediaRecords, string $entityType, string $entityId, string $entityName): void {
        foreach ($mediaRecords as $media) {
            $record = $this->store->find('media_files', (string)($media['id'] ?? ''));
            if (!$record) continue;
            $usage = $record['used_in'] ?? [];
            foreach ($usage as $entry) {
                if (($entry['type'] ?? '') === $entityType && ($entry['id'] ?? '') === $entityId) continue 2;
            }
            $usage[] = ['type'=>$entityType, 'id'=>$entityId, 'name'=>$entityName];
            $this->store->upsertIfRevision('media_files', ['id'=>$record['id'], 'used_in'=>$usage], ResourceService::revision($record));
        }
    }

    private function filePath(string $context): string {
        return $this->storageDir . '/' . $context . '.yaml';
    }

    private function readYaml(string $context): array {
        $file = $this->filePath($context);
        if (is_file($file)) {
            $yaml = @file_get_contents($file);
            if ($yaml === false || $yaml === '') return [];
            $items = $this->parseYamlItems($yaml);
            return $items;
        }
        return [];
    }

    private function readWithAliases(string $context): array {
        $records = $this->readYaml($context);
        foreach ($records as &$r) {
            if (!isset($r['path']) && isset($r['url'])) {
                $r['path'] = $r['url'];
            }
        }
        return $records;
    }

    private function parseYamlItems(string $yaml): array {
        $items = [];
        $current = null;
        $usedInStack = null;
        $inUsedIn = false;
        foreach (explode("\n", $yaml) as $line) {
            if (preg_match('/^\s*-\s*id:\s*(.+)$/', $line, $m)) {
                if ($current) $items[] = $current;
                $current = ['id' => trim($m[1])];
                $inUsedIn = false;
            } elseif ($current && preg_match('/^\s+(filename|original_name|url|path|description|context|mime|created_at):\s*(.*)$/', $line, $m)) {
                if ($inUsedIn && $m[1] !== 'type' && $m[1] !== 'id' && $m[1] !== 'name') {
                    $inUsedIn = false;
                }
                if (!$inUsedIn) {
                    $current[$m[1]] = $this->unescapeYaml(trim($m[2]));
                }
            } elseif ($current && preg_match('/^\s+used_in:/', $line)) {
                $current['used_in'] = [];
                $inUsedIn = true;
            } elseif ($current && $inUsedIn && preg_match('/^\s+-\s*type:\s*(.+)$/', $line, $m)) {
                $usedInStack = ['type' => trim($m[1])];
            } elseif ($current && $inUsedIn && $usedInStack && preg_match('/^\s+id:\s*(.+)$/', $line, $m)) {
                $usedInStack['id'] = trim($m[1]);
            } elseif ($current && $inUsedIn && $usedInStack && preg_match('/^\s+name:\s*(.*)$/', $line, $m)) {
                $usedInStack['name'] = $this->unescapeYaml(trim($m[2]));
                $current['used_in'][] = $usedInStack;
                $usedInStack = null;
            }
        }
        if ($current) $items[] = $current;
        if (empty($items)) {
            $items = $this->parseLegacyYaml($yaml);
        }
        return $items;
    }

    private function parseLegacyYaml(string $yaml): array {
        // Fallback parser for old flat format
        $lines = explode("\n", $yaml);
        $items = [];
        $current = null;
        $i = 0;
        foreach ($lines as $line) {
            if (preg_match('/^\s*-\s*id:\s*(.+)$/', $line, $m)) {
                if ($current) $items[] = $current;
                $current = ['id' => trim($m[1])];
            } elseif ($current && preg_match('/^\s+(filename|original_name|path|context|mime|created_at):\s*(.*)$/', $line, $m)) {
                $current[$m[1]] = $this->unescapeYaml(trim($m[2]));
            }
        }
        if ($current) $items[] = $current;
        return $items;
    }

    private function unescapeYaml(string $value): string {
        if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value)-1] === '"') {
            return str_replace(['\"', '\n'], ['"', "\n"], substr($value, 1, -1));
        }
        return $value;
    }

    public static function migrateFromLegacy(): int {
        $oldFile = storage_path('media.yaml');
        if (!is_file($oldFile)) return 0;
        $yaml = @file_get_contents($oldFile);
        if ($yaml === false || $yaml === '') return 0;
        $service = new self();
        $items = $service->parseLegacyYaml($yaml);
        $grouped = [];
        foreach ($items as $item) {
            $ctx = $item['context'] ?? 'shared';
            if (!isset($item['url']) && isset($item['path'])) {
                $item['url'] = $item['path'];
            }
            $item['description'] = $item['description'] ?? '';
            $item['used_in'] = $item['used_in'] ?? [];
            $grouped[$ctx][] = $item;
        }
        foreach ($grouped as $ctx => $records) {
            foreach ($records as $record) {
                if (empty($record['id']) || $service->store->find('media_files', $record['id'])) continue;
                $service->store->upsert('media_files', $record);
            }
        }
        @rename($oldFile, $oldFile . '.bak');
        return count($items);
    }
}
