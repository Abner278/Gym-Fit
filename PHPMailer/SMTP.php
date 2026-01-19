<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer RFC821 SMTP email transport class.
 */
class SMTP
{
    const VERSION = '6.9.1';
    const LE = "\r\n";
    const DEFAULT_PORT = 25;
    const DEFAULT_SECURE_PORT = 465;
    const MAX_LINE_LENGTH = 998;

    public $do_debug = 0;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    public $Timeout = 300;
    public $Timelimit = 300;
    protected $smtp_conn;
    protected $error = [];
    protected $helo_rply = null;

    public function connect($host, $port = null, $timeout = 30, $options = [])
    {
        $this->smtp_conn = stream_socket_client(
            $host . ":" . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($options)
        );

        if (is_resource($this->smtp_conn)) {
            $this->get_lines(); // Get initial 220 message
            return true;
        }
        return false;
    }

    public function authenticate($username, $password, $authtype = null)
    {
        $this->client_send('EHLO localhost');
        $this->get_lines();

        $this->client_send('AUTH LOGIN');
        $this->get_lines();

        $this->client_send(base64_encode($username));
        $this->get_lines();

        $this->client_send(base64_encode($password));
        $reply = $this->get_lines();

        if (strpos($reply, '235') === 0) {
            return true;
        }
        return false;
    }

    public function mail($from)
    {
        $this->client_send("MAIL FROM:<$from>");
        $rply = $this->get_lines();
        return (strpos($rply, '250') === 0);
    }

    public function recipient($to)
    {
        $this->client_send("RCPT TO:<$to>");
        $rply = $this->get_lines();
        return (strpos($rply, '250') === 0);
    }

    public function data($msg_data)
    {
        $this->client_send("DATA");
        $this->get_lines();

        $this->client_send($msg_data . self::LE . ".");
        $rply = $this->get_lines();
        return (strpos($rply, '250') === 0);
    }

    public function quit()
    {
        $this->client_send("QUIT");
        $this->get_lines();
    }

    public function close()
    {
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }

    public function getError()
    {
        return $this->error;
    }

    protected function client_send($data)
    {
        fwrite($this->smtp_conn, $data . self::LE);
    }

    protected function get_lines()
    {
        $data = "";
        while ($str = fgets($this->smtp_conn, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $data;
    }

    public function reset()
    {
        $this->client_send('RSET');
        $this->get_lines();
    }
}
