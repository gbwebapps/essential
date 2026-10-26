<div>
    <p>
        <?= ui_text("backend/email.account.resetPassword.greeting", [esc($firstname) . ' ' . esc($lastname)], false); ?>
    </p>
    <p>
        <?= ui_text("backend/email.account.resetPassword.requestPassword", [], false); ?><br>
        <?= ui_text("backend/email.account.resetPassword.usernameReminder", [esc($email)], false); ?>
    </p>
    <p>
        <a href="<?= base_url('backend/auth/setPassword/' . esc($token)); ?>">
            <?= ui_text("backend/email.account.resetPassword.setPasswordButton", [], false); ?>
        </a>
    </p>
    <p>
        <?= ui_text("backend/email.account.resetPassword.ignoreNotice", [], false); ?>
    </p>
</div>
