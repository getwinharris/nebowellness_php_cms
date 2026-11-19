<div class="auth-page">
    <div class="auth-visual">
        <h2>Begin Your Journey</h2>
        <p>Create your Nebo account to manage product orders, delivery addresses, and account preferences.</p>
    </div>
    <div class="auth-form-container">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p>Join Nebo Wellness today</p>
            <form method="post" action="/register" class="auth-form">
                <?php $csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16)); ?>
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <div class="form-group">
                    <label for="register-name">Full Name</label>
                    <input type="text" id="register-name" name="name" autocomplete="name" required placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label for="register-email">Email Address</label>
                    <input type="email" id="register-email" name="email" autocomplete="email" value="<?= e($pendingGoogleEmail ?? '') ?>" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label for="register-phone">Phone</label>
                    <input type="tel" id="register-phone" name="phone" autocomplete="tel" required placeholder="+91 XXXXX XXXXX">
                </div>
                <div class="form-group">
                    <label for="register-address">Default delivery address</label>
                    <textarea id="register-address" name="address" autocomplete="street-address" required rows="2" placeholder="Door no, street, area"></textarea>
                </div>
                <div class="auth-form__row">
                    <div class="form-group"><label for="register-city">City</label><input type="text" id="register-city" name="city" autocomplete="address-level2" required placeholder="City"></div>
                    <div class="form-group"><label for="register-pincode">PIN code</label><input type="text" id="register-pincode" name="pincode" autocomplete="postal-code" required inputmode="numeric" placeholder="600001"></div>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" autocomplete="new-password" required placeholder="••••••••" minlength="6">
                </div>
                <div class="form-group">
                    <label for="register-password_confirm">Confirm Password</label>
                    <input type="password" id="register-password_confirm" name="password_confirm" autocomplete="new-password" required placeholder="••••••••" minlength="6">
                </div>
                <div class="form-group form-check">
                    <label class="checkbox-label">
                        <input type="checkbox" name="accept_terms" required>
                        <span>I accept the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a></span>
                    </label>
                </div>
                <?php if (!empty($pendingGoogleEmail)): ?>
                <label class="checkbox-label"><input type="checkbox" name="link_google" value="1"> Enable Google sign-in for <?= e($pendingGoogleEmail) ?></label>
                <?php endif; ?>
                <button class="btn btn-primary btn-block">Create Account</button>
            </form>
            <?php if (!empty($googleAuthEnabled) && empty($pendingGoogleEmail)): ?>
            <a href="/auth/google" class="btn-google">Continue with Google</a>
            <?php endif; ?>
            <div class="auth-footer">
                <p>Already have an account? <a href="/login">Sign in here</a></p>
            </div>
        </div>
    </div>
</div>
