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

const saleItems = document.querySelector('#sale-items');
const saleTemplate = document.querySelector('#sale-item-template');
const saleTotal = document.querySelector('#sale-total');

const formatQuantity = (value, unit) => {
    const number = Number(value) || 0;
    const formatted = number.toLocaleString('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });

    return `${formatted} ${unit}`.trim();
};

const updateSaleRowTotal = (row) => {
    if (!row) {
        return;
    }

    const select = row.querySelector('.sale-product-select');
    const option = select?.options[select.selectedIndex];
    const price = Number(option?.dataset.price || 0);
    const quantity = Number(row.querySelector('.sale-quantity')?.value || 0);
    const lineTotal = row.querySelector('.sale-line-total');

    if (lineTotal) {
        lineTotal.textContent = formatCurrency(quantity * price);
    }
};

const updateSaleRow = (row) => {
    if (!row) {
        return;
    }

    const select = row.querySelector('.sale-product-select');
    const option = select?.options[select.selectedIndex];
    const unit = option?.dataset.unit ?? '';
    const price = option?.dataset.price ?? '0';
    const available = option?.dataset.available ?? '0';

    const unitElement = row.querySelector('.sale-unit');
    const priceElement = row.querySelector('.sale-price');
    const availableElement = row.querySelector('.sale-available');

    if (unitElement) {
        unitElement.textContent = unit;
    }

    if (priceElement) {
        priceElement.textContent = formatCurrency(price);
    }

    if (availableElement) {
        availableElement.textContent = formatQuantity(available, unit);
    }

    updateSaleRowTotal(row);
};

const updateSaleTotal = () => {
    if (!saleItems || !saleTotal) {
        return;
    }

    let total = 0;

    saleItems.querySelectorAll('.sale-item-row').forEach((row) => {
        const select = row.querySelector('.sale-product-select');
        const option = select?.options[select.selectedIndex];
        const price = Number(option?.dataset.price || 0);
        const quantity = Number(row.querySelector('.sale-quantity')?.value || 0);
        total += quantity * price;
    });

    saleTotal.textContent = formatCurrency(total);
};

if (saleItems) {
    saleItems.querySelectorAll('.sale-item-row').forEach((row) => {
        updateSaleRow(row);
    });

    updateSaleTotal();

    saleItems.addEventListener('change', (event) => {
        if (event.target.matches('.sale-product-select')) {
            updateSaleRow(event.target.closest('.sale-item-row'));
            updateSaleTotal();
        }
    });

    saleItems.addEventListener('input', (event) => {
        if (event.target.matches('.sale-quantity')) {
            updateSaleRowTotal(event.target.closest('.sale-item-row'));
            updateSaleTotal();
        }
    });

    saleItems.addEventListener('click', (event) => {
        if (!event.target.matches('.remove-sale-row')) {
            return;
        }

        const row = event.target.closest('.sale-item-row');
        const rows = saleItems.querySelectorAll('.sale-item-row');

        if (rows.length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });

            const select = row.querySelector('.sale-product-select');

            if (select) {
                select.value = '';
            }

            updateSaleRow(row);
        }

        updateSaleTotal();
    });
}

document.querySelector('#add-sale-item')?.addEventListener('click', () => {
    if (!saleTemplate || !saleItems) {
        return;
    }

    const fragment = saleTemplate.content.cloneNode(true);
    const row = fragment.querySelector('.sale-item-row');

    saleItems.appendChild(fragment);

    if (row) {
        updateSaleRow(row);
        row.querySelector('.sale-product-select')?.focus();
    }

    updateSaleTotal();
});
