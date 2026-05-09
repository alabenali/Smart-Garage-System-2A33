(function () {
    'use strict';

    function applyAnimations() {
        document.body.classList.add('ui-ready');
        var targets = document.querySelectorAll('.card, .sg-table-wrap, .vehicle-card, .sg-form-wrap, .admin-chat-shell, .client-chat-shell');
        targets.forEach(function (el, index) {
            el.classList.add('animate-in');
            el.style.animationDelay = Math.min(index * 40, 240) + 'ms';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyAnimations);
    } else {
        applyAnimations();
    }
})();
