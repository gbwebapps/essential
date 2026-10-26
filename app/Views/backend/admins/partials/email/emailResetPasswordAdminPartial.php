<div>
    <p>
        <?= ui_text("backend/email.admins.resetPassword.greeting", [esc($firstname) . ' ' . esc($lastname)], false); ?>
    </p>
    <p>
        <?= ui_text("backend/email.admins.resetPassword.requestPassword", [], false); ?><br>
        <?= ui_text("backend/email.admins.resetPassword.usernameReminder", [esc($email)], false); ?>
    </p>
    <p>
        <a href="<?= esc(base_url('backend/auth/setPassword/' . rawurlencode($token)), 'attr'); ?>">
            <?= ui_text("backend/email.admins.resetPassword.setPasswordButton", [], false); ?>
        </a>
    </p>
    <p>
        <?= ui_text("backend/email.admins.resetPassword.ignoreNotice", [], false); ?>
    </p>
</div>
