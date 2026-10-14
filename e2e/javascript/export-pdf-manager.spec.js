
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const adminUuid = 'aef9b10c-ebe7-4e55-af1f-19aef22c53c8';
const pageUrl = `/backend/admins/show/${adminUuid}`;
const modulePath = '/assets/js/backend/modules/ExportPdf.js';

async function getManagerState(page) {
    return page.evaluate(async path => {
        const { ExportPdfManager } = await import(path);
        const manager = ExportPdfManager.instance;

        return {
            isGenerating: manager.isGenerating,
            eventsBound: manager.eventsBound,
            destroyed: manager.destroyed
        };
    }, modulePath);
}

async function waitForModalReady(page) {
    const modal = page.locator('#exportPdfModal');

    await expect(modal).toBeVisible();

    /* Attende il completamento della transizione Bootstrap */
    await expect.poll(() => page.evaluate(() => {
        const modalEl = document.getElementById('exportPdfModal');
        const instance = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;

        return instance ? instance._isTransitioning : null;
    })).toBe(false);
}

async function preparePdfWrapper(page) {
    await page.evaluate(async path => {
        const { ExportPdfManager } = await import(path);
        const manager = ExportPdfManager.instance;

        if (!document.getElementById(manager.config.wrapperId)) {
            const wrapper = document.createElement('div');
            wrapper.id = manager.config.wrapperId;
            wrapper.textContent = 'Documento PDF E2E';

            document.body.appendChild(wrapper);
        }
    }, modulePath);
}

async function installPdfMock(page) {
    await page.evaluate(() => {
        window.e2ePdf = {
            calls: [],
            pending: [],
            mode: 'success'
        };

        window.html2pdf = () => {
            const call = {
                options: null,
                wrapperId: null
            };

            window.e2ePdf.calls.push(call);

            return {
                set(options) {
                    call.options = options;
                    return this;
                },

                from(element) {
                    call.wrapperId = element.id;
                    return this;
                },

                save() {
                    if (window.e2ePdf.mode === 'failure') {
                        return Promise.reject(new Error('Errore PDF simulato'));
                    }

                    if (window.e2ePdf.mode === 'pending') {
                        return new Promise((resolve, reject) => {
                            window.e2ePdf.pending.push({
                                resolve,
                                reject
                            });
                        });
                    }

                    return Promise.resolve();
                }
            };
        };
    });
}

test('ExportPdfManager apre la modale, ripristina il form e gestisce il backdrop', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/admins\/show\/[0-9a-f-]{36}\/?$/i);

    const result = await page.evaluate(async path => {
        const { ExportPdfManager } = await import(path);
        const manager = ExportPdfManager.instance;

        return {
            exists: Boolean(manager),
            config: manager?.config
        };
    }, modulePath);

    expect(result.exists).toBe(true);

    const modal = page.locator(`#${result.config.modalId}`);
    const form = page.locator(`#${result.config.formId}`);
    const slider = page.locator(`#${result.config.qualitySliderId}`);
    const label = page.locator(`#${result.config.qualityLabelId}`);
    const backdrop = page.locator('#customBackdrop');

    await expect(modal).toHaveCount(1);
    await expect(form).toHaveCount(1);
    await expect(slider).toHaveCount(1);
    await expect(label).toHaveCount(1);

    const initialQuality = await slider.inputValue();

    /* Modifica lo slider prima dell'apertura */
    await slider.evaluate(element => {
        element.value = '40';
        element.dispatchEvent(new Event('input', {
            bubbles: true
        }));
    });

    await expect(label).toHaveText('40%');

    /* Apertura tramite il pulsante globale */
    await page.locator(`#${result.config.buttonId}`).click();

    await waitForModalReady(page);
    await expect(backdrop).toHaveClass(/\bactive\b/);

    /* L'apertura deve ripristinare il form */
    await expect(slider).toHaveValue(initialQuality);
    await expect(label).toHaveText(`${initialQuality}%`);

    /* Chiusura dopo il completamento della transizione */
    await page.evaluate(modalId => {
        const element = document.getElementById(modalId);
        bootstrap.Modal.getInstance(element).hide();
    }, result.config.modalId);

    await expect(modal).toBeHidden();
    await expect(backdrop).not.toHaveClass(/\bactive\b/);

    expect(await getManagerState(page)).toEqual({
        isGenerating: false,
        eventsBound: true,
        destroyed: false
    });
});

test('ExportPdfManager genera il PDF con le opzioni corrette e impedisce duplicazioni', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/admins\/show\/[0-9a-f-]{36}\/?$/i);

    await installPdfMock(page);
    await preparePdfWrapper(page);

    await page.evaluate(() => {
        window.e2ePdf.mode = 'pending';
    });

    const modal = page.locator('#exportPdfModal');
    const form = page.locator('#exportPdfForm');

    await page.locator('#show-export-button').click();

    /* Bootstrap deve completare l'apertura prima della generazione */
    await waitForModalReady(page);

    await form.locator('#pdf_format').selectOption('a4');
    await form.locator('input[name="pdf_orientation"]').first().check();

    await form.locator('#pdf_margin_top').fill('12');
    await form.locator('#pdf_margin_right').fill('14');
    await form.locator('#pdf_margin_bottom').fill('16');
    await form.locator('#pdf_margin_left').fill('18');

    await form.locator('#pdf_quality').evaluate(element => {
        element.value = '70';
        element.dispatchEvent(new Event('input', {
            bubbles: true
        }));
    });

    const orientation = await form.locator('input[name="pdf_orientation"]:checked').inputValue();

    /* Prima generazione: Promise intenzionalmente sospesa */
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => getManagerState(page)).toEqual({
        isGenerating: true,
        eventsBound: true,
        destroyed: false
    });

    /* Secondo submit: non deve avviare un nuovo PDF */
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    const pdfState = await page.evaluate(() => ({
        calls: window.e2ePdf.calls,
        pendingCount: window.e2ePdf.pending.length
    }));

    expect(pdfState.calls).toHaveLength(1);
    expect(pdfState.pendingCount).toBe(1);

    expect(pdfState.calls[0]).toEqual({
        wrapperId: 'show-wrapper',
        options: {
            margin: [12, 14, 16, 18],
            filename: 'export_document.pdf',
            image: {
                type: 'jpeg',
                quality: 0.7
            },
            html2canvas: {
                scale: 2
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation
            }
        }
    });

    /* Durante la generazione la modale rimane aperta */
    await expect(modal).toBeVisible();

    /* Completa la generazione simulata */
    await page.evaluate(() => {
        window.e2ePdf.pending.shift().resolve();
    });

    await expect.poll(() => getManagerState(page)).toEqual({
        isGenerating: false,
        eventsBound: true,
        destroyed: false
    });

    /* La chiusura deve avvenire automaticamente */
    await expect(modal).toBeHidden();
});

test('ExportPdfManager gestisce errori, consente un nuovo tentativo e libera le risorse', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/admins\/show\/[0-9a-f-]{36}\/?$/i);

    await installPdfMock(page);
    await preparePdfWrapper(page);

    await page.evaluate(() => {
        window.e2ePdf.mode = 'failure';
    });

    const modal = page.locator('#exportPdfModal');
    const form = page.locator('#exportPdfForm');

    await page.locator('#show-export-button').click();

    /* Evita submit durante la transizione Bootstrap */
    await waitForModalReady(page);

    /* Prima generazione: fallimento simulato */
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => page.evaluate(() => {
        return window.e2ePdf.calls.length;
    })).toBe(1);

    await expect.poll(() => getManagerState(page)).toEqual({
        isGenerating: false,
        eventsBound: true,
        destroyed: false
    });

    /* Un errore non deve chiudere la modale */
    await expect(modal).toBeVisible();

    /* Secondo tentativo: generazione riuscita */
    await page.evaluate(() => {
        window.e2ePdf.mode = 'success';
    });

    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => page.evaluate(() => {
        return window.e2ePdf.calls.length;
    })).toBe(2);

    await expect.poll(() => getManagerState(page)).toEqual({
        isGenerating: false,
        eventsBound: true,
        destroyed: false
    });

    await expect(modal).toBeHidden();

    /* Distruzione esplicita del manager */
    const destroyedState = await page.evaluate(async path => {
        const { ExportPdfManager } = await import(path);
        const manager = ExportPdfManager.instance;

        manager.destroy();

        return {
            destroyed: manager.destroyed,
            eventsBound: manager.eventsBound,
            singletonCleared: ExportPdfManager.instance === null,
            modalInstanceCleared: manager.modalInstance === null
        };
    }, modulePath);

    expect(destroyedState).toEqual({
        destroyed: true,
        eventsBound: false,
        singletonCleared: true,
        modalInstanceCleared: true
    });

    /* Dopo destroy() il vecchio listener non deve generare PDF */
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    const finalCalls = await page.evaluate(() => window.e2ePdf.calls.length);

    expect(finalCalls).toBe(2);
});
