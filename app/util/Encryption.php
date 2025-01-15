<?php

namespace app\util;

// use app\core\Config;
// use Hashids\Hashids;

class Encryption
{
    /**
     * @var mixed
     */
    public static $hashids;

    public function __construct()
    {
        // self::$hashids = new Hashids(Config::$config->salt);
    }

    /**
     * [decode description]
     * @param  [type] $value          [description]
     * @return [type] [description]
     */
/*    public static function decode($value): string
    {
        return pack('H*', self::$hashids->decodeHex($value));
    }
*/
    /**
     * [encode description]
     * @param  [type] $value          [description]
     * @return [type] [description]
     */
    public static function encode($value): string
    {
        // var_dump($value);
        return \hash('murmur3a', $value);
        // return self::$hashids->encodeHex(unpack('H*', $value)[1]);
    }
}
