<?php
namespace App\Controllers;
use App\Services\{EnvService,SecretService,DatabaseService,GoogleCustomerAuthService,PasswordResetService,AddressService,MailQueueService,SettingsService};
use App\Integrations\GoogleOAuth\GoogleOAuthClient;
final class AuthController extends BaseController {
  public function googleRedirect(): void {
    if (!empty($_SESSION['user'])) $this->redirect('/login');
    $s = (new SecretService())->all();
    if (empty($s['google_client_id']) || empty($s['google_client_secret'])) {
      $this->flash('Google login is not configured yet.', 'warning');
      $this->redirect('/login');
    }
    $transaction = [
      'state'=>bin2hex(random_bytes(32)), 'nonce'=>bin2hex(random_bytes(32)),
      'redirect_uri'=>GoogleCustomerAuthService::callbackUri(), 'issued_at'=>time(),
    ];
    $_SESSION['google_oauth'] = $transaction;
    $client = new GoogleOAuthClient($s['google_client_id'], $s['google_client_secret']);
    $this->redirect($client->authorizationUrl($transaction['redirect_uri'], $transaction['state'], $transaction['nonce']));
  }

  public function callback(): void {
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    $transaction = GoogleCustomerAuthService::consumeState($_SESSION, $_GET['state'] ?? null, time());
    if (!empty($_SESSION['user'])) $this->redirect('/login');
    if ($transaction === null) {
      $this->flash('Google sign-in expired or was invalid. Please try again.', 'error');
      $this->redirect('/login');
    }
    $code = $_GET['code'] ?? null;
    if (isset($_GET['error']) || !is_string($code) || $code === '') {
      $this->flash('Google sign-in was not completed. You can sign in with your password.', 'info');
      $this->redirect('/login');
    }
    try {
      $s = (new SecretService())->all();
      if (empty($s['google_client_id']) || empty($s['google_client_secret'])) {
        throw new \RuntimeException('Google sign-in is not configured.');
      }
      $client = new GoogleOAuthClient($s['google_client_id'], $s['google_client_secret']);
      $identity = $client->exchange($code, $transaction['redirect_uri'], $transaction['nonce']);
      $store = new DatabaseService();
      // SettingsService falls back on read failure. A failed owner lookup must instead stop OAuth.
      $ownerEmails = array_column($store->read('settings'), 'admin_email');
      $ownerEmails[] = getenv('ADMIN_EMAIL') ?: '';
      $user = GoogleCustomerAuthService::linkedSession($identity, $store->read('users'), $ownerEmails);
      $target = module_on('shop') ? '/account/dashboard' : '/';
    } catch (\Throwable) {
      $this->flash('Google sign-in is temporarily unavailable. Please try again or use your password.', 'error');
      $this->redirect('/login');
    }
    if ($user === null) {
      $this->flash('Google sign-in is only available for eligible linked customer accounts. Please sign in with your password or create an account.', 'info');
      $this->redirect('/login');
    }

    // A session is issued only after finding a durable, eligible Google binding.
    if (!session_regenerate_id(true)) {
      $this->flash('Could not start a sign-in session. Please try again.', 'error');
      $this->redirect('/login');
    }
    $_SESSION['user'] = $user;
    $this->flash('Signed in.', 'success');
    $this->redirect($target);
  }

 public function logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
   $params = session_get_cookie_params();
   setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
  }
  session_destroy();
  session_start();
  $this->flash('You are signed out.','info');
  $this->redirect('/login');
 }
  public function register(): void {
     if (!empty($_SESSION['user'])) $this->redirect((($_SESSION['user']['role'] ?? '') === 'admin') ? '/admin' : (module_on('shop') ? '/account/dashboard' : '/'));
     $this->seoKey = 'register';
     $this->render('public/register');
  }
  public function registerPost(): void {
    $this->validateCsrf();
    $this->checkRateLimit('register', 3, 300);
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    if ($password === '' || $email === '' || $name === '' || $phone === '' || $address === '' || $city === '' || $pincode === '') { $this->flash('Account and delivery address fields are required.','error'); $this->redirect('/register'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->flash('Invalid email address.','error'); $this->redirect('/register'); }
    if ($password !== $confirm) { $this->flash('Passwords do not match.','error'); $this->redirect('/register'); }
    if (empty($_POST['accept_terms'])) { $this->flash('You must accept the Terms of Service and Privacy Policy to register.','error'); $this->redirect('/register'); }
    $store = new DatabaseService();
    $users = $store->read('users');
    foreach ($users as $u) { if (($u['email'] ?? '') === $email) { $this->flash('Email already registered.','error'); $this->redirect('/login'); } }
    $id = bin2hex(random_bytes(8));
    $role = 'customer';
    $record = ['id'=>$id,'email'=>$email,'name'=>$name,'role'=>$role,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'accepted_terms_at'=>date('c')];
    $store->upsert('users',$record,'id');
    (new AddressService($store))->save($email, ['address_name'=>'Home','name'=>$name,'phone'=>$phone,'address'=>$address,'city'=>$city,'pincode'=>$pincode,'is_default'=>true]);
    session_regenerate_id(true);
    $_SESSION['user'] = ['sub'=>$id,'email'=>$email,'name'=>$name,'role'=>$role];
    try {
        (new MailQueueService())->enqueue('welcome', $email, 'Welcome to Nebo Lifestyle Clinic',
            '<p>Hello ' . e($name) . ',</p><p>Your account is ready. You can view orders and saved addresses in your dashboard.</p>'
            . \App\Services\MailQueueService::button('Open your dashboard', $this->siteUrl('/account/dashboard')));
    } catch (\Throwable) {}
    $this->flash('Registered and signed in.','success');
    $this->redirect('/');
 }
  public function loginPost(): void {
    $this->validateCsrf();
    $this->checkRateLimit('login', 5, 60);
    $email = trim($_POST['identifier'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') { $this->flash('Username or email and password required.','error'); $this->redirect('/login'); }
    $admin = (new EnvService())->adminCredentials();
    if ($admin['email'] !== '' && $admin['password'] !== '' && $email === $admin['email']) {
        if (password_verify($password, $admin['password']) || hash_equals($admin['password'], $password)) {
            if (hash_equals($admin['password'], $password) && ($admin['source'] ?? '') === 'settings') {
                (new EnvService())->saveAdminCredentials(['admin_password' => $password]);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = ['sub'=>'env-admin','email'=>$admin['email'],'name'=>$admin['username'] ?: 'Admin','role'=>'admin'];
            try { (new MailQueueService())->enqueueLoginNotification($admin['email'], $admin['username'] ?: 'Admin', 'admin'); }
            catch (\Throwable $e) { error_log('Login notification failed: ' . $e->getMessage()); }
            $this->flash('Signed in.','success');
            $this->redirect('/admin');
        }
    }
    $store = new DatabaseService();
    $users = $store->read('users');
    foreach ($users as $u) {
        $matches = strcasecmp((string)($u['email'] ?? ''), $email) === 0 || strcasecmp((string)($u['username'] ?? ''), $email) === 0;
        if ($matches && !empty($u['password_hash']) && password_verify($password,$u['password_hash'])) {
            if (($u['role'] ?? '') === 'consultant') { $this->flash('Consultant access is managed by the site administrator.','info'); $this->redirect('/login'); }
            session_regenerate_id(true);
            $_SESSION['user'] = ['sub'=>$u['id'],'email'=>$u['email'] ?? '','username'=>$u['username'] ?? '','name'=>$u['name'] ?? '','role'=>$u['role'] ?? (!empty($u['is_admin']) ? 'admin' : 'customer'),'consultant_slug'=>$u['consultant_slug'] ?? '','must_change_password'=>(bool)($u['must_change_password'] ?? false)];
            $this->flash('Signed in.','success');
            session_write_close();
            $this->redirect(($u['role'] ?? '') === 'customer' && module_on('shop') ? '/account/dashboard' : '/');
        }
    }
    $this->flash('Invalid credentials.','error');
    error_log(sprintf('Failed login attempt for %s from %s', $email, $_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $this->redirect('/login');
  }
  public function forgotPassword(): void {
     $this->seoKey = 'forgot-password';
     $this->render('public/forgot-password');
  }
  public function forgotPasswordPost(): void {
    $this->validateCsrf();
    $this->checkRateLimit('forgot-password', 3, 120);
    $email = trim($_POST['email'] ?? '');
    if ($email !== '') {
        $token = (new PasswordResetService())->createToken($email);
        if ($token) {
            // The link is emailed, never rendered. Showing it would hand any visitor a
            // working reset for an address they do not control.
            $link = $this->siteUrl('/reset-password?token=' . urlencode($token));
            try {
                (new MailQueueService())->enqueue('password_reset', $email, 'Reset your Nebo Lifestyle Clinic password',
                    '<p>We received a request to reset your password.</p>'
                    . \App\Services\MailQueueService::button('Reset your password', $link)
                    . '<p>If you did not request this, you can ignore this email.</p>');
            } catch (\Throwable) {}
        }
    }
    // Identical response either way, so the form cannot be used to discover which
    // addresses are registered.
    $this->flash('If this email is registered, a reset link has been sent.','info');
    $this->redirect('/forgot-password');
  }
  public function resetPassword(): void {
     $this->seoKey = 'reset-password';
     $this->render('public/reset-password', ['token' => $_GET['token'] ?? '']);
 }
  public function resetPasswordPost(): void {
    $this->validateCsrf();
    $this->checkRateLimit('reset-password', 3, 120);
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if ($password === '' || $password !== $confirm) {
        $this->flash('Passwords do not match.','error');
        $this->redirect('/reset-password?token=' . urlencode($token));
    }
    $resetEmail = (new PasswordResetService())->emailForToken($token);
    if ((new PasswordResetService())->resetPassword($token, $password)) {
        // Confirm the change to the account holder. If the reset was not theirs, this
        // is the only signal they get that someone else changed their password.
        if ($resetEmail !== '' && filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                (new MailQueueService())->enqueue('password_changed', $resetEmail,
                    'Your Nebo Lifestyle Clinic password was changed',
                    '<p>Your account password was just changed.</p>'
                    . '<p>If this was not you, contact us immediately at '
                    . '<a href="mailto:nebolifestyleclinic@gmail.com">nebolifestyleclinic@gmail.com</a>.</p>');
            } catch (\Throwable $e) { error_log('Password change mail failed: ' . $e->getMessage()); }
        }
        $this->flash('Password updated. Please sign in.','success');
        $this->redirect('/login');
    }
    $this->flash('Reset link is invalid or expired.','error');
    $this->redirect('/forgot-password');
 }
}
