<!-- Definizioni di lingua globali -->
<div <?= ui_binding('backend/global.buttons.remove', 'data-remove', [], true); ?> <?= ui_binding('backend/global.labels.select', 'data-select', [], true); ?> <?= ui_binding('backend/global.messages.unsavedChanges', 'data-unsaved-changes', [], true); ?> id="lang-definitions" class="d-none"
    <?= ui_binding('backend/global.interface.showPassword', 'data-show-password'); ?> data-show-password="<?= esc(lang('backend/global.interface.showPassword'), 'attr'); ?>"
    <?= ui_binding('backend/global.interface.hidePassword', 'data-hide-password'); ?> data-hide-password="<?= esc(lang('backend/global.interface.hidePassword'), 'attr'); ?>"
    <?= ui_binding('backend/global.interface.changeError', 'data-interface-error'); ?> data-interface-error="<?= esc(lang('backend/global.interface.changeError'), 'attr'); ?>"
    data-remove="<?= esc(lang('backend/global.buttons.remove')); ?>"
    data-select="<?= esc(lang('backend/global.labels.select')); ?>"
    data-unsaved-changes="<?= esc(lang('backend/global.messages.unsavedChanges')); ?>">
</div>

<!-- Localizzazione errori JS -->
<div <?= ui_binding('backend/global.errors.err403', 'data-err403', [], true); ?> <?= ui_binding('backend/global.errors.err404', 'data-err404', [], true); ?> <?= ui_binding('backend/global.errors.err500', 'data-err500', [], true); ?> <?= ui_binding('backend/global.errors.err504', 'data-err504', [], true); ?> <?= ui_binding('backend/global.errors.errNetwork', 'data-err-network', [], true); ?> id="js-i18n-errors" class="d-none"
    data-err403="<?= esc(lang('backend/global.errors.err403')); ?>"
    data-err404="<?= esc(lang('backend/global.errors.err404')); ?>"
    data-err500="<?= esc(lang('backend/global.errors.err500')); ?>"
    data-err504="<?= esc(lang('backend/global.errors.err504')); ?>"
    data-err-network="<?= esc(lang('backend/global.errors.errNetwork')); ?>">
</div>
