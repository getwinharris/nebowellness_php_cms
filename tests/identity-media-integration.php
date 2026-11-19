<?php
declare(strict_types=1);

use App\Services\{AddressService, DatabaseService, GoogleCustomerAuthService, MediaService};

if (PHP_SAPI !== 'cli' || !in_array(getenv('BAPX_MYSQL_HOST'), ['localhost', '127.0.0.1'], true)
    || !preg_match('/(?:_test|_fixture)$/D', (string)getenv('BAPX_MYSQL_DB')) || getenv('BAPX_TEST_MODE') === '1') {
    fwrite(STDERR, "Set a disposable local BAPX_MYSQL_* connection to a _test or _fixture database.\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';

function verifyIdentityMedia(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}

$db = new DatabaseService(true);
$prefix = 'im-' . bin2hex(random_bytes(6));
$email = $prefix . '@example.test';
$identity = ['sub'=>$prefix, 'email'=>$email];
$user = ['id'=>$prefix, 'email'=>$email, 'name'=>'Test customer', 'role'=>'customer',
    'accepted_terms_at'=>date('c'), 'password_hash'=>password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
    'google_subject'=>$identity['sub']];
$address = ['address_name'=>'Home','name'=>'Fixture','phone'=>'000000','city'=>'Test','pincode'=>'000000','address'=>'Fixture'];
$url = '/assets/images/media/uploads/' . date('Y/m') . '/' . $prefix . '-' . bin2hex(random_bytes(4)) . '.png';
$file = app_path(ltrim($url, '/'));
$media = new MediaService(store: $db);
try {
    try {
        $db->registerCustomer($user, array_replace($address, ['address'=>'']));
        throw new RuntimeException('Invalid onboarding unexpectedly succeeded.');
    } catch (InvalidArgumentException) {
        verifyIdentityMedia($db->find('users', $prefix) === null, 'Failed address persistence must roll the account back.');
    }
    $db->registerCustomer($user, $address);
    $linked = GoogleCustomerAuthService::linkedSession($identity, $db->read('users'), []);
    verifyIdentityMedia(($linked['sub'] ?? '') === $prefix, 'A saved verified customer can sign in.');
    try {
        $db->registerCustomer(array_replace($user, ['id'=>$prefix . '-copy', 'email'=>strtoupper($email)]), $address);
        throw new RuntimeException('Duplicate email unexpectedly registered.');
    } catch (DomainException) {
        verifyIdentityMedia(count((new AddressService($db))->forCustomer($email)) === 1, 'Duplicate signup must not create another delivery address.');
    }

    if (!is_dir(dirname($file))) mkdir(dirname($file), 0775, true);
    file_put_contents($file, 'fixture content');
    $db->upsert('media_files', ['id'=>$prefix, 'source'=>'upload','url'=>$url,'context'=>'shared','used_in'=>[]]);
    verifyIdentityMedia($media->setArchived([$prefix], true)['changed'] === [$prefix], 'Unused upload is archived.');
    // Simulate a stale open editor publishing immediately after archive.
    $db->upsert('categories', ['id'=>$prefix, 'image_url'=>$url]);
    verifyIdentityMedia(is_file($file) && !in_array($url, array_column($media->all('shared'), 'url'), true), 'Published link survives while archived upload stays out of the picker.');
    verifyIdentityMedia($media->setArchived([$prefix], false)['changed'] === [$prefix], 'Owner can restore an archived upload.');
    verifyIdentityMedia($media->setArchived([$prefix], true)['blocked'] === 1, 'An already published reference blocks another archive.');
    $entry = array_values(array_filter($media->library(false), fn($item) => ($item['id'] ?? '') === $prefix))[0];
    verifyIdentityMedia($entry['in_use'] && !$entry['selectable'], 'Referenced image is protected in the library.');
    echo "PASS customer registration transaction, returning Google identity, duplicate prevention and recoverable media archive.\n";
} finally {
    foreach ((new AddressService($db))->forCustomer($email) as $saved) $db->delete('addresses', $saved['id']);
    $db->delete('users', $prefix);
    $db->delete('users', $prefix . '-copy');
    $db->delete('categories', $prefix);
    $db->delete('media_files', $prefix);
    if (is_file($file)) unlink($file);
}
