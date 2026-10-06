<?php

declare(strict_types=1);

define('APPPATH', __DIR__ . '/app/');
define('ROOTPATH', __DIR__ . '/');
define('SYSTEMPATH', __DIR__ . '/vendor/codeigniter4/framework/system/');
define('WRITEPATH', __DIR__ . '/writable/');
define('TESTPATH', __DIR__ . '/tests/');
define('FCPATH', __DIR__ . '/public/');
define('ENVIRONMENT', 'testing');

require_once __DIR__ . '/vendor/autoload.php';
require_once SYSTEMPATH . 'Common.php';

require_once SYSTEMPATH . 'Helpers/url_helper.php';
require_once SYSTEMPATH . 'Helpers/cookie_helper.php';