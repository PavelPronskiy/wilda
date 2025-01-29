<?php

/**
 * FOR WEB ONLY
 */

// app\core\Config::$runType = 'web';

ini_set('display_errors', 1);

require_once __DIR__ . '/autoload.php';

try {
	new \app\core\Config();
    new \app\util\RedisController();
	new \app\util\Cache();
	new \app\core\Router();
} catch (\Error $e)
{
    \app\util\ErrorHandler::error($e);
}
catch (\Exception $e)
{
    \app\util\ErrorHandler::exception($e);
}