/**
 * Calcola i secondi visualizzati dal countdown OTP.
 *
 * @param {'expiry'|'period'} mode Modalità di conteggio
 * @param {number} serverNow Tempo corrente del server in secondi Unix
 * @param {number} target Scadenza assoluta oppure durata del periodo TOTP
 * @returns {number} Secondi interi rimanenti
 */
export function calculateOtpCountdown(mode, serverNow, target) {
    if (!Number.isFinite(serverNow) || !Number.isFinite(target) || target <= 0) {
        return 0;
    }

    if (mode === 'expiry') {
        return Math.max(0, Math.ceil(target - serverNow));
    }

    if (mode === 'period') {
        const elapsed = Math.floor(serverNow) % target;
        return target - elapsed;
    }

    return 0;
}

/**
 * Countdown OTP sincronizzato con il tempo ricevuto dal server.
 */
export class OtpCountdown {
    /**
     * @param {HTMLElement} element Elemento che espone la configurazione tramite data attribute
     */
    constructor(element) {
        this.element = element;
        this.mode = element.dataset.otpCountdownMode || '';
        this.serverTime = Number(element.dataset.serverTime);
        this.target = this.mode === 'expiry'
            ? Number(element.dataset.expiresAt)
            : Number(element.dataset.period);

        this.clientStartedAt = Date.now();
        this.timeoutId = null;
        this.stopped = false;
    }

    /**
     * Avvia il countdown.
     *
     * @returns {OtpCountdown} Istanza corrente
     */
    start() {
        if (this.stopped) return this;

        this.update();
        return this;
    }

    /**
     * Aggiorna il valore usando il tempo server corretto del tempo trascorso nel browser.
     *
     * @returns {void}
     */
    update() {
        if (this.stopped || !this.element.isConnected) {
            this.stop();
            return;
        }

        const elapsedSeconds = (Date.now() - this.clientStartedAt) / 1000;
        const serverNow = this.serverTime + elapsedSeconds;
        const remaining = calculateOtpCountdown(this.mode, serverNow, this.target);

        this.element.textContent = `${remaining}s`;

        if (this.mode === 'expiry' && remaining === 0) {
            this.stop();
            return;
        }

        const serverMilliseconds = serverNow * 1000;
        const delay = Math.max(50, 1010 - (serverMilliseconds % 1000));

        this.timeoutId = window.setTimeout(() => this.update(), delay);
    }

    /**
     * Arresta il countdown e libera il timer pendente.
     *
     * @returns {void}
     */
    stop() {
        this.stopped = true;

        if (this.timeoutId !== null) {
            window.clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    }
}

/**
 * Inizializza il primo countdown OTP presente nel contenitore indicato.
 *
 * @param {ParentNode} root Contenitore nel quale cercare il countdown
 * @returns {OtpCountdown|null} Istanza avviata oppure null se assente
 */
export function initOtpCountdown(root = document) {
    const element = root.querySelector('[data-otp-countdown]');

    if (!(element instanceof HTMLElement)) return null;

    return new OtpCountdown(element).start();
}
