<?php
namespace App\Services;

final class CampaignPageService {
    public const FORM_DEFAULTS = [
        'form_title' => 'Enquire about this program',
        'form_intro' => 'Tell us how to reach you so we can discuss the program with you.',
        'form_button' => 'Request a call back',
    ];
    public const FORM_NOTE = 'Submitting this form sends an enquiry. A session is arranged separately with the clinic.';
    public const FIELD_TYPES = [
        'text' => 'Short text',
        'email' => 'Email',
        'tel' => 'Phone number',
        'textarea' => 'Long text',
        'select' => 'Dropdown',
        'checkbox' => 'Checkbox',
    ];
    public const MAX_FIELDS = 20;
    public const MAX_OPTIONS = 30;
    public const MAX_LABEL_LENGTH = 80;
    public const MAX_OPTION_LENGTH = 80;

    public static function editorialImage(array $campaign): string {
        $default = '/assets/images/nebo-programs.webp';
        $configured = trim((string)($campaign['image_url'] ?? ''));
        $assetPath = parse_url($configured, PHP_URL_PATH) ?: '';
        $missingBundledAsset = str_starts_with($configured, '/assets/images/')
            && !is_file(app_path(rawurldecode($assetPath)));
        if (!$missingBundledAsset && !in_array($configured, ['', $default, '/assets/images/nebo-programs.png'], true)) {
            return $configured;
        }

        // These three campaigns used the former generic listing and detail artwork.
        return match ($campaign['slug'] ?? '') {
            'maternal-vitality' => '/assets/images/editorial/nebo-maternal-care.webp',
            'gut-restoration' => '/assets/images/blog/gut-health-food.webp',
            default => $default,
        };
    }

    /** Home placement is opt-in; unrelated campaigns never become programmes. */
    public static function programFields(array $input): array {
        $enabled = in_array($input['homepage_program'] ?? false, [true, 1, '1', 'on'], true);
        $result = ['homepage_program' => $enabled];
        foreach (['program_area' => 40, 'program_area_label' => 80, 'program_duration' => 80] as $field => $limit) {
            $value = $input[$field] ?? '';
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) throw new \InvalidArgumentException('Enter valid programme details.');
            $value = trim($value);
            if (mb_strlen($value) > $limit || preg_match('/[\x00-\x1f\x7f<>]/u', $value)) throw new \InvalidArgumentException('Programme fields must be bounded plain text.');
            $result[$field] = $value;
        }
        if ($enabled && (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $result['program_area']) || $result['program_area'] === 'all' || $result['program_area_label'] === '')) {
            throw new \InvalidArgumentException('Enter a programme category key and label. Use lowercase letters, numbers and hyphens for the key.');
        }
        $order = $input['program_order'] ?? '';
        if ($order === '') $order = 999;
        $order = filter_var($order, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>9999]]);
        if ($order === false) throw new \InvalidArgumentException('Programme order must be a whole number between 0 and 9999.');
        $result['program_order'] = $order;
        return $result;
    }

    public static function homepagePrograms(array $pages): array {
        $cards = [];
        foreach ($pages as $page) {
            if (($page['status'] ?? '') !== 'published' || !in_array($page['homepage_program'] ?? false, [true, 1, '1'], true)) continue;
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', (string)($page['slug'] ?? '')) || trim((string)($page['title'] ?? '')) === '') continue;
            try { $fields = self::programFields($page); } catch (\InvalidArgumentException) { continue; }
            $image = self::editorialImage($page);
            $size = str_starts_with($image, '/assets/images/') ? @getimagesize(app_path(ltrim($image, '/'))) : false;
            $cards[] = ['title'=>(string)$page['title'], 'slug'=>(string)$page['slug'], 'duration'=>$fields['program_duration'], 'area'=>$fields['program_area'], 'area_label'=>$fields['program_area_label'], 'order'=>$fields['program_order'], 'copy'=>(string)($page['summary'] ?? ''), 'image'=>[$image, (int)($size[0] ?? 1600), (int)($size[1] ?? 900)]];
        }
        usort($cards, static fn(array $a, array $b): int => [$a['order'], $a['slug']] <=> [$b['order'], $b['slug']]);
        return $cards;
    }

    /** Return normalized definitions and any validation errors indexed by editor row. */
    public static function validateFields(array $fields): array {
        $normalized = [];
        $errors = [];
        $usedKeys = [];
        foreach ($fields as $index => $field) {
            if (!$field instanceof \stdClass) {
                $errors[$index][] = 'Enter a field definition.';
                continue;
            }
            $key = $field->key ?? '';
            $label = is_string($field->label ?? null) ? trim($field->label) : '';
            $type = $field->type ?? '';
            $required = $field->required ?? false;
            $options = $field->options ?? [];

            if ($key === '') $key = 'field_' . bin2hex(random_bytes(12));
            if (!is_string($key) || !preg_match('/^[a-z0-9_]{1,50}$/D', $key)) {
                $errors[$index][] = 'The saved field key is invalid. Remove this field and add it again.';
            } elseif (isset($usedKeys[$key])) {
                $errors[$index][] = 'This field has a duplicate saved key. Remove the duplicate and add a new field if needed.';
            } else {
                $usedKeys[$key] = true;
            }
            if ($label === '' || mb_strlen($label) > self::MAX_LABEL_LENGTH) {
                $errors[$index][] = 'Enter a label of 1–' . self::MAX_LABEL_LENGTH . ' characters.';
            }
            if (!is_string($type) || !isset(self::FIELD_TYPES[$type])) {
                $errors[$index][] = 'Choose a supported field type.';
            }
            if (!is_bool($required)) $errors[$index][] = 'Choose whether this field is required.';
            if (!is_array($options) || count($options) > self::MAX_OPTIONS || ($type === 'select' && $options === [])) {
                $errors[$index][] = 'A dropdown needs 1–' . self::MAX_OPTIONS . ' options.';
            } else {
                $validOptions = [];
                foreach ($options as $optionIndex => $option) {
                    $option = is_string($option) ? trim($option) : '';
                    $number = $optionIndex + 1;
                    if ($option === '' || mb_strlen($option) > self::MAX_OPTION_LENGTH) {
                        $errors[$index][] = 'Enter 1–' . self::MAX_OPTION_LENGTH . ' characters for option ' . $number . '.';
                    } elseif (in_array($option, $validOptions, true)) {
                        $errors[$index][] = 'Option ' . $number . ' repeats an earlier option.';
                    } else {
                        $validOptions[] = $option;
                    }
                }
                $options = $validOptions;
            }
            if (!isset($errors[$index])) {
                $normalized[] = ['key' => $key, 'label' => $label, 'type' => $type, 'required' => $required, 'options' => $options];
            }
        }
        return [$normalized, $errors];
    }

    /** Preserve the visitor's question wording even if the campaign is edited later. */
    public static function answerLabel(array $submission, string $key): string {
        $label = $submission['form_labels'][$key] ?? null;
        return is_string($label) && trim($label) !== '' ? $label : ucwords(str_replace('_', ' ', $key));
    }

    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function published(): array {
        $pages = $this->publicPages($this->store->read('campaign_pages'));
        usort($pages, static fn(array $a, array $b): int => strcmp((string)$a['title'], (string)$b['title']));
        return $pages;
    }

    public function newestPublished(): array {
        // Use persisted creation order, including records created before this UI.
        // Editing an older offering must not turn it into the newest one.
        $rows = $this->store->query('SELECT id, _data FROM campaign_pages ORDER BY _created_at DESC, id ASC');
        $pages = array_map(static function (array $row): array {
            $data = json_decode((string)($row['_data'] ?? ''), true);
            return is_array($data) ? array_replace($data, ['id' => $row['id']]) : [];
        }, $rows);
        return $this->publicPages($pages);
    }

    private function publicPages(array $pages): array {
        return array_values(array_filter(
            $pages,
            static fn(array $page): bool => ($page['status'] ?? 'draft') === 'published'
                && trim((string)($page['slug'] ?? '')) !== ''
                && trim((string)($page['title'] ?? '')) !== ''
        ));
    }

    public function findPublished(string $slug): ?array {
        $matches = array_values(array_filter($this->published(), static fn(array $page): bool => $page['slug'] === $slug));
        return count($matches) === 1 ? $matches[0] : null;
    }
}
