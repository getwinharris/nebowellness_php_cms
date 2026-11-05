<?php
namespace App\Services;
final class RateLimiter {
    private string $file;
    private array $data;
    public function __construct(?string $file = null) {
        $this->file = $file ?? storage_path('logs/rate_limit.php');
        $this->data = is_file($this->file) ? require $this->file : [];
    }
    public function consume(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new \RuntimeException('Rate-limit storage directory is unavailable.');
        $lock = fopen($this->file . '.lock', 'c');
        if ($lock === false) throw new \RuntimeException('Rate-limit lock is unavailable.');
        try {
            if (!flock($lock, LOCK_EX)) throw new \RuntimeException('Rate-limit lock could not be acquired.');
            if (function_exists('opcache_invalidate')) opcache_invalidate($this->file, true);
            $this->data = is_file($this->file) ? require $this->file : [];
            if (!$this->check($key, $maxAttempts, $windowSeconds)) return false;
            $this->data[$key][] = time();
            $this->save();
            return true;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    public function check(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool {
        $now = time();
        $this->clean($key, $now, $windowSeconds);
        $attempts = $this->data[$key] ?? [];
        return count($attempts) < $maxAttempts;
    }
    public function hit(string $key): void {
        $now = time();
        $this->data[$key][] = $now;
        $this->save();
    }
    public function getRemaining(string $key, int $maxAttempts = 5, int $windowSeconds = 60): int {
        $now = time();
        $this->clean($key, $now, $windowSeconds);
        $attempts = $this->data[$key] ?? [];
        return max(0, $maxAttempts - count($attempts));
    }
    private function clean(string $key, int $now, int $windowSeconds): void {
        if (isset($this->data[$key])) {
            $this->data[$key] = array_values(array_filter($this->data[$key], fn($ts) => $ts > $now - $windowSeconds));
            if (empty($this->data[$key])) unset($this->data[$key]);
        }
    }
    private function save(): void {
        $content = '<?php return ' . var_export($this->data, true) . ';';
        $dir = dirname($this->file);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new \RuntimeException('Rate-limit storage directory is unavailable.');
        $temp = tempnam($dir, '.rate-');
        if ($temp === false) throw new \RuntimeException('Rate-limit storage could not create a temporary file.');
        try {
            if (file_put_contents($temp, $content, LOCK_EX) !== strlen($content)) throw new \RuntimeException('Rate-limit storage write failed.');
            if (!rename($temp, $this->file)) throw new \RuntimeException('Rate-limit storage replace failed.');
            if (function_exists('opcache_invalidate')) opcache_invalidate($this->file, true);
        } finally { if (is_file($temp)) unlink($temp); }
    }
}
