<div class="row py-2">
    <div class="col-12">

        <div class="row">
            <div class="col-6 offset-3">
                <label for="search-admin"><?= lang('backend/groups.labels.admins'); ?></label>
                
                <div class="position-relative">
                    <div class="input-group">
                        <input type="text" id="search-admin" class="form-control" placeholder="<?= lang('backend/groups.placeholders.admins'); ?>" autocomplete="off">
                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                    </div>
                    
                    <div id="dropdownAdmins" class="w-100 position-absolute start-0 z-3"></div>
                </div>
            </div>
            <div id="admin-permissions-container" class="col-12"></div>
        </div>

    </div>
</div>