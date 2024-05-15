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
    public static function getHTMLTemplate(object $submission)
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
            $file_gc = str_replace('{TABLEDATA}', self::setDynamicFieldsHTMLTemplate($submission), $file_gc);
            $file_gc = str_replace('{TIMESTAMP}', $gmdate, $file_gc);
        }
/*        else
        {
            $this->message([
                'message' => 'ERROR',
                'results' => [
                    '7097086:4614970481',
                ],
            ]);
        }*/

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

        /* $html = self::getHTMLTemplate((object) [
            'name'      => isset(Config::$route->post->Name) ? Config::$route->post->Name : '',
            'phone'     => isset(Config::$route->post->Phone) ? Config::$route->post->Phone : '',
            'mail'      => isset(Config::$route->post->Mail) ? Config::$route->post->Mail : '',
            'message'   => isset(Config::$route->post->Message) ? Config::$route->post->Message : '',
            'Date'      => isset(Config::$route->post->Date) ? Config::$route->post->Date : '',
            'Selectbox' => isset(Config::$route->post->Selectbox) ? Config::$route->post->Selectbox : '',
        ]); */

        $fields = self::dynamicSubmissionFields();
        $html = self::getHTMLTemplate($fields);
        // var_dump($html);
        return self::send($html);
    }

    public static function dynamicSubmissionFields(array $fields = []): object
    {
        // $mail_recipients = Config::getMailRecipients();
        // var_dump($mail_recipients);

        foreach((array) Config::$route->post as $key => $value)
        {
            if (!preg_match('#tildaspec#', $key) && !empty($value))
            {
                $fields[$key] = $value;
            }
        }

        return (object) $fields;
    }

    /**
     * [submissionTemplate description]
     * @param  [type] $object         [description]
     * @return [type] [description]
     */
    public static function setDynamicFieldsHTMLTemplate(object $object, string $html = ''): string
    {
        $incr = 0;
        $p_css = 'style="padding:4px 8px 4px 8px"';

        foreach((array) $object as $key => $value)
        {
            $key = str_replace('_', ' ', $key);

            if (preg_match('/^name$/i', $key))
            {
                $html .= '<p '.$p_css.'><b>Имя:</b>&nbsp;'.$value.'</p>';
            }
            else if (preg_match('/^phone$/i', $key))
            {
                $phone = preg_replace('/[^\p{L}\p{N}]/', '', $value);
                $html .= '<p '.$p_css.'><b>Телефон:&nbsp;</b><a href="tel:'.$phone.'">'.$value.'</a></p>';
            }
            else if (preg_match('/^message/i', $key))
            {
                $html .= '<p '.$p_css.'><b>Сообщение:&nbsp;</b><pre>'.$value.'</pre></p>';
            }
            else if (preg_match('/^file/i', $key))
            {
                $file = preg_replace('/file/', 'Файл', $key);
                $html .= '<p '.$p_css.'><b>'.$file.':&nbsp;</b><a href="'.$value.'">'.$value.'</a></p>';
            }
            else if (preg_match('/^mail$/i', $key))
            {
                $html .= '<p '.$p_css.'><b>Почта:</b>&nbsp;'.$value.'</p>';
            }
            else if (preg_match('/^date$/i', $key))
            {
                $html .= '<p '.$p_css.'><b>Дата:</b>&nbsp;'.$value.'</p>';
            }
            else if (preg_match('/^selectbox$/i', $key))
            {
                $html .= '<p '.$p_css.'><b>Выбор:</b>&nbsp;'.$value.'</p>';
            }
            else
            {
                $html .= '<p '.$p_css.'><b>'.$key.':</b>&nbsp;'.$value.'</p>';
            }

        }

        return $html;
    }

    /**
     * Sets the debug.
     *
     * @return     int   ( description_of_the_return_value )
     */
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
