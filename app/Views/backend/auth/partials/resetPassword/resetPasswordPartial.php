<div class="row">
    <!-- Aggiunto col-sm-8 e offset-sm-2 per coprire i tablet in portrait sotto i 768px. Su md passa a 6 colonne. -->
    <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-4 offset-lg-4">

        <form id="reset_password_form">

            <!-- Campo email -->
            <div class="mb-2">
                <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.email'); ?></label>
                <input type="text" id="email" name="email" class="form-control" placeholder="<?= lang('backend/auth.placeholders.email'); ?>">
                <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
            </div>
            <!-- End Campo email -->

            <!-- Pulsante invio dati -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-floppy-disk"></i><?= lang('backend/auth.buttons.resetPassword'); ?></button>
            </div>
            <!-- End Pulsante invio dati -->

        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('backend/auth/login'); ?>"><i class="fa-solid fa-right-to-bracket"></i><?= lang('backend/auth.links.login'); ?></a>
        </div>

    </div>
</div>