<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="index-auth-container">
                <?= $this->include('backend/auth/partials/index/indexPartial'); ?>
            </div>
        </div>
    </div>

    <?php

        /* Verifichiamo se siamo atterrati qui a seguito di un logout */
        if (session()->getFlashdata('isLogout')): 
    ?>
        <script>

            /* Pulizia totale e profonda del Local Storage client */
            localStorage.clear();

        </script>
    <?php

        /* Vaporizza la vecchia sessione con i dati dell'utente loggato */
        session()->destroy();

        endif; 
    ?>
    
<?= $this->endSection() ?>