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
