<?php

/**
 * FOR CLI ONLY
 */

// define(RUN_METHOD, 'cli');
require_once __DIR__ . '/autoload.php';

use \CliArgs\CliArgs as CA;
use app\util\Cache as Cache;
use app\util\Crontab as Crontab;
use app\util\Chromium as Chromium;
use app\util\RedisController as RedisController;
use app\core\Config as Config;

Config::$runType = 'cli';

new Config();
new RedisController();

Config::$cli = new CA([
    'method' => 'm',
    'site' => 's'
]);

if (empty(Config::$cli->getArg('method')))
{
	echo 'Method arg not defined';
	die();
}


switch(Config::$cli->getArg('method'))
{
	case 'autocache':
		Chromium::sendToRevalidateSites();
		break;

	case 'clearcache':
		Cache::sendCleanCacheSite();
		break;

	case 'testcron':
		// new app\util\Cache();
		$crontab = new Crontab();
		$crontab->updateAutocacheJob(10);
		break;
}
// app\util\Crontab::setJobCrontab();
// var_dump($CA->getArg('method'));
// new app\util\Booster();
