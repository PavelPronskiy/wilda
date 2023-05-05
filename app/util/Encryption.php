<?php

namespace app\util;

use app\core\Config;
use \Hashids\Hashids;

class Encryption
{
	public static $hashids;

	function __construct() {
		self::$hashids = new Hashids(Config::$config->salt);
	}

	/**
	 * [encode description]
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public static function encode($value) : string
	{
		return self::$hashids->encodeHex(unpack('H*', $value)[1]);
	}

	/**
	 * [decode description]
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public static function decode($value) : string
	{
		return pack('H*', self::$hashids->decodeHex($value));
	}

}
