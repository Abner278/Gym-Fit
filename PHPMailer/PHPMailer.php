<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer - PHP email creation and transport class.
 * Simplified for local GymFit environment.
 */
class PHPMailer
{
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    // Properties
    public $Priority = null;
    public $CharSet = self::CHARSET_ISO88591;
    public $ContentType = self::CONTENT_TYPE_PLAINTEXT;
    public $Encoding = self::ENCODING_8BIT;
    public $ErrorInfo = '';
    public $From = '';
    public $FromName = '';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $WordWrap = 0;
    public $Mailer = 'mail';
    public $Sendmail = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $ConfirmReadingTo = '';
    public $Hostname = '';
    public $MessageID = '';
    public $MessageDate = '';
    public $Host = 'localhost';
    public $Port = 25;
    public $Helo = '';
    public $SMTPSecure = '';
    public $SMTPAutoTLS = true;
    public $SMTPAuth = false;
    public $SMTPOptions = [];
    public $Username = '';
    public $Password = '';
    public $AuthType = '';
    public $Timeout = 300;
    public $DSN = '';
    public $SMTPDebug = 0;
    public $Debugoutput = 'html';
    public $SMTPKeepAlive = false;
    public $SingleTo = false;

    // Internal
    protected $smtp = null;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $all_recipients = [];
    protected $RecipientsQueue = [];
    protected $ReplyToQueue = [];
    protected $Attachment = [];
    protected $CustomHeader = [];
    protected $lastMessageID = '';
    protected $message_type = '';
    protected $boundary = [];
    protected $language = [];
    protected $error_count = 0;
    protected $sign_cert_file = '';
    protected $sign_key_file = '';
    protected $sign_extracerts_file = '';
    protected $sign_key_pass = '';
    protected $exceptions = false;
    protected $uniqueid = '';

    // MISSING PROPERTIES FIXED HERE
    public $MIMEHeader = '';
    public $MIMEBody = '';

    const VERSION = '6.9.1';
    const STOP_MESSAGE = 0;
    const STOP_CONTINUE = 1;
    const STOP_CRITICAL = 2;

    public function __construct($exceptions = null)
    {
        if (null !== $exceptions) {
            $this->exceptions = (bool) $exceptions;
        }
        $this->Lang('en_us');
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    public function isHTML($isHtml = true)
    {
        if ($isHtml) {
            $this->ContentType = self::CONTENT_TYPE_TEXT_HTML;
        } else {
            $this->ContentType = self::CONTENT_TYPE_PLAINTEXT;
        }
    }

    public function addAddress($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('to', $address, $name);
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        if (($pos = strrpos($address, '@')) === false) {
            if ($this->exceptions) {
                throw new Exception("Invalid address: $address");
            }
            return false;
        }
        $this->From = $address;
        $this->FromName = $name;
        if ($auto) {
            if (empty($this->Sender)) {
                $this->Sender = $address;
            }
        }
        return true;
    }

    public function send()
    {
        try {
            if (!$this->preSend()) {
                return false;
            }
            return $this->postSend();
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    protected function preSend()
    {
        if (empty($this->From)) {
            $this->setError($this->lang('empty_message'));
            return false;
        }

        // --- Manual Header Construction (Missing in previous version) ---
        $headers = "Date: " . date('D, j M Y H:i:s O') . "\r\n";

        // From
        if ($this->FromName == '') {
            $headers .= "From: " . $this->From . "\r\n";
        } else {
            $headers .= "From: " . $this->FromName . " <" . $this->From . ">\r\n";
        }

        // To
        $recipients = [];
        foreach ($this->to as $to) {
            $recipients[] = $to[0];
        }
        $headers .= "To: " . implode(', ', $recipients) . "\r\n";

        // Subject
        $headers .= "Subject: " . $this->Subject . "\r\n";

        // Content Type
        $headers .= "MIME-Version: 1.0\r\n";
        if ($this->ContentType == self::CONTENT_TYPE_TEXT_HTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }

        // Save to Properties
        $this->MIMEHeader = $headers . "\r\n"; // Double CRLF separates header from body
        $this->MIMEBody = $this->Body . "\r\n";

        return true;
    }

    protected function postSend()
    {
        try {
            switch ($this->Mailer) {
                case 'smtp':
                    return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
                default:
                    return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
            }
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    protected function smtpSend($header, $body)
    {
        if (!$this->smtpConnect($this->SMTPOptions)) {
            throw new Exception($this->lang('smtp_connect_failed'), self::STOP_CRITICAL);
        }

        if (empty($this->Sender)) {
            $smtp_from = $this->From;
        } else {
            $smtp_from = $this->Sender;
        }

        if (!$this->smtp->mail($smtp_from)) {
            throw new Exception($this->lang('from_failed') . $smtp_from, self::STOP_CRITICAL);
        }

        foreach ($this->to as $to) {
            if (!$this->smtp->recipient($to[0])) {
                // recipient failed
            }
        }

        if (!$this->smtp->data($header . $body)) {
            throw new Exception($this->lang('data_not_accepted'), self::STOP_CRITICAL);
        }

        $this->smtp->quit();
        $this->smtp->close();

        return true;
    }

    public function smtpConnect($options = null)
    {
        if (null === $this->smtp) {
            $this->smtp = new SMTP();
        }

        if (null !== $options) {
            $this->SMTPOptions = $options;
        }

        // Connect
        $connection = $this->smtp->connect($this->Host, $this->Port, $this->Timeout, $this->SMTPOptions);

        if ($connection) {
            if (!empty($this->Host) && $this->SMTPAuth) {
                if (!$this->smtp->authenticate($this->Username, $this->Password, $this->AuthType)) {
                    throw new Exception($this->lang('authenticate'));
                }
            }
        }
        return $connection;
    }

    protected function mailSend($header, $body)
    {
        $toArr = [];
        foreach ($this->to as $to) {
            $toArr[] = $this->addrFormat($to);
        }
        $to = implode(', ', $toArr);

        return @mail($to, $this->Subject, $body, $header);
    }

    protected function addOrEnqueueAnAddress($kind, $address, $name)
    {
        $address = trim($address);
        if ($kind != 'Reply-To') {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                array_push($this->$kind, [$address, $name]);
                $this->all_recipients[strtolower($address)] = true;
                return true;
            }
        }
        return false;
    }

    public function addrFormat($addr)
    {
        if (empty($addr[1])) {
            return $addr[0];
        } else {
            return $addr[1] . " <" . $addr[0] . ">";
        }
    }

    protected function Lang($key)
    {
        // Simple lang fallback
        $map = [
            'authenticate' => 'SMTP Error: Could not authenticate.',
            'connect_host' => 'SMTP Error: Could not connect to SMTP host.',
            'data_not_accepted' => 'SMTP Error: data not accepted.',
            'empty_message' => 'Message body empty',
            'from_failed' => 'The following From address failed: ',
            'smtp_connect_failed' => 'SMTP connect() failed.',
        ];
        return isset($map[$key]) ? $map[$key] : $key;
    }

    protected function setError($msg)
    {
        $this->error_count++;
        $this->ErrorInfo = $msg;
    }
}
