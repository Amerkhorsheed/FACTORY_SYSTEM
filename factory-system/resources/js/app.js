import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Arabic } from 'flatpickr/dist/l10n/ar.js';
import TomSelect from 'tom-select';

window.Alpine = Alpine;
window.TomSelect = TomSelect;

document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
    });
});

window.formatMoney = function (amount, currency = 'SYP') {
    const symbol = { SYP: 'ل.س', USD: '$', EUR: '€' }[currency] || currency;
    return `${new Intl.NumberFormat('ar-SY').format(amount)} ${symbol}`;
};

window.initTomSelect = function (selector = '[data-tom-select]', options = {}) {
    document.querySelectorAll(selector).forEach((element) => {
        if (!element.tomselect) {
            new TomSelect(element, { create: false, maxItems: 1, ...options });
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-datepicker]').forEach((element) => {
        flatpickr(element, { locale: Arabic, dateFormat: 'Y-m-d', allowInput: true });
    });

    window.initTomSelect();

    document.querySelectorAll('[data-flash-dismiss]').forEach((element) => {
        setTimeout(() => element.remove(), 5000);
    });
});

Alpine.start();
