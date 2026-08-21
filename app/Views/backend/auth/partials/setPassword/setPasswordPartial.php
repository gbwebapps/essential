<div class="row">
    <!-- col-12 per mobile, col-sm-8 per tablet portrait piccolo, col-md-6 per tablet, col-lg-4 per desktop -->
    <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-4 offset-lg-4">

        <form id="set_password_form">

            <!-- Campo nascosto che trasporta il token dal caricamento pagina (GET) al salvataggio (POST) -->
            <input type="hidden" name="token" value="<?= esc($token); ?>">

            <!-- Campo password -->
            <div class="mb-2">
                <label for="password" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.newPassword'); ?></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="<?= lang('backend/auth.placeholders.newPassword'); ?>">
                <div class="error_password text-danger fw-bold small pt-1">&nbsp;</div>
            </div>
            <!-- End Campo password -->

            <!-- Campo conferma password -->
            <div class="mb-2">
                <label for="password-confirm" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.confirmNewPassword'); ?></label>
                <input type="password" id="password-confirm" name="confirmPassword" class="form-control" placeholder="<?= lang('backend/auth.placeholders.confirmNewPassword'); ?>">
                <div class="error_confirmPassword text-danger fw-bold small pt-1">&nbsp;</div>
            </div>
            <!-- End Campo conferma password -->

            <!-- Pulsante invio dati -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-floppy-disk"></i><?= lang('backend/auth.buttons.setPassword'); ?></button>
            </div>
            <!-- End Pulsante invio dati -->

        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('backend/auth/login'); ?>" class="pe-3"><i class="fa-solid fa-right-to-bracket"></i><?= lang('backend/auth.links.login'); ?></a>
            <a href="<?= base_url('backend/auth/resetPassword'); ?>" class="ps-3"><i class="fa-solid fa-unlock"></i><?= lang('backend/auth.links.resetPassword'); ?></a>
        </div>

    </div>
</div>