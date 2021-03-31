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
			\Curl\Controller::rget()
		);

	
		/* if (
			isset(\Config\Controller::$route->query) &&
			\Config\Controller::$route
				->query
				->{key(\Config\Controller::$route->query)} == 'flush'
		)
			return $cacheController->flush(
				\Config\Controller::$route->query->{key(\Config\Controller::$route->query)}
			);*/

	}
}