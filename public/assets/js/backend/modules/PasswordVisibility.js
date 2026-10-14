export class PasswordVisibility {

    constructor() {
        if (PasswordVisibility.instance) {
            return PasswordVisibility.instance;
        }

        PasswordVisibility.instance = this;

        this.eventsBound = false;
        this.destroyed = false;
        this.eventsController = new AbortController();

        this.init();
    }

    init() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('click', e => {
            if (!(e.target instanceof Element)) return;

            const button = e.target.closest('.toggle-password-visibility');
            if (!button) return;

            e.preventDefault();

            const group = button.closest('.input-group');
            if (!group) return;

            const input = group.querySelector('input[type="password"], input[type="text"]');
            const icon = button.querySelector('i');

            if (!input || !icon) return;

            const isVisible = input.type === 'text';

            input.type = isVisible ? 'password' : 'text';

            icon.classList.toggle('fa-eye', isVisible);
            icon.classList.toggle('fa-eye-slash', !isVisible);

            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', isVisible ? 'Mostra password' : 'Nascondi password');

        }, {
            signal: this.eventsController.signal
        });
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (PasswordVisibility.instance === this) {
            PasswordVisibility.instance = null;
        }
    }
}
