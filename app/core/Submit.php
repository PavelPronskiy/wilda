<?php

namespace app\core;

use app\util\Mail;

/**
 * This class describes a submit.
 */
class Submit
{
    public function __construct()
    {

        if (Config::$mail->enabled)
        {
            if (Mail::sendMailSubmission())
            {
                $msg = [
                    'message' => 'OK',
                    'results' => [],
                ];
            }
            else
            {
                $msg = [
                    'error' => Config::$mail->error,
                ];
            }
        }
        else
        {
            $msg = [
                'message' => 'OK',
                'results' => [],
            ];
        }

        self::message($msg);
    }

    /**
     * { function_description }
     *
     * @param <type> $object The object
     */
    public static function message($object)
    {
        header('Content-type: application/javascript; charset=utf-8');
        die(json_encode($object));
    }
}
