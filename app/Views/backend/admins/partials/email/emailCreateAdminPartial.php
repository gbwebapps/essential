<div>
    <p>
        <?= ui_text("backend/email.admins.add.greeting", [esc($firstname) . ' ' . esc($lastname)], false); ?>
    </p>
    <p>
        <?= ui_text("backend/email.admins.add.createdNotice", [], false); ?><br>
        <?= ui_text("backend/email.admins.add.usernameReminder", [esc($email)], false); ?>
    </p>
    <p>
        <a href="<?= esc(base_url('backend/auth/setPassword/' . rawurlencode($token)), 'attr'); ?>">
            <?= ui_text("backend/email.admins.add.setPasswordButton", [], false); ?>
        </a>
    </p>
    <p>
        <?= ui_text("backend/email.auth.resetPassword.ignoreNotice", [], false); ?>
    </p>
</div>
