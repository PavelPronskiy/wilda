<?php

namespace Utils;

class CheckProjects
{
	function __construct()
	{
		self::run();
	}

	public static function run() : void
	{
		$array = [];
		$config_json = [];
		$config_user_json = [];

		foreach (\Config\Controller::$config->hosts as $host)
		{
			$get_site = \Curl\Controller::get($host->site);
			
			if (!empty($get_site->body))
			{
				echo 'Site work: ' . $host->site . PHP_EOL;
			} else {
				echo 'Site not work: ' . $host->site . PHP_EOL;

			}
		}
	}
}
