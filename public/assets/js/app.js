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

const purchaseItems = document.querySelector('#purchase-items');
const purchaseTemplate = document.querySelector('#purchase-item-template');
const purchaseTotal = document.querySelector('#purchase-total');

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const formatCurrency = (value) => currencyFormatter.format(Number(value) || 0);

const updateRowTotal = (row) => {
    if (!row) {
        return;
    }

    const quantity = Number(row.querySelector('.quantity-input')?.value || 0);
    const unitCost = Number(row.querySelector('.cost-input')?.value || 0);
    const lineTotal = row.querySelector('.line-total');

    if (lineTotal) {
        lineTotal.textContent = formatCurrency(quantity * unitCost);
    }
};

const updatePurchaseTotal = () => {
    if (!purchaseItems || !purchaseTotal) {
        return;
    }

    let total = 0;

    purchaseItems.querySelectorAll('.purchase-item-row').forEach((row) => {
        const quantity = Number(row.querySelector('.quantity-input')?.value || 0);
        const unitCost = Number(row.querySelector('.cost-input')?.value || 0);
        total += quantity * unitCost;
    });

    purchaseTotal.textContent = formatCurrency(total);
};

const updatePurchaseRow = (row) => {
    if (!row) {
        return;
    }

    const productSelect = row.querySelector('.product-select');
    const option = productSelect?.options[productSelect.selectedIndex];
    const controlsLots = option?.dataset.controlsLots === '1';
    const lotCode = row.querySelector('.lot-code');
    const expiresAt = row.querySelector('.lot-expiry');
    const unitLabel = row.querySelector('.unit-label');

    if (unitLabel) {
        unitLabel.textContent = option?.dataset.unit ?? '';
    }

    if (lotCode && expiresAt) {
        lotCode.disabled = !controlsLots;
        expiresAt.disabled = !controlsLots;
        lotCode.required = controlsLots;
        expiresAt.required = controlsLots;

        if (!controlsLots) {
            lotCode.value = '';
            expiresAt.value = '';
        }
    }

    updateRowTotal(row);
};

if (purchaseItems) {
    purchaseItems.querySelectorAll('.purchase-item-row').forEach((row) => {
        updatePurchaseRow(row);
    });

    updatePurchaseTotal();

    purchaseItems.addEventListener('change', (event) => {
        if (event.target.matches('.product-select')) {
            updatePurchaseRow(event.target.closest('.purchase-item-row'));
        }
    });

    purchaseItems.addEventListener('input', (event) => {
        if (event.target.matches('.quantity-input, .cost-input')) {
            updateRowTotal(event.target.closest('.purchase-item-row'));
            updatePurchaseTotal();
        }
    });

    purchaseItems.addEventListener('click', (event) => {
        if (!event.target.matches('.remove-row')) {
            return;
        }

        const row = event.target.closest('.purchase-item-row');
        const rows = purchaseItems.querySelectorAll('.purchase-item-row');

        if (rows.length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });

            const select = row.querySelector('.product-select');

            if (select) {
                select.value = '';
            }

            updatePurchaseRow(row);
        }

        updatePurchaseTotal();
    });
}

document.querySelector('#add-item')?.addEventListener('click', () => {
    if (!purchaseTemplate || !purchaseItems) {
        return;
    }

    const fragment = purchaseTemplate.content.cloneNode(true);
    const row = fragment.querySelector('.purchase-item-row');

    purchaseItems.appendChild(fragment);

    if (row) {
        updatePurchaseRow(row);
        row.querySelector('.product-select')?.focus();
    }

    updatePurchaseTotal();
});
