<?php declare(strict_types = 1);

return [
    'account' => [
        'resetPassword' => [
            'greeting' => 'Hi <strong>%s</strong>,',
            'requestPassword' => 'A new password setup has been requested.',
            'usernameReminder' => 'We remind you that your username is <strong>%s</strong>',
            'setPasswordButton' => 'Set password',
            'ignoreNotice' => 'If you did not request a new password setup, you can ignore this email.', 
            'subjectResetPasswordEmail' => 'Password reset %s %s'
        ]
    ],
    'auth' => [
        'resetPassword' => [
            'greeting' => 'Hi <strong>%s</strong>,',
            'requestPassword' => 'A new password setup has been requested.',
            'usernameReminder' => 'We remind you that your username is <strong>%s</strong>',
            'setPasswordButton' => 'Set password',
            'ignoreNotice' => 'If you did not request a new password setup, you can ignore this email.', 
            'subjectResetPasswordEmail' => 'Password reset %s %s'
        ],
        '2fa' => [
            'greeting' => 'Hi <strong>%s</strong>,',
            'codeNotice' => 'Your 2FA code is <strong>%s</strong> and expires in <strong>%d %s</strong>',
            'expiryMinute' => 'minute',
            'expiryMinutes' => 'minutes', 
            'subjectVerifyCodeEmail' => 'Your verification code',
        ]
    ],
    'admins' => [
        'add' => [
            'greeting' => 'Hi <strong>%s</strong>,',
            'createdNotice' => 'Your new profile has been created.',
            'usernameReminder' => 'We remind you that your username is <strong>%s</strong>',
            'setPasswordButton' => 'Set password',
            'ignoreNotice' => 'If you did not request the creation of this profile, you can ignore this email.', 
            'subjectCreateAdminEmail' => 'Administrator creation %s %s'
        ],
        'resetPassword' => [
            'greeting' => 'Hi <strong>%s</strong>,',
            'requestPassword' => 'A new password setup has been requested.',
            'usernameReminder' => 'We remind you that your username is <strong>%s</strong>',
            'setPasswordButton' => 'Set password',
            'ignoreNotice' => 'If you did not request a new password setup, you can ignore this email.', 
            'subjectResetPasswordEmail' => 'Password reset %s %s'
        ]
    ],
    'messages' => [
    	'sendingEmailSuccess' => 'The email was sent successfully.',
    	'sendingEmailFailed' => 'The email was not sent successfully.', 
    ]
];