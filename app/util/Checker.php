<?php

namespace app\util;

// namespace Utils;
use app\core\Config;
use app\util\Curl;

class Checker
{
	function __construct()
	{
		self::run();
	}

	public static function run() : void
	{
		$array = [];

		foreach (Config::$config->hosts as $host)
		{
			$get_site = Curl::get($host->site);

			if (!empty($get_site->body))
				echo 'Site work: ' . $host->site . PHP_EOL;
			else
				echo 'Site not work: ' . $host->site . PHP_EOL;

		}
	}
}
