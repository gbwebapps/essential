<div class="row">
    <div class="col-4 offset-4">

        <form id="reset_password_form">
            <?= csrf_field() ?>

            <!-- Campo email -->
            <div class="mb-2">
                <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.email'); ?></label>
                <input type="text" id="email" name="email" class="form-control" placeholder="<?= lang('backend/auth.placeholders.email'); ?>">
                <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
            </div>

            <!-- Pulsante invio dati -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-floppy-disk"></i><?= lang('backend/auth.buttons.reset_password'); ?></button>
            </div>

        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('backend/auth/login'); ?>"><i class="fa-solid fa-right-to-bracket"></i><?= lang('backend/auth.links.login'); ?></a>
        </div>

    </div>
</div>