<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Welcome', 
		'login' => 'Login', 
		'resetPassword' => 'Reset Password', 
		'setPassword' => 'Set Password', 
		'verify' => 'Verify OTP'
	],
	'labels' => [
		'email' => 'Email', 
		'username' => 'Username', 
		'password' => 'Password', 
		'rememberMe' => 'Remember Me', 
		'newPassword' => 'New Password', 
		'confirmNewPassword' => 'Confirm Password', 
		'code' => 'OTP Code'
	], 
	'placeholders' => [
		'email' => 'Enter email address here...', 
		'username' => 'Enter username here...', 
		'password' => 'Enter password here...', 
		'newPassword' => 'Enter password here...', 
		'confirmNewPassword' => 'Confirm password', 
		'code' => 'Enter OTP code here...', 
	], 
	'buttons' => [
		'login' => 'Login', 
		'resetPassword' => 'Reset Password', 
		'setPassword' => 'Set Password', 
		'verify' => 'Verify OTP'
	], 
	'links' => [
		'login' => 'Login', 
		'resetPassword' => 'Reset Password'
	], 
	'errors' => [
	    'passwordFormatNotValid' => 'The password does not meet the security requirements.',
	],
	'audits' => [
	    'loginRefused'      => 'Login attempt with non-existent account',
	    'loginBlocked'      => 'Access denied, account blocked %s %s',
	    'loginFailed'       => 'Failed login attempt %s %s',
	    '2faRequired'       => '2FA verification code required %s %s %s',
	    'loginSuccess'      => 'Login successful %s %s',
	    'resetPasswordAuth' => 'Password reset %s %s',
	    'setPassword'       => 'Password setting %s %s',
	    'verifyFailed'      => 'Login attempt with non-existent account',
	    '2faBlocked'        => '2FA block %s %s',
	    '2faFailed'         => 'Incorrect or expired 2FA code %s %s',
	],
	'messages' => [
		'goodbye' => 'Goodbye %s %s.', 
		'welcome' => 'Good morning %s %s.', 
		'loginFailed' => 'Login attempt failed.', 
	    'loginNeeded' => 'Please log in to access.', 
	    'resetPasswordFailed' => 'Error during password reset.', 
		'setPasswordSuccess' => 'Password set. <a class="fw-bold text-white" href="' . base_url('backend/auth/login') . '"><i class="fa-solid fa-right-to-bracket"></i> Login.</a>', 
		'setPasswordFailed' => 'Error setting password.', 
		'setPasswordError' => 'Error setting password.', 
		'checkAuthError' => 'Authentication code is invalid or expired.', 
		'tooManyAttempts' => 'Too many attempts have been made.', 
	    'currentSessionOn' => '%s %s session still in progress.', 
		'validationErrors' => 'Validation errors.', 
		'resetPasswordSuccess' => 'Password reset process initiated. Check the sent email to complete the operations.', 
		'resetPasswordSuccessNoEmail' => 'Password reset process initiated, but the email was not sent. Please contact the administrator.', 
		'expiredCode' => 'OTP code expired.',
		'wrongCode' => 'Incorrect OTP code.', 
	]
];