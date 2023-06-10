<?php

/**
 * FOR CLI ONLY
 */

// define(RUN_METHOD, 'cli');

require_once __DIR__ . '/autoload.php';

app\core\Config::$runType = 'cli';

new app\core\Config();
new app\util\Booster();
