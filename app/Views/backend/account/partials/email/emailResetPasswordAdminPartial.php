<div>
    <p>
        <?= sprintf(lang("backend/email.account.resetPassword.greeting"), esc($firstname) . ' ' . esc($lastname)); ?>
    </p>
    <p>
        <?= lang("backend/email.account.resetPassword.requestPassword"); ?><br>
        <?= sprintf(lang("backend/email.account.resetPassword.usernameReminder"), esc($email)); ?>
    </p>
    <p>
        <a href="<?= base_url('backend/auth/setPassword/' . esc($token)); ?>">
            <?= lang("backend/email.account.resetPassword.setPasswordButton"); ?>
        </a>
    </p>
    <p>
        <?= lang("backend/email.account.resetPassword.ignoreNotice"); ?>
    </p>
</div>
