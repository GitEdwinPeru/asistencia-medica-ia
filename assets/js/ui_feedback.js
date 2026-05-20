window.UIFeedback = (() => {
    const hasSwal = () => typeof Swal !== 'undefined';

    const toast = (icon, title, options = {}) => {
        if (!hasSwal()) {
            console[icon === 'error' ? 'error' : 'log'](title);
            return Promise.resolve();
        }

        return Swal.fire({
            icon,
            title,
            toast: true,
            position: options.position || 'top-end',
            timer: options.timer || 2200,
            timerProgressBar: true,
            showConfirmButton: false,
            background: '#ffffff',
            customClass: { popup: 'shadow-sm' },
            ...options
        });
    };

    const modal = (icon, title, text = '', options = {}) => {
        if (!hasSwal()) {
            console[icon === 'error' ? 'error' : 'log'](`${title} ${text}`.trim());
            return Promise.resolve();
        }

        return Swal.fire({
            icon,
            title,
            text,
            confirmButtonColor: options.confirmButtonColor || '#0d6efd',
            ...options
        });
    };

    const confirm = (title, text, options = {}) => {
        if (!hasSwal()) return Promise.resolve({ isConfirmed: window.confirm(`${title}\n${text}`) });

        return Swal.fire({
            icon: options.icon || 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Confirmar',
            cancelButtonText: options.cancelButtonText || 'Cancelar',
            confirmButtonColor: options.confirmButtonColor || '#dc3545',
            reverseButtons: true,
            ...options
        });
    };

    const fromQuery = (map, paramName = 'msg') => {
        const params = new URLSearchParams(window.location.search);
        const key = params.get(paramName);
        if (!key || !map[key]) return;

        const config = map[key];
        toast(config.icon || 'success', config.title || config.message, config);
    };

    return {
        success: (title, options = {}) => toast('success', title, options),
        error: (title, text = '', options = {}) => modal('error', title, text, options),
        warning: (title, text = '', options = {}) => modal('warning', title, text, options),
        info: (title, text = '', options = {}) => modal('info', title, text, options),
        loading: (title = 'Procesando...', text = '') => hasSwal()
            ? Swal.fire({ title, text, allowOutsideClick: false, didOpen: () => Swal.showLoading() })
            : Promise.resolve(),
        close: () => hasSwal() && Swal.close(),
        confirm,
        fromQuery
    };
})();
