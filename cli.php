<?php

/**
 * FOR CLI ONLY
 */


define('RUN_METHOD', 'cli');

require_once __DIR__ . '/autoload.php';

new app\core\Config;
new app\util\Checker;
