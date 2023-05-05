<?php

namespace app\core;

// use app\core\Config;
// use app\core\Tags;
use app\util\Cache;
use app\util\Curl;

class Router
{
	function __construct()
	{
		if (isset(Config::$route->query))
			if (key(Config::$route->query))
				switch (key(Config::$route->query))
				{
					case 'clear': return Cache::clear();
					case 'keys': return Cache::keys();
					case 'cleaner': return Cache::webCacheCleaner();
				}

		// for post submits only
		if (isset(Config::$route->post))
			new Submit;

		// for gets
		return Config::render(
			self::routeGet(
				Curl::preCachedRequest()
			)
		);
	}

	/**
	 * [routeGet description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	private static function routeGet($content)
	{
		switch (Config::$route->path)
		{
			case '/robots.txt':
				return Tags::changeRobotsHost($content);

			default:
				return $content;
		}
	}
}