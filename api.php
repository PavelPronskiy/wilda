<?php

/**
 * FOR WEB ONLY
 */

// app\core\Config::$runType = 'web';

ini_set('display_errors', 1);

require_once __DIR__ . '/autoload.php';

use \app\core\Config;
// use \app\util\Encryption as Encryption;
use \app\util\Cache;
use \app\core\Api;
use \app\util\ErrorHandler;
use \app\util\RedisController;

try {

	new Config();
    new RedisController();
	// new Cache();

	new Api();

} catch (\Error $e)
{
    ErrorHandler::error($e);
}
catch (\Exception $ex)
{
    ErrorHandler::exception($ex);
}