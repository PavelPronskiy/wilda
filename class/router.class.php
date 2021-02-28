<?php

namespace Router;

class Controller
{
	function __construct()
	{
		$cacheController = new \Cache\Controller;

		if (
			isset(\Config\Controller::$route->query) &&
			\Config\Controller::$route
				->query
				->{key(\Config\Controller::$route->query)} == 'flush'
		)
			return $cacheController->flush(
				\Config\Controller::$route->query->{key(\Config\Controller::$route->query)}
			);
	

		return \Config\Controller::render(
			\Curl\Controller::rget()
		);
	}
}