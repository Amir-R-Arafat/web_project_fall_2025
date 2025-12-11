script.js
// assets/js/script.js

// Toggle main navigation on mobile (requires a .menu-toggle button and .main-nav in header)
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-nav');

    if (toggleBtn && nav) {
        toggleBtn.addEventListener('click', function () {
            nav.classList.toggle('nav-open');
        });
    }

    // Small helper: auto-submit track form when pressing Enter in hero/CTA input
    const trackForms = document.querySelectorAll('.track-form');
    trackForms.forEach(function (form) {
        const input = form.querySelector('input[name="order_id"], input[name="tracking_code"]');
        if (input) {
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    form.submit();
                }
            });
        }
    });

    // Optional: prevent negative qty in any quantity input
    const qtyInputs = document.querySelectorAll('input[type="number"][name="quantity"], input[name^="qty["]');
    qtyInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            const val = parseInt(input.value, 10);
            if (isNaN(val) || val < 1) {
                input.value = 1;
            }
        });
    });

    // Hero image slider (dynamic folder)
    const hero = document.getElementById('hero-slider');
    if (hero) {
        const images = [
            'assets/images/dynamic/hero1.jpg',
            'assets/images/dynamic/hero2.jpg',
            'assets/images/dynamic/hero3.jpg',
            'assets/images/dynamic/hero4.jpg',
            'assets/images/dynamic/hero5.jpg'
        ];

        let idx = 0;

        setInterval(function () {
            idx = (idx + 1) % images.length;
            hero.src = images[idx];
        }, 1500); // 1.5 seconds
    }
});