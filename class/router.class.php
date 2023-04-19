<?php

namespace Router;

class Controller
{
	function __construct()
	{
		if (isset(\Config\Controller::$route->query))
		{
			$cacheController = new \Cache\Controller;
			$query = \Config\Controller::$route->query->{key(\Config\Controller::$route->query)};
		
			switch ($query)
			{
				case 'flush': return $cacheController->flush($query);
				case 'keys': return $cacheController->keys($query);
			}
		}

		return \Config\Controller::render(
			self::route(
				\Curl\Controller::rget()
			)
		);
	}

	private static function route($content)
	{
		switch (\Config\Controller::$route->path)
		{
			case '/robots.txt':
				return \Tags\Controller::changeRobotsHost($content);

			default:
				return $content;
		}
	}
}