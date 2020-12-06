<?php

namespace Tilda;

class Config
{
	public static function getConfig()
	{
		$array = [];
		$config_json = [];
		$config_user_json = [];
		
		if (file_exists(CONFIG)) {
			$config_json = json_decode(file_get_contents(CONFIG));
			if (json_last_error() > 0) {
				die(json_last_error_msg() . ' ' . CONFIG);
			}
		} else {
			die('Default config: ' . CONFIG . ' not found');
		}

		if (file_exists(CONFIG_USER)) {
			$config_user_json = json_decode(file_get_contents(CONFIG_USER));
			if (json_last_error() > 0) {
				die(json_last_error_msg() . ' ' . CONFIG_USER);
			}
		}

		$array = (object)array_merge((array)$config_json, (array)$config_user_json);
		return $array;
	}

}
