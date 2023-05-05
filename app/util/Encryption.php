<?php

namespace app\util;

use app\core\Config;


class Encryption
{
	public static function safeB64encode($string) : string
	{
		$data = base64_encode($string);
		$data = str_replace(array('+','/','='),array('-','_',''),$data);
		return $data;
	}

	public static function safeB64decode($string) : string
	{
		$data = str_replace(array('-','_'),array('+','/'),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}
	
	public static function encode($value) : string
	{
		return \trim(self::safeB64encode(
			\mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256,
				Config::$config->salt,
				$value,
				MCRYPT_MODE_ECB,
				\mcrypt_create_iv(
					\mcrypt_get_iv_size(
						MCRYPT_RIJNDAEL_256,
						MCRYPT_MODE_ECB
					),
					MCRYPT_RAND)
				)
			)
		);
	}

	public static function decode($value) : string
	{
		return \trim(
			\mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256,
				Config::$config->salt,
				self::safeB64decode($value),
				MCRYPT_MODE_ECB,
				\mcrypt_create_iv(
					\mcrypt_get_iv_size(
						MCRYPT_RIJNDAEL_256,
						MCRYPT_MODE_ECB
					),
					MCRYPT_RAND
				)
			)
		);
	}
}
