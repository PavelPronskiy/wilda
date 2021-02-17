<?php

define("PATH", __DIR__);
define("CLASS_PATH", PATH . '/class/');
define("CONFIG", PATH . '/config.json');
define("CONFIG_USER", PATH . '/.config.json');

require_once PATH . '/vendor/autoload.php';

foreach (['config', 'cache', 'encryption', 'tilda'] as $class) {
	require_once CLASS_PATH . $class . '.class.php';
}

new \Tilda\Controller();
