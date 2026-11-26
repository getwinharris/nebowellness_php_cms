<?php
namespace App\Services;

final class SettingsService {
    public const SETTINGS_ID = 'app_settings';

    /** Modules that can be switched off from Admin → Site Settings. */
    public const MODULES = [
        'blog'    => 'Blog',
        'campaigns' => 'Campaign landing pages',
    ];

    /** Request-level memo. DatabaseService has no caching and every read is a full table scan. */
    private static ?array $cache = null;

    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    private function load(): array {
        if (self::$cache !== null) return self::$cache;
        try {
            $rows = $this->store->read('settings');
        } catch (\Throwable) {
            return self::$cache = [];
        }
        // Always prefer the canonical record. `read()` returns an unordered SELECT *,
        // so `[0]` can be a stale duplicate row with different (or plaintext) values.
        foreach ($rows as $row) {
            if ((string)($row['id'] ?? '') === self::SETTINGS_ID) return self::$cache = $row;
        }
        return self::$cache = ($rows[0] ?? []);
    }

    public static function flushCache(): void { self::$cache = null; }

    public function public(): array {
        $settings = $this->load();
        return $settings ?: ['shipping_mode' => 'free', 'flat_rate' => 0];
    }

    public function admin(): array { return $this->load(); }

    /**
     * Module on/off flags. Absent means enabled, so existing installs are unaffected.
     * @return array<string,bool>
     */
    public function modules(): array {
        $settings = $this->load();
        $modules = ['shop'=>false]; // Retired compatibility flag; never owner-enableable.
        foreach (array_keys(self::MODULES) as $key) {
            $modules[$key] = (string)($settings['module_' . $key] ?? '1') !== '0';
        }
        return $modules;
    }

    /** Runtime retrieval must not mistake a failed settings read for enabled modules. */
    public function strictModules(): array {
        $rows=$this->store->read('settings');
        $settings=$rows[0]??[];
        foreach ($rows as $row) if (($row['id']??'')===self::SETTINGS_ID) { $settings=$row; break; }
        $modules=['shop'=>false];
        foreach (array_keys(self::MODULES) as $key) $modules[$key]=(string)($settings['module_'.$key]??'1')!=='0';
        return $modules;
    }

    public function moduleEnabled(string $key): bool {
        return $this->modules()[$key] ?? false;
    }

    /** Owner-supplied minimum customer figure. Empty means do not publish it. */
    public static function normalizeFoundationCustomersMin(mixed $value): ?int {
        if ($value === null || $value === '') return null;
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-9][0-9]{0,7}$/D', (string)$value)) {
            throw new \InvalidArgumentException('Enter a positive whole number up to 99,999,999, or leave blank to hide.');
        }
        return (int)$value;
    }

    public function foundationCustomersMin(): ?int {
        try { return self::normalizeFoundationCustomersMin($this->load()['foundation_customers_min'] ?? null); }
        catch (\InvalidArgumentException) { return null; }
    }

    public function saveFoundationCustomersMin(mixed $value, string $revision): void {
        $count = self::normalizeFoundationCustomersMin($value);
        // No read-outage defaults or full-record overwrite on this narrow settings form.
        $current = $this->store->find('settings', self::SETTINGS_ID);
        if ($current === null) throw new \DomainException('Save the site settings before publishing a customer figure.');
        $this->store->upsertIfRevision('settings', ['id'=>self::SETTINGS_ID, 'foundation_customers_min'=>$count], $revision);
        self::flushCache();
    }

    public function saveClinicChannels(mixed $phones, mixed $whatsapp, string $revision): void {
        $numbers=ClinicContactService::normalize($phones);
        $messages=ClinicContactService::normalize($whatsapp);
        $this->store->upsertIfRevision('settings',['id'=>self::SETTINGS_ID,'clinic_phones'=>$numbers,'clinic_whatsapp'=>$messages],$revision);
        self::flushCache();
    }
    public function savePublic(array $settings): void {
        unset($settings['module_shop'], $settings['shipping_mode'], $settings['flat_rate']);
        $settings['id'] = self::SETTINGS_ID;
        $this->store->upsert('settings', $settings, 'id');
        self::flushCache();
    }
}
