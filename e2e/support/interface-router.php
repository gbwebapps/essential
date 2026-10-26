<?php declare(strict_types = 1);

/** Router esclusivamente per l'istanza locale delle prove di presentazione. */
if (PHP_SAPI !== 'cli-server'):
    exit(1);
endif;

if (getenv('database.defaultGroup') !== 'tests'):
    http_response_code(503);
    exit('The interface test server requires the tests database group.');
endif;

foreach (['app.baseURL', 'database.defaultGroup', 'cookie.path', 'cookie.secure', 'cache.handler'] as $key):
    $value = getenv($key);
    if ($value !== false):
        $_ENV[$key] = $_SERVER[$key] = $value;
    endif;
endforeach;

defined('CI_DEBUG') || define('CI_DEBUG', false);
if (($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'):
    $_SERVER['HTTPS'] = 'on';
endif;

$public = realpath(__DIR__ . '/../../public');
$file = realpath($public . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($file !== false && str_starts_with($file, $public . DIRECTORY_SEPARATOR) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php'):
    return false;
endif;

require $public . '/index.php';
