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
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" required placeholder="+91 XXXXX XXXXX">
                </div>
                <div class="form-group">
                    <label>Default delivery address</label>
                    <textarea name="address" required rows="2" placeholder="Door no, street, area"></textarea>
                </div>
                <div class="auth-form__row">
                    <div class="form-group"><label>City</label><input type="text" name="city" required placeholder="City"></div>
                    <div class="form-group"><label>PIN code</label><input type="text" name="pincode" required inputmode="numeric" placeholder="600001"></div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirm" required placeholder="••••••••" minlength="6">
                </div>
                <div class="form-group form-check">
                    <label class="checkbox-label">
                        <input type="checkbox" name="accept_terms" required>
                        <span>I accept the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a></span>
                    </label>
                </div>
                <button class="btn btn-primary btn-block">Create Account</button>
            </form>
            <div class="auth-footer">
                <p>Already have an account? <a href="/login">Sign in here</a></p>
            </div>
        </div>
    </div>
</div>
