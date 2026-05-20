(() => {
    const initTooltips = () => {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });
    };

    const improveForms = () => {
        document.querySelectorAll('input, select, textarea').forEach((field) => {
            if (!field.id && field.name) {
                field.id = `field_${field.name.replace(/[^a-zA-Z0-9_-]/g, '_')}`;
            }
            const label = field.closest('.mb-3, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-12')?.querySelector('label');
            if (label && field.id && !label.getAttribute('for')) {
                label.setAttribute('for', field.id);
            }
            if (field.required && !field.getAttribute('aria-required')) {
                field.setAttribute('aria-required', 'true');
            }
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        initTooltips();
        improveForms();
    });
})();
