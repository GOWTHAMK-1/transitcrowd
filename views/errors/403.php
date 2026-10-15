<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="max-width: 600px; margin: 4rem auto; text-align: center;">
    <div style="font-size: 4rem; margin-bottom: 1rem;">⛔</div>
    <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--crowd-high); margin-bottom: 0.5rem;">403 - Access Forbidden</h1>
    <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">
        You do not have administrative permissions to view or modify this resource.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="/" class="btn btn-primary">Return Home</a>
        <a href="/login" class="btn btn-secondary">Sign In as Admin</a>
    </div>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
