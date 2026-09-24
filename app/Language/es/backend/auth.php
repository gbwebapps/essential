<?php declare(strict_types = 1);

return [
	'titles' => [
		'index' => 'Bienvenido',
		'login' => 'Iniciar sesión',
		'resetPassword' => 'Restablecer contraseña',
		'setPassword' => 'Establecer contraseña',
		'verify' => 'Verificar OTP'
	],
	'labels' => [
		'email' => 'Correo electrónico',
		'username' => 'Nombre de usuario',
		'password' => 'Contraseña',
		'rememberMe' => 'Recuérdame',
		'newPassword' => 'Introduce la contraseña',
		'confirmNewPassword' => 'Confirma la contraseña',
		'code' => 'Código OTP'
	],
	'placeholders' => [
		'email' => 'Introduce aquí el correo electrónico...',
		'username' => 'Introduce aquí el nombre de usuario...',
		'password' => 'Introduce aquí la contraseña...',
		'newPassword' => 'Introduce aquí la contraseña...',
		'confirmNewPassword' => 'Confirma la contraseña',
		'code' => 'Introduce aquí el código OTP...',
	],
	'buttons' => [
		'login' => 'Iniciar sesión',
		'resetPassword' => 'Restablecer contraseña',
		'setPassword' => 'Establecer contraseña',
		'verify' => 'Verificar OTP'

	],
	'links' => [
		'login' => 'Iniciar sesión',
		'resetPassword' => 'Restablecer contraseña'
	],
	'errors' => [
	    'passwordFormatNotValid' => 'La contraseña no cumple con los requisitos de seguridad.',
	],
	'audits' => [
	    'loginRefused'      => 'Intento de inicio de sesión con cuenta inexistente',
	    'loginBlocked'      => 'Acceso denegado, cuenta bloqueada %s %s',
	    'loginFailed'       => 'Intento de inicio de sesión fallido %s %s',
	    '2faRequired'       => 'Código de verificación 2FA requerido %s %s %s',
	    'loginSuccess'      => 'Inicio de sesión exitoso %s %s',
	    'resetPasswordAuth' => 'Restablecimiento de contraseña %s %s',
	    'setPassword'       => 'Configuración de contraseña %s %s',
	    'verifyFailed'      => 'Intento de inicio de sesión con cuenta inexistente',
	    '2faBlocked'        => 'Bloqueo 2FA %s %s',
	    '2faFailed'         => 'Código 2FA incorrecto o caducado %s %s',
	],
	'messages' => [
		'goodbye' => 'Hasta luego %s %s.',
		'welcome' => 'Buenos días %s %s.',
		'loginFailed' => 'Intento de inicio de sesión fallido.',
	    'loginNeeded' => 'Inicia sesión para acceder.',
	    'resetPasswordFailed' => 'Error durante el restablecimiento de la contraseña.',
		'setPasswordSuccess' => 'Contraseña establecida. <a class="fw-bold text-white" href="' . base_url('backend/auth/login') . '"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión.</a>',
		'setPasswordFailed' => 'Error al establecer la contraseña.',
		'setPasswordError' => 'Error al establecer la contraseña.',
		'checkAuthError' => 'Código de autenticación no válido o caducado.',
		'tooManyAttempts' => 'Se han realizado demasiados intentos.',
	    'currentSessionOn' => 'Sesión de %s %s aún en curso.',
		'validationErrors' => 'Errores de validación.',
		'resetPasswordSuccess' => 'Proceso de restablecimiento de contraseña configurado. Verifica el correo electrónico enviado para finalizar las operaciones.',
		'resetPasswordSuccessNoEmail' => 'Proceso de restablecimiento de contraseña configurado, pero el correo no ha sido enviado. Contacta con el administrador.',
		'expiredCode' => 'Código OTP caducado.',
		'wrongCode' => 'Código OTP erróneo.',
	]
];