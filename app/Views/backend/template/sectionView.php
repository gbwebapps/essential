<div class="row align-items-center mb-3">

    <div class="col-12 col-md-4 text-center text-md-start">
        <h1 class="mb-0"><?= $icon; ?> <?= $title; ?></h1>
    </div>

    <div class="col-12 col-md-4 text-center my-2 my-md-0">
        <?php
            $msgClass = $class ?? session()->getFlashdata('class');
            $msgContent = $message ?? session()->getFlashdata('message');
            $msgIcon = $message_icon ?? session()->getFlashdata('icon') ?? '';

            if ($msgClass) :
                $alertClass = (in_array($msgClass, ['success', 'danger', 'info', 'warning', 'primary', 'secondary'])) ? 'alert-' . $msgClass : 'alert-secondary';
            endif;

            if ($msgClass && $msgContent): 
        ?>
            <div class="alert <?= $alertClass; ?> alert-sessione alert-dismissible fade show border-0 d-flex align-items-center p-3" role="alert">
                <div class="w-100 text-center p-0 ms-4">
                    <?= $msgIcon; ?> <?= $msgContent; ?>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-md-4 text-center text-md-end">
        <?php if(isset($options)): ?>
            <?= $options; ?>
        <?php endif; ?>
    </div>

</div>