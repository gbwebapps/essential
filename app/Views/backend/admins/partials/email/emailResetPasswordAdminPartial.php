<div>
    <p>
        <?= sprintf(lang("backend/email.admins.resetPassword.greeting"), esc($firstname) . ' ' . esc($lastname)); ?>
    </p>
    <p>
        <?= lang("backend/email.admins.resetPassword.requestPassword"); ?><br>
        <?= sprintf(lang("backend/email.admins.resetPassword.usernameReminder"), esc($email)); ?>
    </p>
    <p>
        <a href="<?= base_url('backend/auth/setPassword/' . esc($token)); ?>">
            <?= lang("backend/email.admins.resetPassword.setPasswordButton"); ?>
        </a>
    </p>
    <p>
        <?= lang("backend/email.admins.resetPassword.ignoreNotice"); ?>
    </p>
</div>
