<div class="card-body">
    <form id="email-settings" autocomplete="off">
        <div class="row">
            <div class="col-12">
                
                <!-- Mittente -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-address-card me-2"></i><?= lang('backend/settings.labels.sender'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_fromEmail" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.fromEmail'); ?></label>
                        <input type="text" id="email_fromEmail" name="fromEmail" class="form-control shadow-none" value="<?= esc($emailSettings['fromEmail']); ?>" placeholder="<?= lang('backend/settings.placeholders.fromEmail'); ?>">
                        <div class="error_fromEmail text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_fromName" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.fromName'); ?></label>
                        <input type="text" id="email_fromName" name="fromName" class="form-control shadow-none" value="<?= esc($emailSettings['fromName']); ?>" placeholder="<?= lang('backend/settings.placeholders.fromName'); ?>">
                        <div class="error_fromName text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_recipients" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.recipients'); ?></label>
                        <input type="text" id="email_recipients" name="recipients" class="form-control shadow-none" value="<?= esc($emailSettings['recipients']); ?>" placeholder="<?= lang('backend/settings.placeholders.recipients'); ?>">
                        <div class="error_recipients text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Protocollo e connessione -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-circle-nodes me-2"></i><?= lang('backend/settings.labels.protConn'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_protocol" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.protocol'); ?></label>
                        <select id="email_protocol" name="protocol" class="form-select shadow-none">
                            <option value="smtp" <?= $emailSettings['protocol'] === 'smtp' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.smtp'); ?></option>
                            <option value="mail" <?= $emailSettings['protocol'] === 'mail' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.mail'); ?></option>
                            <option value="sendemail" <?= $emailSettings['protocol'] === 'sendemail' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.sendEmail'); ?></option>
                        </select>
                        <div class="error_protocol text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_SMTPHost" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.SMTPHost'); ?></label>
                        <input type="text" id="email_SMTPHost" name="SMTPHost" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPHost']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPHost'); ?>">
                        <div class="error_SMTPHost text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_SMTPPort" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.SMTPPort'); ?></label>
                        <input type="number" id="email_SMTPPort" name="SMTPPort" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPPort']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPPort'); ?>">
                        <div class="error_SMTPPort text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email_SMTPCrypto" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.SMTPCrypto'); ?></label>
                        <select id="email_SMTPCrypto" name="SMTPCrypto" class="form-select shadow-none">
                            <option value="none" <?= $emailSettings['SMTPCrypto'] === 'none' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.cryptoNone'); ?></option>
                            <option value="tls" <?= $emailSettings['SMTPCrypto'] === 'tls' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.cryptoTls'); ?></option>
                            <option value="ssl" <?= $emailSettings['SMTPCrypto'] === 'ssl' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.cryptoSsl'); ?></option>
                        </select>
                        <div class="error_SMTPCrypto text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Autenticazione smtp -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-shield-halved me-2"></i><?= lang('backend/settings.labels.smtpAuth'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_SMTPUser" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.SMTPUser'); ?></label>
                        <input type="text" id="email_SMTPUser" name="SMTPUser" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPUser']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPUser'); ?>">
                        <div class="error_SMTPUser text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_SMTPPass" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.SMTPPass'); ?></label>
                        <input type="text" id="email_SMTPPass" name="SMTPPass" class="form-control shadow-none" value="<?= esc($emailSettings['SMTPPass']); ?>" placeholder="<?= lang('backend/settings.placeholders.SMTPPass'); ?>">
                        <div class="error_SMTPPass text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_SMTPAuthMethod" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.SMTPAuthMethod'); ?></label>
                        <select id="email_SMTPAuthMethod" name="SMTPAuthMethod" class="form-select shadow-none">
                            <option value="LOGIN" <?= $emailSettings['SMTPAuthMethod'] === 'LOGIN' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.methodLogin'); ?></option>
                            <option value="PLAIN" <?= $emailSettings['SMTPAuthMethod'] === 'PLAIN' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.methodPlain'); ?></option>
                        </select>
                        <div class="error_SMTPAuthMethod text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Parametri invio e formato -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-sliders me-2"></i><?= lang('backend/settings.labels.parameterSendFormat'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_mailType" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.mailType'); ?></label>
                        <select id="email_mailType" name="mailType" class="form-select shadow-none">
                            <option value="html" <?= $emailSettings['mailType'] === 'html' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.typeHtml'); ?></option>
                            <option value="text" <?= $emailSettings['mailType'] === 'text' ? 'selected' : ''; ?>><?= lang('backend/settings.labels.typeText'); ?></option>
                        </select>
                        <div class="error_mailType text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_charset" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.charset'); ?></label>
                        <input type="text" id="email_charset" name="charset" class="form-control shadow-none" value="<?= esc($emailSettings['charset']); ?>" placeholder="<?= lang('backend/settings.placeholders.charset'); ?>">
                        <div class="error_charset text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="email_priority" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.priority'); ?></label>
                        <select id="email_priority" name="priority" class="form-select shadow-none">
                            <option value="1" <?= (int) $emailSettings['priority'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.priority1'); ?></option>
                            <option value="3" <?= (int) $emailSettings['priority'] === 3 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.priority3'); ?></option>
                            <option value="5" <?= (int) $emailSettings['priority'] === 5 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.priority5'); ?></option>
                        </select>
                        <div class="error_priority text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>
            </div>

            <!-- Pulsanti di Controllo -->
            <div class="row">
                <div class="col-12 mt-4 text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-email" data-message="Sei sicuro di voler ripristinare i valori dei files?">
                        <i class="fa-solid fa-trash-can me-1"></i><?= lang('backend/settings.buttons.restoreData'); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm text-white me-2 btn-refresh-email">
                        <i class="fa-solid fa-rotate me-1"></i><?= lang('backend/settings.buttons.refreshData'); ?>
                    </button>
                    <button type="submit" class="btn btn-success btn-sm text-white btn-save-email">
                        <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/settings.buttons.sendData'); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>