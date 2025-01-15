<?php

/**
 * FOR WEB ONLY
 */

// app\core\Config::$runType = 'web';

ini_set('display_errors', 1);

require_once __DIR__ . '/autoload.php';

use \app\core\Config as Config;
// use \app\util\Encryption as Encryption;
use \app\util\Cache as Cache;
use \app\core\Router as Router;
use \app\util\ErrorHandler as ErrorHandler;
use \app\util\RedisController as RedisController;

try {
	new Config();
	// new Encryption();

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