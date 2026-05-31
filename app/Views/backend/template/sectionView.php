<div class="row align-items-center mb-3">

    <div class="col-12 col-md-4 text-center text-md-start">
        <h1 class="mb-0"><?= $icon; ?> <?= $title; ?></h1>
    </div>

    <div class="col-12 col-md-4 text-center my-2 my-md-0">
        <?php
            $msgClass = $class ?? session()->getFlashdata('class');
            $msgContent = $message ?? session()->getFlashdata('message');
            $msgIcon = $message_icon ?? session()->getFlashdata('icon') ?? '';

            if ($msgClass && $msgContent): 
        ?>
            <span class="lead text-<?= $msgClass; ?> fw-bold" role="alert">
                <?= $msgIcon; ?> <?= $msgContent; ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="col-12 col-md-4 text-center text-md-end">
        <?php if(isset($options)): ?>
            <?= $options; ?>
        <?php endif; ?>
    </div>

</div>