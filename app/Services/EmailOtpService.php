<?php
namespace App\Services;

final class EmailOtpService
{
    private const TTL = 600;
    private const RESEND_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;
    /** How long a transport failure disables verification for this visitor's session. */
    private const FAILURE_TTL = 900;

    public function send(string $context, string $email): void
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Enter a valid email address.');
        $key = $this->key($context, $email);
        $existing = $_SESSION['email_otps'][$key] ?? [];
        if ((int)($existing['sent_at'] ?? 0) + self::RESEND_SECONDS > time()) {
            throw new \DomainException('Please wait one minute before requesting another code.');
        }
        $code = (string)random_int(100000, 999999);
        $record = [
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + self::TTL,
            'sent_at' => time(),
            'attempts' => 0,
            'verified' => false,
        ];
        $secrets = (new SecretService())->all();
        try {
            (new SmtpMailer($secrets))->send(
                $email,
                'Your Nebo verification code',
                '<p>Your verification code is:</p><p style="font-size:28px;font-weight:700;letter-spacing:6px">' . e($code) . '</p><p>This code expires in 10 minutes. If you did not request it, you can ignore this email.</p>'
            );
        } catch (\Throwable $error) {
            // Remember the transport failure so the contact form can accept a plain
            // submission instead of demanding a code that can never arrive.
            $_SESSION['email_otp_failed_at'] = time();
            throw $error;
        }
        unset($_SESSION['email_otp_failed_at']);
        // Start expiry and resend enforcement only after the SMTP transport accepts
        // the message. A temporary transport failure must not lock the visitor out.
        $_SESSION['email_otps'][$key] = $record;
    }

    /**
     * True when Admin → Integrations holds complete SMTP credentials. This is a pure
     * configuration check and never opens a socket.
     */
    public function smtpConfigured(): bool
    {
        try {
            return (new SmtpMailer((new SecretService())->all()))->configured();
        } catch (\Throwable $error) {
            error_log('Email OTP configuration check failed: ' . $error->getMessage());
            return false;
        }
    }

    /**
     * True when a verification email can actually be delivered. A recent transport
     * failure counts as unavailable too: a host that blocks outbound SMTP would
     * otherwise reject every contact submission because no code can ever arrive.
     */
    public function available(): bool
    {
        if ((int)($_SESSION['email_otp_failed_at'] ?? 0) + self::FAILURE_TTL >= time()) return false;
        return $this->smtpConfigured();
    }

    public function verify(string $context, string $email, string $code): bool
    {
        $key = $this->key($context, strtolower(trim($email)));
        $record = $_SESSION['email_otps'][$key] ?? null;
        if (!is_array($record) || (int)($record['expires_at'] ?? 0) < time()) return false;
        // An already verified record stays valid for the pending submission. Counting
        // repeat checks as failed attempts exhausted MAX_ATTEMPTS and then rejected a
        // correctly verified code before the form could be sent.
        if (!empty($record['verified'])) return true;
        if ((int)($record['attempts'] ?? 0) >= self::MAX_ATTEMPTS) return false;
        $record['attempts'] = (int)($record['attempts'] ?? 0) + 1;
        if (preg_match('/^\d{6}$/', $code) && password_verify($code, (string)($record['hash'] ?? ''))) {
            $record['verified'] = true;
            unset($record['hash']);
        }
        $_SESSION['email_otps'][$key] = $record;
        return !empty($record['verified']);
    }

    public function isVerified(string $context, string $email): bool
    {
        $record = $_SESSION['email_otps'][$this->key($context, strtolower(trim($email)))] ?? null;
        return is_array($record) && !empty($record['verified']) && (int)($record['expires_at'] ?? 0) >= time();
    }

    public function consume(string $context, string $email): void
    {
        unset($_SESSION['email_otps'][$this->key($context, strtolower(trim($email)))]);
    }

    private function key(string $context, string $email): string
    {
        return hash_hmac('sha256', $context . "\0" . $email, session_id() ?: 'no-session');
    }
}
