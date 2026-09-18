<?php declare(strict_types = 1);

return [
    'account' => [
        'resetPassword' => [
            'greeting' => '您好 <strong>%s</strong>，',
            'requestPassword' => '已请求设置新密码。',
            'usernameReminder' => '请记住，您的用户名是 <strong>%s</strong>',
            'setPasswordButton' => '设置密码',
            'ignoreNotice' => '如果您没有请求设置新密码，可以忽略此邮件。', 
            'subjectResetPasswordEmail' => '重置密码 %s %s'
        ]
    ],
    'auth' => [
        'resetPassword' => [
            'greeting' => '您好 <strong>%s</strong>，',
            'requestPassword' => '已请求设置新密码。',
            'usernameReminder' => '请记住，您的用户名是 <strong>%s</strong>',
            'setPasswordButton' => '设置密码',
            'ignoreNotice' => '如果您没有请求设置新密码，可以忽略此邮件。', 
            'subjectResetPasswordEmail' => '重置密码 %s %s'
        ],
        '2fa' => [
            'greeting' => '您好 <strong>%s</strong>，',
            'codeNotice' => '您的 2FA 验证码是 <strong>%s</strong>，将在 <strong>%d %s</strong> 内过期',
            'expiryMinute' => '分钟',
            'expiryMinutes' => '分钟', 
            'subjectVerifyCodeEmail' => '您的验证码',
        ]
    ],
    'admins' => [
        'add' => [
            'greeting' => '您好 <strong>%s</strong>，',
            'createdNotice' => '您的新个人资料已创建。',
            'usernameReminder' => '请记住，您的用户名是 <strong>%s</strong>',
            'setPasswordButton' => '设置密码',
            'ignoreNotice' => '如果您没有请求创建此个人资料，可以忽略此邮件。', 
            'subjectCreateAdminEmail' => '创建管理员 %s %s'
        ],
        'resetPassword' => [
            'greeting' => '您好 <strong>%s</strong>，',
            'requestPassword' => '已请求设置新密码。',
            'usernameReminder' => '请记住，您的用户名是 <strong>%s</strong>',
            'setPasswordButton' => '设置密码',
            'ignoreNotice' => '如果您没有请求设置新密码，可以忽略此邮件。', 
            'subjectResetPasswordEmail' => '重置密码 %s %s'
        ]
    ],
    'messages' => [
    	'sendingEmailSuccess' => '邮件已成功发送。',
    	'sendingEmailFailed' => '邮件发送失败。', 
    ]
];
