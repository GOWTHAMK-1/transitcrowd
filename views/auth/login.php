<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="max-width: 480px; margin: 2rem auto;">
    <div class="card" style="box-shadow: var(--shadow-lg);">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔐</div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary-dark);">Sign In</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Access passenger favorites or administrator control panel.
            </p>
        </div>

        <form action="/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="loginEmail" class="form-control" placeholder="name@example.test" required autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                Sign In
            </button>
        </form>

        <!-- Quick Demo Credentials for College Review -->
        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed var(--border-color); text-align: center;">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; text-transform: uppercase;">
                College Demo Quick-Fill Credentials
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-outline" style="color: var(--primary-dark); border-color: var(--border-color);" onclick="fillCreds('admin@example.test', 'Admin@123')">
                    🔑 Fill Admin (admin@example.test)
                </button>
                <button type="button" class="btn btn-sm btn-outline" style="color: var(--primary-dark); border-color: var(--border-color);" onclick="fillCreds('passenger@example.test', 'Passenger@123')">
                    👤 Fill Passenger (passenger@example.test)
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account yet? <a href="/register" style="color: var(--primary-light); font-weight: 600;">Register as Passenger</a>
        </div>
    </div>
</div>

<script>
function fillCreds(email, password) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = password;
}
</script>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
