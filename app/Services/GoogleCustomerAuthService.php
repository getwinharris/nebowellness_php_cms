<?php
namespace App\Services;

final class GoogleCustomerAuthService {
    public static function callbackUri(): string {
        $config = require app_path('config/database.php');
        return $config['app_url'] . '/auth/google/callback';
    }

    public static function consumeState(array &$session, mixed $state, int $now): ?array {
        $transaction = $session['google_oauth'] ?? null;
        if ($transaction === null || !is_string($state) || $state === '' || !hash_equals($transaction['state'], $state)) {
            return null;
        }

        // Consume a matching attempt before processing denial, exchanging the code, or loading the user.
        unset($session['google_oauth']);
        if ($transaction['issued_at'] > $now || $transaction['issued_at'] <= $now - 600) return null;
        return $transaction;
    }

    /** A verified identity waiting for the customer's explicit password or signup confirmation. */
    public static function pending(array &$session, int $now): ?array {
        $pending = $session['google_link'] ?? null;
        if (!is_array($pending) || ($pending['expires_at'] ?? 0) <= $now) {
            unset($session['google_link']);
            return null;
        }
        return $pending['identity'];
    }

    /** Never infer consent or ownership from a matching email address. */
    public static function canBind(array $identity, array $users, array $ownerEmails, ?string $passwordProvenId): bool {
        foreach ($ownerEmails as $email) {
            if (strcasecmp(trim((string)$email), $identity['email']) === 0) return false;
        }
        $matches = [];
        foreach ($users as $user) {
            if (($user['google_subject'] ?? '') === $identity['sub']) return false;
            if (strcasecmp((string)($user['email'] ?? ''), $identity['email']) === 0) $matches[] = $user;
        }
        if ($passwordProvenId === null) return !$matches;
        if (count($matches) !== 1) return false;
        $user = $matches[0];
        return ($user['id'] ?? '') === $passwordProvenId && ($user['role'] ?? '') === 'customer'
            && empty($user['is_admin']) && empty($user['must_change_password']) && empty($user['google_subject'])
            && !empty($user['accepted_terms_at']);
    }

    public static function linkedSession(array $identity, array $users, array $ownerEmails): ?array {
        $linked = null;
        $emailMatches = 0;
        foreach ($users as $user) {
            if (strcasecmp((string)($user['email'] ?? ''), $identity['email']) === 0) $emailMatches++;
            if (($user['google_subject'] ?? null) !== $identity['sub']) continue;
            if ($linked !== null) return null;
            $linked = $user;
        }
        // Other customer data is keyed by email, so don't accept a duplicate or drifted address.
        // Never infer a Google binding from an email match or a legacy provider-shaped user ID.
        if ($linked === null || $emailMatches !== 1 || empty($linked['id'])
            || strcasecmp((string)($linked['email'] ?? ''), $identity['email']) !== 0
            || ($linked['role'] ?? '') !== 'customer' || !empty($linked['is_admin'])
            || !empty($linked['must_change_password']) || empty($linked['accepted_terms_at'])) {
            return null;
        }
        foreach ($ownerEmails as $ownerEmail) {
            if (strcasecmp(trim((string)$ownerEmail), $identity['email']) === 0) return null;
        }
        return ['sub'=>$linked['id'], 'email'=>$linked['email'], 'name'=>$linked['name'] ?? '', 'role'=>'customer'];
    }
}
