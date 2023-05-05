<?php

/**
 * FOR WEB ONLY
 */


define('RUN_METHOD', 'web');

require_once __DIR__ . '/autoload.php';

new app\core\Config;
new app\util\Encryption;
new app\util\Cache;
new app\core\Router;
