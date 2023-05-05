<?php

namespace app\core;

use app\util\Mail;


class Submit
{
	function __construct()
	{

		if (Config::$mail->enabled)
			if (Mail::sendMailSubmission())
				$msg = [
					'message' => 'OK',
					'results' => []
				];
			else
				$msg = [
					'error' => Config::$mail->error
				];

		self::message($msg);
	}

	/**
	 * [message description]
	 * @param  [type] $object [description]
	 * @return [type]         [description]
	 */
	public static function message($object)
	{
		header('Content-type: application/javascript; charset=utf-8');
		die(json_encode($object));
	}
}