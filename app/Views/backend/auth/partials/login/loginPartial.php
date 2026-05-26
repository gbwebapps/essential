<div class="row">
    <div class="col-4 offset-4">

        <form id="login_form">

            <!-- Campo email -->
            <div class="mb-2">
                <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.email'); ?></label>
                <input type="text" id="email" name="email" value="gbwebapps@gmail.com" class="form-control" placeholder="<?= lang('backend/auth.placeholders.email'); ?>">
                <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
            </div>

            <!-- Campo password -->
            <div class="mb-2">
                <label for="password" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.password'); ?></label>
                <input type="password" id="password" name="password" value="19_DPhrvmlapdf_83" class="form-control" placeholder="<?= lang('backend/auth.placeholders.password'); ?>">
                <div class="error_password text-danger fw-bold small pt-1">&nbsp;</div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <!-- Checkbox remember me -->
                <div class="form-check text-start my-3">
                    <input class="form-check-input" type="checkbox" name="rememberMe" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        <?= lang('backend/auth.labels.rememberMe'); ?>
                    </label>
                </div>
                <!-- Pulsante invio dati -->
                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-floppy-disk"></i><?= lang('backend/auth.buttons.login'); ?></button>
            </div>

        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('backend/auth/resetPassword'); ?>"><i class="fa-solid fa-unlock"></i><?= lang('backend/auth.links.resetPassword'); ?></a>
        </div>

    </div>
</div>