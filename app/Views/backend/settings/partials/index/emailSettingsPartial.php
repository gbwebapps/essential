<div class="card-body">

    <!-- Indicatore della sorgente dati -->
    <div class="ps-2 py-1 app-bg-surface fw-bold small mb-3 border text-center rounded-2">
        <?php if (isset($isFromDatabase) && $isFromDatabase): ?>
            <span class="app-text-accent">
                <i class="fa-solid fa-database me-1"></i> <?= ui_text('backend/settings.messages.sourceDatabase', [], false); ?>
            </span>
        <?php else: ?>
            <span class="app-text">
                <i class="fa-solid fa-file-code me-1"></i> <?= ui_text('backend/settings.messages.sourceConfigFile', [], false); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <form id="email-settings" autocomplete="off" novalidate data-unsaved-check>
        <div class="row">
            <div class="col-12">
                
                <!-- Mittente -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-address-card me-2"></i><?= ui_text('backend/settings.labels.sender', [], false); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_fromEmail" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.fromEmail', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.fromEmail', 'placeholder', [], false); ?> type="text" id="email_fromEmail" name="fromEmail" class="form-control shadow-none" value="<?= esc($emailSettings['fromEmail']); ?>" placeholder="<?= lang('backend/settings.placeholders.fromEmail'); ?>">
                        <div class="error_fromEmail app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_fromName" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.fromName', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.fromName', 'placeholder', [], false); ?> type="text" id="email_fromName" name="fromName" class="form-control shadow-none" value="<?= esc($emailSettings['fromName']); ?>" placeholder="<?= lang('backend/settings.placeholders.fromName'); ?>">
                        <div class="error_fromName app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_recipients" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.recipients', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.recipients', 'placeholder', [], false); ?> type="text" id="email_recipients" name="recipients" class="form-control shadow-none" value="<?= esc($emailSettings['recipients']); ?>" placeholder="<?= lang('backend/settings.placeholders.recipients'); ?>">
                        <div class="error_recipients app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Protocollo e connessione -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-circle-nodes me-2"></i><?= ui_text('backend/settings.labels.protConn', [], false); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_protocol" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.protocol', [], false); ?></label>
                        <select id="email_protocol" name="protocol" class="form-select shadow-none">
                            <option <?= ui_binding('backend/settings.labels.smtp', 'text', [], false); ?> value="smtp" <?= $emailSettings['protocol'] === 'smtp' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.smtp'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.mail', 'text', [], false); ?> value="mail" <?= $emailSettings['protocol'] === 'mail' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.mail'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.sendEmail', 'text', [], false); ?> value="sendmail" <?= $emailSettings['protocol'] === 'sendmail' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.sendEmail'); ?></option>
                        </select>
                        <div class="error_protocol app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_SMTPHost" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.SMTPHost', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.SMTPHost', 'placeholder', [], false); ?> type="text" id="email_SMTPHost" name="SMTPHost" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPHost']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPHost'); ?>">
                        <div class="error_SMTPHost app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_SMTPPort" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.SMTPPort', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.SMTPPort', 'placeholder', [], false); ?> type="number" id="email_SMTPPort" name="SMTPPort" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPPort']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPPort'); ?>">
                        <div class="error_SMTPPort app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_SMTPCrypto" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.SMTPCrypto', [], false); ?></label>
                        <select id="email_SMTPCrypto" name="SMTPCrypto" class="form-select shadow-none">
                            <option <?= ui_binding('backend/settings.labels.cryptoNone', 'text', [], false); ?> value="none" <?= $emailSettings['SMTPCrypto'] === 'none' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.cryptoNone'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.cryptoTls', 'text', [], false); ?> value="tls" <?= $emailSettings['SMTPCrypto'] === 'tls' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.cryptoTls'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.cryptoSsl', 'text', [], false); ?> value="ssl" <?= $emailSettings['SMTPCrypto'] === 'ssl' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.cryptoSsl'); ?></option>
                        </select>
                        <div class="error_SMTPCrypto app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Autenticazione smtp -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-shield-halved me-2"></i><?= ui_text('backend/settings.labels.smtpAuth', [], false); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_SMTPUser" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.SMTPUser', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.SMTPUser', 'placeholder', [], false); ?> type="text" id="email_SMTPUser" name="SMTPUser" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPUser']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPUser'); ?>">
                        <div class="error_SMTPUser app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_SMTPPass" class="form-label mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.SMTPPass', [], false); ?>
                        </label>
                        <div class="input-group">
                            <input <?= ui_binding('backend/settings.placeholders.SMTPPass', 'placeholder', [], false); ?> type="password"
                                   id="email_SMTPPass"
                                   name="SMTPPass"
                                   class="form-control shadow-none"
                                   value="<?= esc($emailSettings['SMTPPass']); ?>"
                                   placeholder="<?= lang('backend/settings.placeholders.SMTPPass'); ?>">
                            <button class="btn app-btn-secondary app-bg-surface-elevated border toggle-password-visibility"
                                    type="button"
                                    aria-label="Mostra password"
                                    aria-pressed="false">
                                <i class="fa-solid fa-eye app-text-muted"></i>
                            </button>
                        </div>
                        <div class="error_SMTPPass app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_SMTPAuthMethod" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.SMTPAuthMethod', [], false); ?></label>
                        <select id="email_SMTPAuthMethod" name="SMTPAuthMethod" class="form-select shadow-none">
                            <option <?= ui_binding('backend/settings.labels.methodLogin', 'text', [], false); ?> value="LOGIN" <?= $emailSettings['SMTPAuthMethod'] === 'LOGIN' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.methodLogin'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.methodPlain', 'text', [], false); ?> value="PLAIN" <?= $emailSettings['SMTPAuthMethod'] === 'PLAIN' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.methodPlain'); ?></option>
                        </select>
                        <div class="error_SMTPAuthMethod app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Parametri invio e formato -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-sliders me-2"></i><?= ui_text('backend/settings.labels.parameterSendFormat', [], false); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_mailType" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.mailType', [], false); ?></label>
                        <select id="email_mailType" name="mailType" class="form-select shadow-none">
                            <option <?= ui_binding('backend/settings.labels.typeHtml', 'text', [], false); ?> value="html" <?= $emailSettings['mailType'] === 'html' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.typeHtml'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.typeText', 'text', [], false); ?> value="text" <?= $emailSettings['mailType'] === 'text' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.typeText'); ?></option>
                        </select>
                        <div class="error_mailType app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_charset" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.charset', [], false); ?></label>
                        <input <?= ui_binding('backend/settings.placeholders.charset', 'placeholder', [], false); ?> type="text" id="email_charset" name="charset" class="form-control shadow-none" value="<?= esc($emailSettings['charset']); ?>" placeholder="<?= lang('backend/settings.placeholders.charset'); ?>">
                        <div class="error_charset app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_priority" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/settings.labels.priority', [], false); ?></label>
                        <select id="email_priority" name="priority" class="form-select shadow-none">
                            <option <?= ui_binding('backend/settings.labels.priority1', 'text', [], false); ?> value="1" <?= (int) $emailSettings['priority'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.priority1'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.priority3', 'text', [], false); ?> value="3" <?= (int) $emailSettings['priority'] === 3 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.priority3'); ?></option>
                            <option <?= ui_binding('backend/settings.labels.priority5', 'text', [], false); ?> value="5" <?= (int) $emailSettings['priority'] === 5 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.priority5'); ?></option>
                        </select>
                        <div class="error_priority app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Pulsanti di Controllo -->
                <div class="row">
                    <div class="col-12">
                        <!-- Flexbox: In colonna su mobile, in riga da tablet in poi, centrato orizzontalmente -->
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            
                            <button <?= ui_binding('backend/settings.messages.areYouSureDeleteSettings', 'data-message', [], false); ?> type="button" class="btn app-btn-danger btn-sm btn-delete-email" data-message="<?= lang('backend/settings.messages.areYouSureDeleteSettings'); ?>">
                                <i class="fa-solid fa-trash-can me-1"></i><?= ui_text('backend/settings.buttons.restoreData', [], false); ?>
                            </button>
                            
                            <button <?= ui_binding('backend/settings.messages.areYouSureRefreshSettings', 'data-message', [], false); ?> type="button" class="btn app-btn-warning btn-sm btn-refresh-email" data-message="<?= lang('backend/settings.messages.areYouSureRefreshSettings'); ?>">
                                <i class="fa-solid fa-rotate me-1"></i><?= ui_text('backend/settings.buttons.refreshData', [], false); ?>
                            </button>
                            
                            <button type="submit" class="btn app-btn-success btn-sm btn-save-email">
                                <i class="fa-solid fa-floppy-disk me-1"></i><?= ui_text('backend/settings.buttons.sendData', [], false); ?>
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
