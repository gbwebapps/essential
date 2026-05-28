<div>
    <p>
        <?= sprintf(lang("backend/email.admins.add.greeting"), esc($firstname) . ' ' . esc($lastname)); ?>
    </p>
    <p>
        <?= lang("backend/email.admins.add.createdNotice"); ?><br>
        <?= sprintf(lang("backend/email.admins.add.usernameReminder"), esc($email)); ?>
    </p>
    <p>
        <a href="<?= base_url('backend/auth/setPassword/' . esc($token)); ?>">
            <?= lang("backend/email.admins.add.setPasswordButton"); ?>
        </a>
    </p>
    <p>
        <?= lang("backend/email.auth.resetPassword.ignoreNotice"); ?>
    </p>
</div>