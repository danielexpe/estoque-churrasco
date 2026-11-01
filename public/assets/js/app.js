'use strict';

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

const categorySelect = document.querySelector('[data-lots-default-source]');
const lotsCheckbox = document.querySelector('#controls_lots');

if (categorySelect && lotsCheckbox && !lotsCheckbox.disabled) {
    categorySelect.addEventListener('change', () => {
        const option = categorySelect.options[categorySelect.selectedIndex];
        lotsCheckbox.checked = option?.dataset.defaultLots === '1';
    });
}
