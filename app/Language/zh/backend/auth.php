<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => '欢迎', 
		'login' => '登录', 
		'resetPassword' => '重置密码', 
		'setPassword' => '设置密码', 
		'verify' => '验证 OTP'
	],
	'labels' => [
		'email' => '电子邮件', 
		'username' => '用户名', 
		'password' => '密码', 
		'rememberMe' => '记住我', 
		'newPassword' => '输入密码', 
		'confirmNewPassword' => '确认密码', 
		'code' => 'OTP 验证码'
	], 
	'placeholders' => [
		'email' => '在此输入电子邮件地址...', 
		'username' => '在此输入用户名...', 
		'password' => '在此输入密码...', 
		'newPassword' => '在此输入密码...', 
		'confirmNewPassword' => '确认密码', 
		'code' => '在此输入 OTP 验证码...', 
	], 
	'buttons' => [
		'login' => '登录', 
		'resetPassword' => '重置密码', 
		'setPassword' => '设置密码', 
		'verify' => '验证 OTP'

	], 
	'links' => [
		'login' => '登录', 
		'resetPassword' => '重置密码'
	], 
	'errors' => [
	    'passwordFormatNotValid' => '密码不符合安全要求。',
	],
	'audits' => [
	    'loginRefused'      => '使用不存在的账号登录尝试',
	    'loginBlocked'      => '访问被拒绝，账号已被锁定 %s %s',
	    'loginFailed'       => '登录尝试失败 %s %s',
	    '2faRequired'       => '需要 2FA 验证码 %s %s %s',
	    'loginSuccess'      => '登录成功 %s %s',
	    'resetPasswordAuth' => '重置密码 %s %s',
	    'setPassword'       => '设置密码 %s %s',
	    'verifyFailed'      => '使用不存在的账号登录尝试',
	    '2faBlocked'        => '2FA 锁定 %s %s',
	    '2faFailed'         => '2FA 验证码错误或已过期 %s %s',
	],
	'messages' => [
		'goodbye' => '再见 %s %s。', 
		'welcome' => '早上好 %s %s。', 
		'loginFailed' => '登录尝试失败。', 
	    'loginNeeded' => '请登录以访问。', 
	    'resetPasswordFailed' => '重置密码时出错。', 
		'setPasswordSuccess' => '密码已设置。 <a class="fw-bold text-white" href="' . base_url('backend/auth/login') . '"><i class="fa-solid fa-right-to-bracket"></i> 登录。</a>', 
		'setPasswordFailed' => '设置密码时出错。', 
		'setPasswordError' => '设置密码时出错。', 
		'checkAuthError' => '身份验证码无效或已过期。', 
		'tooManyAttempts' => '尝试次数过多。', 
	    'currentSessionOn' => '%s %s 会话仍在进行中。', 
		'validationErrors' => '验证错误。', 
		'resetPasswordSuccess' => '重置密码流程已设置。请检查发送的电子邮件以完成操作。', 
		'resetPasswordSuccessNoEmail' => '重置密码流程已设置，但电子邮件未发送。请联系管理员。', 
		'expiredCode' => 'OTP 验证码已过期。',
		'wrongCode' => 'OTP 验证码错误。', 
	]
];
