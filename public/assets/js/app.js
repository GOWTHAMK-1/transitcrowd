/**
 * Public Transport Crowd Predictor - Core Application Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Navigation Toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // 2. User Menu Dropdown Toggle
    const userBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userMenuDropdown');
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            userDropdown.classList.remove('show');
        });
    }

    // 3. Dynamic Stop Population when Route changes (in Prediction & Report forms)
    const routeSelect = document.getElementById('routeSelect');
    const stopSelect = document.getElementById('stopSelect');

    if (routeSelect && stopSelect) {
        routeSelect.addEventListener('change', async () => {
            const selectedOption = routeSelect.options[routeSelect.selectedIndex];
            const stopIdsAttr = selectedOption.getAttribute('data-stops');
            
            if (stopIdsAttr) {
                const stopIds = JSON.parse(stopIdsAttr);
                Array.from(stopSelect.options).forEach(opt => {
                    if (opt.value === '') return;
                    if (stopIds.includes(opt.value)) {
                        opt.style.display = 'block';
                    } else {
                        opt.style.display = 'none';
                    }
                });

                // Select first visible stop
                const firstVisible = Array.from(stopSelect.options).find(opt => opt.style.display !== 'none' && opt.value !== '');
                if (firstVisible) {
                    stopSelect.value = firstVisible.value;
                }
            }
        });
    }

    // 4. AJAX Favorite Route Toggle
    document.querySelectorAll('.btn-fav-route').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const routeId = btn.getAttribute('data-route-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch('/api/favorites/route', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({ route_id: routeId, csrf_token: csrfToken }),
                });

                const data = await response.json();
                if (data.success) {
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        btn.innerHTML = '★ Favorited';
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = '☆ Add to Favorites';
                    }
                } else {
                    alert(data.message || 'Please log in to save favorites.');
                }
            } catch (err) {
                console.error('Error toggling favorite:', err);
            }
        });
    });

    // 5. AJAX Favorite Stop Toggle
    document.querySelectorAll('.btn-fav-stop').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const stopId = btn.getAttribute('data-stop-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch('/api/favorites/stop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({ stop_id: stopId, csrf_token: csrfToken }),
                });

                const data = await response.json();
                if (data.success) {
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        btn.innerHTML = '★ Favorited';
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = '☆ Add to Favorites';
                    }
                } else {
                    alert(data.message || 'Please log in to save favorites.');
                }
            } catch (err) {
                console.error('Error toggling favorite:', err);
            }
        });
    });
});
