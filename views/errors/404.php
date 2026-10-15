<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="max-width: 600px; margin: 4rem auto; text-align: center;">
    <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
    <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.5rem;">404 - Not Found</h1>
    <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">
        The requested transport route, stop, or page could not be located in the system.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="/" class="btn btn-primary">Return Home</a>
        <a href="/routes" class="btn btn-secondary">Explore Routes</a>
    </div>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
