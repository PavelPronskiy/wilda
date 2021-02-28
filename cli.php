<?php


define("PATH", __DIR__);
define("CLASS_PATH", PATH . '/class/');
define("CONFIG", PATH . '/config.json');
define("CONFIG_USER", PATH . '/.config.json');
define("RUN_METHOD", 'cli');

\libxml_use_internal_errors(true);

require_once PATH . '/vendor/autoload.php';

foreach ([
	'config',
	'checker',
	'curl'
] as $class) {
	require_once CLASS_PATH . $class . '.class.php';
}

new \Config\Controller;
new \Utils\CheckProjects;
