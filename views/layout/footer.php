<?php
/**
 * Shared Application Footer Layout
 */
?>
    </main>

    <footer class="app-footer">
        <div class="container footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand">
                        <span class="logo-icon">🚍</span>
                        <strong>Public Transport Crowd Predictor</strong>
                    </div>
                    <p class="footer-desc">
                        A full-stack open-source platform providing explainable public transit crowd estimates
                        and predictions powered by PHP 8 and Apache CouchDB.
                    </p>
                    <div class="db-badge">
                        <span class="db-dot"></span> Powered by Apache CouchDB 3.x NoSQL
                    </div>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="/">Home</a></li>
                        <li><a href="/routes">Route & Stop Explorer</a></li>
                        <li><a href="/predictions">Crowd Predictions</a></li>
                        <li><a href="/predictions/compare">Departure Time Comparison</a></li>
                        <li><a href="/reports/submit">Passenger Crowd Reports</a></li>
                        <li><a href="/analytics">Historical Analytics</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Transparency & Rules</h4>
                    <ul class="footer-links">
                        <li><span class="text-muted">Crowd Categories:</span></li>
                        <li><span class="badge badge-low">Low: 0–30%</span></li>
                        <li><span class="badge badge-moderate">Moderate: 31–60%</span></li>
                        <li><span class="badge badge-high">High: 61–80%</span></li>
                        <li><span class="badge badge-very-high">Very High: 81–100%</span></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Open-Source Project</h4>
                    <p class="footer-meta">
                        Designed for college open-source demonstration.<br>
                        <strong>No live vehicle GPS fabricated.</strong><br>
                        Estimates rely on verified historical records and voluntary passenger reports.
                    </p>
                    <div class="footer-auth-links">
                        <a href="/login" class="link-subtle">Admin / Passenger Login</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Public Transport Crowd Predictor. Open Source Mini Project.</p>
                <p>Map tiles &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors.</p>
            </div>
        </div>
    </footer>

    <!-- CSRF Global Token for JS Fetch calls -->
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

    <!-- Main JavaScript Bundle -->
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/charts.js"></script>
    <script src="/assets/js/map.js"></script>
</body>
</html>
