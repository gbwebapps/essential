<?php
/* Recupero dei dati: priorità alle variabili dirette, fallback su flashdata */
$msgClass = $class ?? $session->getFlashdata('class');
$msgContent = $message ?? $session->getFlashdata('message');
$msgIcon = $message_icon ?? $session->getFlashdata('icon') ?? '';

if ($msgClass && $msgContent): 
?>
    <!-- Sezione messaggi unificata -->
    <div class="row">
        <div class="col-12">
            <div class="lead text-<?= $msgClass; ?> text-center fw-bold" role="alert">
                <?= $msgIcon; ?> <?= $msgContent; ?>
            </div>
        </div>
    </div>
<?php endif; ?>