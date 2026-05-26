<div class="row">
    <div class="col-4 offset-4">

        <form id="set_password_form">

            <!-- Campo nascosto che trasporta il token dal caricamento pagina (GET) al salvataggio (POST) -->
            <input type="hidden" name="token" value="<?= $token ?>">

            <!-- Campo password -->
            <div class="mb-2">
                <label for="password" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.newPassword'); ?></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="<?= lang('backend/auth.placeholders.newPassword'); ?>">
                <div class="error_password text-danger fw-bold small pt-1">&nbsp;</div>
            </div>

            <!-- Campo conferma password -->
            <div class="mb-2">
                <label for="password_confirm" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.confirmNewPassword'); ?></label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="<?= lang('backend/auth.placeholders.confirmNewPassword'); ?>">
                <div class="error_password_confirm text-danger fw-bold small pt-1">&nbsp;</div>
            </div>

            <!-- Pulsante invio dati -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-floppy-disk"></i><?= lang('backend/auth.buttons.setPassword'); ?></button>
            </div>

        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('backend/auth/login'); ?>" class="pe-3"><i class="fa-solid fa-right-to-bracket"></i><?= lang('backend/auth.links.login'); ?></a>
            <a href="<?= base_url('backend/auth/resetPassword'); ?>" class="ps-3"><i class="fa-solid fa-unlock"></i><?= lang('backend/auth.links.resetPassword'); ?></a>
        </div>

    </div>
</div>