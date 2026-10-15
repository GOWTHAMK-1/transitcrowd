<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="max-width: 480px; margin: 2rem auto;">
    <div class="card" style="box-shadow: var(--shadow-lg);">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📝</div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary-dark);">Passenger Sign Up</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Create an account to save favorite lines, stations, and personalized predictions.
            </p>
        </div>

        <form action="/register" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" placeholder="Jane Doe" required autocomplete="name">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" placeholder="jane@example.test" required autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label">Password * (Min 6 characters)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                Create Passenger Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="/login" style="color: var(--primary-light); font-weight: 600;">Sign In</a>
        </div>
    </div>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
