import htmx from 'htmx.org';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './charts/expense-pie-chart.js';
import './viability-calculator.js';

window.htmx = htmx;
window.Alpine = Alpine;
window.Chart = Chart;

// Handle 422 validation errors in HTMX
document.body.addEventListener('htmx:beforeSwap', function(evt) {
    if (evt.detail.xhr.status === 422) {
        evt.detail.shouldSwap = true;
        evt.detail.isError = false;
    }
});

// Close modal after successful form submission
document.body.addEventListener('htmx:afterSwap', function(evt) {
    if (evt.detail.xhr.getResponseHeader('HX-Trigger') === 'closeModal') {
        document.getElementById('modal-container').innerHTML = '';
    }
});

// Handle session expiry
document.body.addEventListener('htmx:responseError', function(evt) {
    if (evt.detail.xhr.status === 419) {
        window.location.href = '/login';
    }
});

Alpine.start();

window.flockModal = function flockModal() {
    return {
        firstFocusable: null,
        lastFocusable:  null,
        open() {
            this.$nextTick(() => {
                const focusable = this.$refs.panel.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                this.firstFocusable = focusable[0];
                this.lastFocusable  = focusable[focusable.length - 1];
                this.firstFocusable?.focus();
                document.body.style.overflow = 'hidden';
            });
        },
        trapFocus(event) {
            if (event.key !== 'Tab') { return; }
            if (event.shiftKey) {
                if (document.activeElement === this.firstFocusable) {
                    event.preventDefault();
                    this.lastFocusable?.focus();
                }
            } else {
                if (document.activeElement === this.lastFocusable) {
                    event.preventDefault();
                    this.firstFocusable?.focus();
                }
            }
        },
        close() {
            document.body.style.overflow = '';
            document.getElementById('modal-container').innerHTML = '';
        },
    };
};
