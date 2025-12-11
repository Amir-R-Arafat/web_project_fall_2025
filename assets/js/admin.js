admin.js
// Small admin helpers: confirm dangerous actions and basic responsive sidebar tweaks
document.addEventListener('DOMContentLoaded', function () {
    // Extra safety for any link with data-confirm attribute
    document.body.addEventListener('click', function (e) {
        const target = e.target.closest('[data-confirm]');
        if (target) {
            const message = target.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        }
    });

    // Optional: you can attach data-confirm instead of inline onclick in PHP
    // Example in PHP:
    // <a href="delete_food.php?id=..." data-confirm="Delete this food item?">Delete</a>

    // Future idea: you can add simple notification auto-hide, etc.
});