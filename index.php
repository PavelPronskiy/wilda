<?php

/**
 * FOR WEB ONLY
 */

// app\core\Config::$runType = 'web';

ini_set('display_errors', 1);

require_once __DIR__ . '/autoload.php';

use \app\core\Config;
use \app\util\Cache;
use \app\core\Router;
use \app\util\ErrorHandler;
use \app\util\RedisController;

try {
	new Config();
    new RedisController();
	new Cache();
	new Router();
} catch (\Error $e)
{
    ErrorHandler::error($e);
}
catch (\Exception $ex)
{
    ErrorHandler::exception($e);
}