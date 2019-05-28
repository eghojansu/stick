<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Util;

use Fal\Stick\Fw;
use Fal\Stick\Magic;

/**
 * SMTP helper ported from F3/SMTP.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Smtp extends Magic
{
    /** @var string E-mail attachments */
    protected $attachments;

    /** @var string SMTP host */
    protected $host;

    /** @var string SMTP port */
    protected $port;

    /** @var string TLS/SSL */
    protected $scheme;

    /** @var string User ID */
    protected $user;

    /** @var string User password */
    protected $password;

    /** @var resource TLS/SSL stream context */
    protected $context;

    /** @var resource TCP/IP socket */
    protected $socket;

    /** @var array */
    protected $headers;

    /** @var string Server-client conversation */
    protected $log = '';

    /**
     * Class constructor.
     *
     * @param string|null $user
     * @param string|null $password
     * @param string      $scheme
     * @param string      $host
     * @param int         $port
     * @param array|null  $context
     * @param string      $charset
     */
    public function __construct(
        string $user = null,
        string $password = null,
        string $scheme = 'ssl',
        string $host = 'localhost',
        int $port = 25,
        array $context = null,
        string $charset = 'UTF-8'
    ) {
        $this->headers = array(
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset='.$charset,
        );
        $this->scheme = 0 === strcasecmp($scheme, 'ssl') ? 'ssl' : 'tcp';
        $this->host = $this->scheme.'://'.strtolower($host);
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->context = stream_context_create($context);
    }

    /**
     * Return TRUE if header exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->headers[Fw::dashCase($key)]);
    }

    /**
     * Return value of e-mail header.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &get(string $key)
    {
        $use = Fw::dashCase($key);

        if (isset($this->headers[$use])) {
            return $this->headers[$use];
        }

        throw new \LogicException(sprintf('Header not exists: %s.', $key));
    }

    /**
     * Bind value to e-mail header.
     *
     * @param string $key
     * @param string $value
     *
     * @return Magic
     */
    public function set(string $key, $value): Magic
    {
        $this->headers[Fw::dashCase($key)] = (string) $value;

        return $this;
    }

    /**
     * Remove header.
     *
     * @param string $key
     *
     * @return Magic
     */
    public function rem(string $key): Magic
    {
        unset($this->headers[Fw::dashCase($key)]);

        return $this;
    }

    /**
     * Return client-server conversation history.
     *
     * @return string
     */
    public function log(): string
    {
        return str_replace("\n", PHP_EOL, $this->log);
    }

    /**
     * Add e-mail attachment.
     *
     * @param string      $file
     * @param string|null $alias
     * @param string|null $cid
     *
     * @return Smtp
     */
    public function attach(string $file, string $alias = null, string $cid = null): Smtp
    {
        if (!is_file($file)) {
            throw new \LogicException(sprintf('Attachment file not found: %s.', $file));
        }

        $this->attachments[] = array(
            'filename' => $file,
            'alias' => $alias ?? basename($file),
            'cid' => $cid,
        );

        return $this;
    }

    /**
     * Transmit message.
     *
     * @param string $message
     * @param bool   $log
     * @param bool   $mock
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public function send(string $message, bool $log = true, bool $mock = false): bool
    {
        if ('ssl' == $this->scheme && !extension_loaded('openssl')) {
            return false;
        }

        // Message should not be blank
        if (!$message) {
            throw new \LogicException('Message must not be blank.');
        }

        // Retrieve headers
        $headers = $this->headers;
        $host = $this->fw->get('HOST');

        // Connect to the server
        if (!$mock) {
            $socket = &$this->socket;
            $socket = stream_socket_client(
                $this->host.':'.$this->port,
                $errno,
                $errstr,
                ini_get('default_socket_timeout'),
                STREAM_CLIENT_CONNECT,
                $this->context
            );

            if (!$socket) {
                $this->fw->error(500, $errstr);

                return false;
            }

            stream_set_blocking($socket, true);
        }

        // Get server's initial response
        $this->dialog(null, $log, $mock);

        // Announce presence
        $reply = $this->dialog('EHLO '.$host, $log, $mock);

        if ('tls' === $this->scheme) {
            $this->dialog('STARTTLS', $log, $mock);

            if (!$mock) {
                $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    $method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
                }

                stream_socket_enable_crypto($socket, true, $method);
            }

            $reply = $this->dialog('EHLO '.$host, $log, $mock);
        }

        $message = wordwrap($message, 998);

        if (preg_match('/8BITMIME/', $reply)) {
            $headers['Content-Transfer-Encoding'] = '8bit';
        } else {
            $headers['Content-Transfer-Encoding'] = 'quoted-printable';
            $message = preg_replace('/^\.(.+)/m', '..$1', quoted_printable_encode($message));
        }

        if ($this->user && $this->password && preg_match('/AUTH/', $reply)) {
            // Authenticate
            $this->dialog('AUTH LOGIN', $log, $mock);
            $this->dialog(base64_encode($this->user), $log, $mock);

            $reply = $this->dialog(base64_encode($this->password), $log, $mock);

            if (!preg_match('/^235\s.*/', $reply)) {
                $this->dialog('QUIT', $log, $mock);

                if (!$mock && $socket) {
                    fclose($socket);
                }

                return false;
            }
        }

        if (empty($headers['Message-Id'])) {
            $headers['Message-Id'] = '<'.uniqid('', true).'@'.$this->host.'>';
        }

        if (empty($headers['Date'])) {
            $headers['Date'] = date('r');
        }

        // Required headers
        $reqd = array('From', 'To', 'Subject');

        foreach ($reqd as $id) {
            if (empty($headers[$id])) {
                throw new \LogicException(sprintf('%s: header is required', $id));
            }
        }

        $eol = "\r\n";

        // Stringify headers
        foreach ($headers as $key => &$val) {
            if (in_array($key, array('From', 'To', 'Cc', 'Bcc'))) {
                $email = '';

                preg_match_all('/(?:".+?" )?(?:<.+?>|[^ ,]+)/', $val, $matches, PREG_SET_ORDER);

                foreach ($matches as $raw) {
                    $email .= ', '.(preg_match('/<.+?>/', $raw[0]) ? $raw[0] : '<'.$raw[0].'>');
                }

                $val = ltrim($email, ', ');
            }

            unset($val);
        }

        // Start message dialog
        $this->dialog('MAIL FROM: '.strstr($headers['From'], '<'), $log, $mock);

        foreach ($this->fw->split($headers['To'].';'.($headers['Cc'] ?? '').';'.($headers['Bcc'] ?? '')) as $dst) {
            $this->dialog('RCPT TO: '.strstr($dst, '<'), $log, $mock);
        }

        $this->dialog('DATA', $log, $mock);

        if ($this->attachments) {
            $hash = uniqid(null, true);
            // Replace Content-Type
            $type = $headers['Content-Type'];
            unset($headers['Content-Type']);
            $enc = $headers['Content-Transfer-Encoding'];
            unset($headers['Content-Transfer-Encoding']);

            // Send mail headers
            $out = 'Content-Type: multipart/mixed; boundary="'.$hash.'"'.$eol;
            foreach ($headers as $key => $val) {
                if ('Bcc' != $key) {
                    $out .= $key.': '.$val.$eol;
                }
            }

            $out .= $eol;
            $out .= 'This is a multi-part message in MIME format'.$eol;
            $out .= $eol;
            $out .= '--'.$hash.$eol;
            $out .= 'Content-Type: '.$type.$eol;
            $out .= 'Content-Transfer-Encoding: '.$enc.$eol;
            $out .= $eol;
            $out .= $message.$eol;

            foreach ($this->attachments as $attachment) {
                $out .= '--'.$hash.$eol;
                $out .= 'Content-Type: application/octet-stream'.$eol;
                $out .= 'Content-Transfer-Encoding: base64'.$eol;

                if ($attachment['cid']) {
                    $out .= 'Content-Id: '.$attachment['cid'].$eol;
                }

                $out .= 'Content-Disposition: attachment; '.'filename="'.$attachment['alias'].'"'.$eol;
                $out .= $eol;
                $out .= chunk_split(base64_encode(file_get_contents($file))).$eol;
            }

            $out .= $eol;
            $out .= '--'.$hash.'--'.$eol;
            $out .= '.';

            $this->dialog($out, preg_match('/verbose/i', $log), $mock);
        } else {
            // Send mail headers
            $out = '';

            foreach ($headers as $key => $val) {
                if ('Bcc' != $key) {
                    $out .= $key.': '.$val.$eol;
                }
            }

            $out .= $eol;
            $out .= $message.$eol;
            $out .= '.';

            // Send message
            $this->dialog($out, preg_match('/verbose/i', $log), $mock);
        }

        $this->dialog('QUIT', $log, $mock);

        if (!$mock && $socket) {
            fclose($socket);
        }

        return true;
    }

    /**
     * Send SMTP command and record server response.
     *
     * @param string|null $cmd
     * @param bool        $log
     * @param bool        $mock
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function dialog(string $cmd = null, bool $log = true, bool $mock = false): string
    {
        $reply = '';

        if ($mock) {
            $host = str_replace('ssl://', '', $this->host);

            switch ($cmd) {
                case null:
                    $reply = '220 '.$host.' ESMTP ready'."\n";
                    break;
                case 'DATA':
                    $reply = '354 Go ahead'."\n";
                    break;
                case 'QUIT':
                    $reply = '221 '.$host.' closing connection'."\n";
                    break;
                default:
                    $reply = '250 OK'."\n";
                    break;
            }
        } else {
            $socket = &$this->socket;

            if ($cmd) {
                fputs($socket, $cmd."\r\n");
            }

            while (!feof($socket) &&
                ($info = stream_get_meta_data($socket)) &&
                !$info['timed_out'] && $str = fgets($socket, 4096)) {
                $reply .= $str;
                if (preg_match('/(?:^|\n)\d{3} .+?\r\n/s', $reply)) {
                    break;
                }
            }
        }

        if ($log) {
            if ($cmd) {
                $this->log .= $cmd."\n";
            }

            $this->log .= str_replace("\r", '', $reply);
        }

        return $reply;
    }
}
