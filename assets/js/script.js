// =====================================================================
// Factory Inventory & Production Tracker - shared JS
// CLEAN LIGHT PASTEL THEME pass: no new behaviour was needed here —
// the toast-style flash messages were already implemented in an
// earlier UI-polish pass, and the flat pastel styling for them now
// lives entirely in style.css (.app-toast / .app-toast-container).
// Kept as-is below, with comments, so this file stays in sync with
// the CSS.
// =====================================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- Flash messages -> top-right toasts ----
    // Moves the existing server-rendered .alert elements (from PHP's
    // render_flash_messages()) into a fixed-position container and
    // tags them .app-toast so style.css can slide them in from the
    // top-right with the flat pastel toast look. No PHP or markup
    // changes required — same alert elements, same close button,
    // just relocated + a class added.
    var flashAlerts = document.querySelectorAll('.alert');
    if (flashAlerts.length > 0) {
        var toastContainer = document.createElement('div');
        toastContainer.className = 'app-toast-container';
        document.body.appendChild(toastContainer);

        flashAlerts.forEach(function (alertEl) {
            alertEl.classList.add('app-toast');
            toastContainer.appendChild(alertEl);
        });
    }

    // ---- Auto-dismiss alerts after 5 seconds ----
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            var alert = bootstrap.Alert.getOrCreateInstance(alertEl);
            alert.close();
        }, 5000);
    });

    // ---- Generic "confirm before delete/deactivate" ----
    // For any element with data-confirm="Are you sure...?"
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});
