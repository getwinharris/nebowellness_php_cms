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

    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function published(): array {
        $pages = array_values(array_filter(
            $this->store->read('campaign_pages'),
            static fn(array $page): bool => ($page['status'] ?? 'draft') === 'published'
                && trim((string)($page['slug'] ?? '')) !== ''
                && trim((string)($page['title'] ?? '')) !== ''
        ));
        usort($pages, static fn(array $a, array $b): int => strcmp((string)$a['title'], (string)$b['title']));
        return $pages;
    }

    public function findPublished(string $slug): ?array {
        $matches = array_values(array_filter($this->published(), static fn(array $page): bool => $page['slug'] === $slug));
        return count($matches) === 1 ? $matches[0] : null;
    }
}
