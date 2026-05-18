<!-- Contenitore dell'intestazione della sezione (Titolo e Icona) -->
<div class="row align-items-center mb-3">

    <!-- Colonna del titolo principale -->
    <div class="col">
        <h1 class="mb-0"><?= $icon; ?> <?= $title; ?></h1>
    </div>

    <!-- Sezione opzionale per pulsanti di azione rapida o filtri specifici -->
    <?php if(isset($options)): ?>
        <div class="col-auto pt-2">
            <?= $options; ?>
        </div>
    <?php endif; ?>

</div>