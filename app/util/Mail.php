<?php

namespace app\util;

use app\core\Config;

/**
 * Mail sender
 */
class Mail
{
    /**
     * [getHTMLTemplate description]
     * @param  [type] $submission     [description]
     * @return [type] [description]
     */
    public static function getHTMLTemplate($submission)
    {
        $gmdate = gmdate('D, d M Y H:i:s T', time());

        $fc      = dirname(__DIR__) . '/tpl/mail/index.html';
        $file_gc = '';

        if (file_exists($fc))
        {
            $file_gc = file_get_contents($fc);
            // $file_gc = str_replace('{SUBJECT}', Config::$mail->subject . ' ' . Config::getSiteName(), $file_gc);
            $file_gc = str_replace('{MAILFROM}', Config::$mail->from, $file_gc);
            // $file_gc = str_replace('{DOMAIN}', Config::$domain->site, $file_gc);
            $file_gc = str_replace('{TABLEDATA}', self::submissionTemplate($submission), $file_gc);
            $file_gc = str_replace('{TIMESTAMP}', $gmdate, $file_gc);
        }
        else
        {
            $this->message([
                'message' => 'ERROR',
                'results' => [
                    '7097086:4614970481',
                ],
            ]);
        }

        return $file_gc;
    }

    /**
     * [sendMailSubmission description]
     * @return [type] [description]
     */
    public static function sendMailSubmission()
    {
        // $mail_recipients = Config::getMailRecipients();
        // var_dump($mail_recipients);

        $html = self::getHTMLTemplate((object) [
            'name'    => isset(Config::$route->post->Name) ? Config::$route->post->Name : '',
            'phone'   => isset(Config::$route->post->Phone) ? Config::$route->post->Phone : '',
            'mail'    => isset(Config::$route->post->Mail) ? Config::$route->post->Mail : '',
            'message' => isset(Config::$route->post->Message) ? Config::$route->post->Message : '',
        ]);

        return self::send($html);
    }

    /**
     * [submissionTemplate description]
     * @param  [type] $object         [description]
     * @return [type] [description]
     */
    public static function submissionTemplate($object)
    {

        $width_th = 'width="150"';
        $date     = date('Y-m-d H:i:s');
        $padding  = 'padding: 4px 8px;';
        $html     = '';

        if (isset($object->name) && !empty($object->name))
        {
            $html .= '<tr><th ' . $width_th . ' style="text-align:right;' . $padding . '">Имя: </th><td style="' . $padding . '">' . $object->name . '</td></tr>';
        }

        if (isset($object->phone) && !empty($object->phone))
        {
            $html .= '<tr><th ' . $width_th . ' style="text-align:right;' . $padding . '">Телефон: </th><td style="' . $padding . '">' . $object->phone . '</td></tr>';
        }

        if (isset($object->mail) && !empty($object->mail))
        {
            $html .= '<tr><th ' . $width_th . ' style="text-align:right;' . $padding . '">E-mail: </th><td style="' . $padding . '">' . $object->mail . '</td></tr>';
        }

        if (isset($object->message) && !empty($object->message))
        {
            $html .= '<tr><td ' . $width_th . ' colspan="2" style="' . $padding . '"><b>Сообщение:</b><pre>' . $object->message . '</pre></td></tr>';
        }

        $html .= '<tr><th ' . $width_th . ' style="text-align:right;' . $padding . '"><b>Дата создания:</b></th><td style="' . $padding . '">' . $date . '</td></tr>';

        return $html;
    }

    /**
     * [send description]
     * @param  [type] $body           [description]
     * @return [type] [description]
     */
    private static function send($body): bool
    {
        $bool = false;
        try {
            $PHPMailer            = new \PHPMailer (false);
            $PHPMailer->XMailer   = ' ';
            $PHPMailer->SMTPDebug = 1;
            $PHPMailer->CharSet   = 'utf-8';
            $PHPMailer->isSendmail();

            $PHPMailer->Sender = Config::$mail->from;
            $PHPMailer->setFrom(Config::$mail->from, Config::$mail->name, false);
            $PHPMailer->isHTML(true);
            $PHPMailer->Subject = Config::$mail->subject . ' ' . Config::getSiteName();
            $PHPMailer->Body    = $body;

            /**
             * Установка адресов отправителей
             */
            foreach (Config::$mail->to as $to)
            {
                if (!is_null($to))
                {
                    $PHPMailer->addAddress($to);
                }
            }

            /**
             * Отправка
             */

            return $PHPMailer->send();
        }
        catch (Exception $e)
        {
            $bool = false;
        }

        return $bool;
    }
}
