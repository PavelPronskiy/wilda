<?php

namespace app\util;

use app\core\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
            'name'      => isset(Config::$route->post->Name) ? Config::$route->post->Name : '',
            'phone'     => isset(Config::$route->post->Phone) ? Config::$route->post->Phone : '',
            'mail'      => isset(Config::$route->post->Mail) ? Config::$route->post->Mail : '',
            'message'   => isset(Config::$route->post->Message) ? Config::$route->post->Message : '',
            'Date'      => isset(Config::$route->post->Date) ? Config::$route->post->Date : '',
            'Selectbox' => isset(Config::$route->post->Selectbox) ? Config::$route->post->Selectbox : '',
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
        $align    = 'text-align:right;';

        if (!empty($object->name))
        {
            $html .= '<tr><th ' . $width_th . ' style="' . $align . $padding . '">Имя: </th><td style="' . $padding . '">' . $object->name . '</td></tr>';
        }

        if (!empty($object->phone))
        {
            $html .= '<tr><th ' . $width_th . ' style="' . $align . $padding . '">Телефон: </th><td style="' . $padding . '">' . $object->phone . '</td></tr>';
        }

        if (!empty($object->mail))
        {
            $html .= '<tr><th ' . $width_th . ' style="' . $align . $padding . '">E-mail: </th><td style="' . $padding . '">' . $object->mail . '</td></tr>';
        }

        if (!empty($object->Date))
        {
            $html .= '<tr><th ' . $width_th . ' style="' . $align . $padding . '"><b>Дата бронирования: </b></th><td>' . $object->Date . '</td></tr>';
        }

        if (!empty($object->Selectbox))
        {
            $html .= '<tr><th ' . $width_th . ' style="' . $align . $padding . '"><b>Объект бронирования: </b></th><td>' . $object->Selectbox . '</td></tr>';
        }

        if (!empty($object->message))
        {
            $html .= '<tr><td ' . $width_th . ' colspan="2" style="' . $padding . '"><b>Сообщение:</b><pre>' . $object->message . '</pre></td></tr>';
        }

        $html .= '<tr><td ' . $width_th . ' colspan="2" style="' . $align . $padding . '"><i>Дата создания заявки: ' . $date . '</i></td></tr>';

        return $html;
    }

    private static function setDebug(): int
    {
        switch(Config::$mail->debug)
        {
            case 'off':
                return SMTP::DEBUG_OFF;

            case 'client':
                return SMTP::DEBUG_CLIENT;

            case 'server':
                return SMTP::DEBUG_SERVER;
        }
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
            $PHPMailer            = new PHPMailer(false);
            $PHPMailer->XMailer   = ' ';
            $PHPMailer->CharSet   = 'utf-8';
            $PHPMailer->SMTPDebug = self::setDebug();

            switch(Config::$mail->send_type)
            {
                case 'sendmail':
                    $PHPMailer->isSendmail();
                    break;

                case 'smtp':
                    $PHPMailer->IsSMTP();

                    $PHPMailer->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ]
                    ];
                    
                    // $PHPMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $PHPMailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

                    //Set the hostname of the mail server
                    $PHPMailer->Host = Config::$mail->smtp->host;

                    //Set the SMTP port number:
                    // - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
                    // - 587 for SMTP+STARTTLS
                    $PHPMailer->Port = Config::$mail->smtp->port;

                    //Whether to use SMTP authentication
                    $PHPMailer->SMTPAuth = Config::$mail->smtp->auth;

                    //Username to use for SMTP authentication - use full email address for gmail
                    $PHPMailer->Username = Config::$mail->smtp->username;

                    //Password to use for SMTP authentication
                    $PHPMailer->Password = Config::$mail->smtp->password;

                    break;
            }

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
