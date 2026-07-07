<div class="row">
    <div class="col-4 offset-4">

        <form id="verify_form">

            <!-- Campo code -->
            <div class="mb-2">
                <label for="code" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/auth.labels.code'); ?></label>
                <input type="number" id="code" name="code" class="form-control" placeholder="<?= lang('backend/auth.placeholders.code'); ?>">
                <div class="error_code text-danger fw-bold small pt-1">&nbsp;</div>
            </div>
            <!-- End Campo code -->

            <div class="d-flex justify-content-end align-items-center">

                <!-- Pulsante invio dati -->
                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-floppy-disk"></i><?= lang('backend/auth.buttons.verify'); ?></button>
                <!-- End Pulsante invio dati -->

            </div>

        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('backend/auth/login'); ?>"><i class="fa-solid fa-right-to-bracket"></i><?= lang('backend/auth.links.login'); ?></a>
        </div>

    </div>
</div>