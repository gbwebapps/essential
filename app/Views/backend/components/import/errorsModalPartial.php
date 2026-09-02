<?php
    /* Assicuriamoci che la variabile esista per evitare notice */
    $validationErrors = $validationErrors ?? [];
    $errorCount = count($validationErrors);
?>

<div class="alert alert-light text-danger fw-bold alert-dismissible fade show d-flex flex-column p-3" role="alert">
    <div class="d-flex align-items-center w-100">
        <div class="ms-4">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= sprintf(lang('backend/components/import.messages.validationErrors'), $errorCount); ?>
        </div>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="badge bg-danger p-2 border-0 rounded-1" data-bs-toggle="collapse" data-bs-target="#collapseValidationErrors" aria-expanded="false" aria-controls="collapseValidationErrors">
                <i class="fa-solid fa-chevron-down"></i> <span class="fw-bold"><?= lang('backend/components/import.labels.toggleVisibility') ?></span>
            </button>
            <button type="button" class="badge bg-danger p-2 border-0 rounded-1" data-bs-dismiss="alert" style="cursor: pointer;">
                <i class="fa-solid fa-xmark"></i> <?= lang('backend/components/import.buttons.remove') ?>
            </button>
        </div>
    </div>
    
    <div class="collapse w-100" id="collapseValidationErrors">
        <div class="p-3 bg-white border border-danger rounded mt-3" style="max-height: 400px; overflow-y: auto;">
            <ul class="mb-0 fw-normal small list-unstyled">
                <?php foreach ($validationErrors as $error): ?>
                    <li class="bg-danger text-white p-2 my-2 rounded d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-right"></i>
                        <span><?= esc($error) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>