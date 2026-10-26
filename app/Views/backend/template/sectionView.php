<div class="row align-items-center mb-3">

    <div class="col-12 col-md-4 text-center text-md-start">
        <h1 class="mb-0"><?= $icon; ?> <?= ui_value($title); ?></h1>
    </div>

    <div class="col-12 col-md-4 text-center my-2 my-md-0">
        <?php
            $msgClass = $class ?? session()->getFlashdata('class');
            $msgContent = $message ?? session()->getFlashdata('message');
            $msgIcon = $message_icon ?? session()->getFlashdata('icon') ?? '';

            if ($msgClass && $msgContent): 
        ?>
            <div class="alert <?= esc($msgClass, 'attr'); ?> alert-session alert-dismissible fade show border-0 d-flex align-items-center p-3 mb-0" role="alert">
                <div class="w-100 text-center p-0 ms-4">
                    <?= $msgIcon; ?> <span<?php if( ! isset($message) && session()->getFlashdata('message_translation')): ?> data-i18n-text="<?= esc(json_encode(session()->getFlashdata('message_translation'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'attr'); ?>"<?php endif; ?>><?= esc($msgContent); ?></span>
                </div>
                <!-- Pulsante stile badge per la chiusura dell'alert -->
                <button type="button" class="badge app-bg-danger p-2 border-0 rounded-1 ms-auto" data-bs-dismiss="alert" style="cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/global.buttons.remove', [], false); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-md-4 text-center text-md-end">
        <?php if(isset($options)): ?>
            <?= $options; ?>
        <?php endif; ?>
    </div>

</div>
