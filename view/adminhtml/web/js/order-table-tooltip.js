define(['jquery'], function($) {
    'use strict';

    document.body.addEventListener('click', (e) => {
        let tooltip = e.target.closest('.grid-more-info'),
            allTooltips = Array.from(document.querySelectorAll('.grid-more-info'));

        allTooltips.forEach((item) => { item.classList.remove('show') });

        if (tooltip) {
            e.preventDefault();
            tooltip.classList.add('show');
        }
    });
});
