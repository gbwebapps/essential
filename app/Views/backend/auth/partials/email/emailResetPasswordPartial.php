<div>
    <p>
        <?= sprintf(lang("backend/email.auth.resetPassword.greeting"), esc($firstname) . ' ' . esc($lastname)); ?>
    </p>
    <p>
        <?= lang("backend/email.auth.resetPassword.requestPassword"); ?><br>
        <?= sprintf(lang("backend/email.auth.resetPassword.usernameReminder"), esc($email)); ?>
    </p>
    <p>
        <a href="<?= base_url('backend/auth/setPassword/' . esc($token)); ?>">
            <?= lang("backend/email.auth.resetPassword.setPasswordButton"); ?>
        </a>
    </p>
    <p>
        <?= lang("backend/email.auth.resetPassword.ignoreNotice"); ?>
    </p>
</div>
