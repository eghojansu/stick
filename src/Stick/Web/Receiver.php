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

namespace Fal\Stick\Web;

use Fal\Stick\Fw;

/**
 * Request related helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Receiver
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * Class constructor.
     *
     * @param Fw $fw
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw;
    }

    /**
     * Return the MIME types stated in the HTTP Accept header as an array.
     *
     * @return array
     */
    public function acceptable(): array
    {
        $accept = array();

        foreach (explode(',', str_replace(' ', '', $this->fw->get('REQUEST.Accept', ''))) as $mime) {
            if (preg_match('/(.+?)(?:;q=([\d\.]+)|$)/', $mime, $parts)) {
                $accept[$parts[1]] = $parts[2] ?? 1;
            }
        }

        if ($accept) {
            krsort($accept);
            arsort($accept);
        } else {
            $accept['*/*'] = 1;
        }

        return $accept;
    }

    /**
     * Returns best match.
     *
     * @param mixed $mimes
     *
     * @return string
     */
    public function acceptBest($mimes = null): ?string
    {
        $mimes = $this->fw->split($mimes);

        foreach ($this->acceptable() as $mime => $q) {
            if ($q && $out = preg_grep('/'.str_replace('\*', '.*', preg_quote($mime, '/')).'/', $mimes)) {
                return current($out);
            }
        }

        return null;
    }

    /**
     * Write from php input.
     *
     * @param string $file
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public static function receiveRaw(string $file): bool
    {
        $src = fopen('php://input', 'r');
        $dst = fopen($file, 'w');

        if (!$src || !$dst) {
            return false;
        }

        while (!feof($src) && ($info = stream_get_meta_data($src)) && !$info['timed_out'] && $str = fgets($src, 4096)) {
            fputs($dst, $str, strlen($str));
        }

        fclose($dst);
        fclose($src);

        return true;
    }

    /**
     * Receive uploaded content.
     *
     * @param string $saveAs
     * @param bool   $overwrite
     *
     * @return bool
     */
    public function receiveFile(string $saveAs, bool $overwrite = false): bool
    {
        if (($exists = is_file($saveAs)) && !$overwrite) {
            return false;
        }

        $this->fw->mkdir($tmp = $this->fw->get('TEMP').'uploads/');

        $tmp .= $this->fw->get('SEED').'.'.$this->fw->hash(uniqid());
        $written = $this->fw->get('RAW') ? $this->fw->receiveRaw($tmp) : false !== $this->fw->write($tmp, $this->fw->get('BODY'));
        $saved = false;

        if ($written) {
            if ($exists) {
                unlink($saveAs);
            } else {
                $this->fw->mkdir(dirname($saveAs));
            }

            $saved = rename($tmp, $saveAs);
        }

        return $saved;
    }

    /**
     * Receive uploaded files.
     *
     * @param string        $dir
     * @param bool          $overwrite
     * @param callable|null $handler
     * @param callable|null $slugger
     *
     * @return array
     */
    public function receiveFiles(string $dir, bool $overwrite = false, callable $handler = null, callable $slugger = null): array
    {
        $result = array();

        if ($files = $this->fw->get('FILES')) {
            $this->fw->mkdir($dir);

            foreach ($files as $ndx => $uploadedFile) {
                if (isset($uploadedFile['name'])) {
                    $uploadedFile = array($uploadedFile);
                }

                foreach ($uploadedFile as $pos => $item) {
                    if ($slugger) {
                        $file = $dir.$slugger(basename($item['name']), $ndx, $pos);
                    } else {
                        $ext = (string) strrchr($item['name'], '.');
                        $file = $dir.Mime::slug(basename($item['name'], $ext)).$ext;
                    }

                    if (UPLOAD_ERR_OK === $item['error']) {
                        $unwritten = $overwrite || !file_exists($file);

                        if ($unwritten && (!$handler || false === $handler($file, $item, $ndx, $pos))) {
                            !is_uploaded_file($item['tmp_name']) || move_uploaded_file($item['tmp_name'], $file);
                        }
                    }

                    $result[$ndx][$file] = file_exists($file);
                }
            }
        }

        return $result;
    }

    /**
     * Submit HTTP request, cache the page as instructed by remote server.
     *
     * @param string      $url
     * @param array|null  $options HTTP context (http://www.php.net/manual/en/context.http.php)
     * @param string|null $engine
     *
     * @return array|null
     */
    public function request(string $url, array $options = null, string $engine = null): ?array
    {
        $parts = parse_url($url);

        if (empty($parts['scheme'])) {
            // Local URL
            $url = $this->fw->siteUrl($url, true);
            $parts = parse_url($url);
        } elseif (!preg_match('/https?/', $parts['scheme'])) {
            return null;
        }

        if (empty($options['headers'])) {
            $options['headers'] = array();
        } elseif (is_string($options['headers'])) {
            $options['headers'] = array($options['headers']);
        }

        $options += array(
            'method' => 'GET',
            'follow_location' => true,
            'max_redirects' => 20,
            'ignore_errors' => false,
        );

        $wrapper = $this->requestEngine($engine);

        if ('stream' !== $wrapper) {
            // PHP streams can't cope with redirects when Host header is set
            $this->requestSubstituteHeader($options['headers'], 'Host: '.$parts['host']);
        }

        $this->requestSubstituteHeader($options['headers'], array(
            'Accept-Encoding: gzip,deflate',
            'User-Agent: '.($options['user_agent'] ?? 'Mozilla/5.0 (compatible; '.php_uname('s').')'),
            'Connection: close',
        ));

        if (isset($options['content']) && is_string($options['content'])) {
            if (0 === strcasecmp($options['method'], 'POST') && !preg_grep('/^Content-Type:/i', $options['headers'])) {
                $this->requestSubstituteHeader($options['headers'], 'Content-Type: application/x-www-form-urlencoded');
            }

            $this->requestSubstituteHeader($options['headers'], 'Content-Length: '.strlen($options['content']));
        }

        if (isset($parts['user'],$parts['pass'])) {
            $this->requestSubstituteHeader($options['headers'], 'Authorization: Basic '.base64_encode($parts['user'].':'.$parts['pass']));
        }

        $eol = "\r\n";
        $cached = preg_match('/GET|HEAD/i', $options['method']);

        if ($cached && $cache = $this->fw->cget($hash = $this->fw->hash($options['method'].' '.$url).'.url')) {
            $cache['cached'] = true;

            if (preg_match('/Last-Modified: (.+?)'.preg_quote($eol).'/', implode($eol, $cache['headers']), $mod)) {
                $this->requestSubstituteHeader($options['headers'], 'If-Modified-Since: '.$mod[1]);
            }
        }

        try {
            $response = $this->{'request'.$wrapper}($url, $options);
        } catch (\Throwable $e) {
            throw new \LogicException(sprintf('Unable to perform request to %s (Message was: %s).', $url, trim($e->getMessage())));
        }

        if ($response && $cached) {
            if (preg_match('/HTTP\/1\.\d 304/', implode($eol, $response['headers']))) {
                $response = $cache;
            } elseif (preg_match('/Cache-Control:(?:.*)max-age=(\d+)(?:,?.*'.preg_quote($eol).')/i', implode($eol, $response['headers']), $exp)) {
                $this->fw->cset($hash, $response, $exp[1] + 0);
            }
        }

        $request = $options['headers'];
        array_unshift($request, $options['method'].' '.$url);

        return array_merge(compact('request'), $response);
    }

    /**
     * Specify the HTTP request engine to use.
     *
     * If not available, fallback to an applicable substitute.
     *
     * @param string|null $name
     *
     * @return string
     */
    protected function requestEngine(string $name = null): string
    {
        $available = array(
            'curl' => extension_loaded('curl'),
            'stream' => ini_get('allow_url_fopen'),
            'socket' => function_exists('fsockopen'),
        );

        foreach ($available as $engine => $ok) {
            if ((null === $name || 0 === strcasecmp($engine, $name)) && $ok) {
                return $engine;
            }
        }

        throw new \LogicException(sprintf('No suitable HTTP request engine found, given %s.', $name ?? 'none'));
    }

    /**
     * Replace old headers with new elements.
     *
     * @param array        &$oldHeaders
     * @param string|array $newHeaders
     */
    protected function requestSubstituteHeader(array &$oldHeaders, $newHeaders): void
    {
        if (is_string($newHeaders)) {
            $newHeaders = array($newHeaders);
        }

        foreach ($newHeaders as $header) {
            $oldHeaders = preg_grep('/'.preg_quote(strstr($header, ':', true), '/').':.+/', $oldHeaders, PREG_GREP_INVERT);
            array_push($oldHeaders, $header);
        }
    }

    /**
     * HTTP request via cURL.
     *
     * @param string $url
     * @param array  $options
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected function requestCurl(string $url, array $options): array
    {
        $curl = curl_init($url);

        if (!$open_basedir = ini_get('open_basedir')) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $options['follow_location']);
        }

        curl_setopt($curl, CURLOPT_MAXREDIRS, $options['max_redirects']);
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $options['method']);

        if (isset($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        if (isset($options['content'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['content']);
        }

        if (isset($options['proxy'])) {
            curl_setopt($curl, CURLOPT_PROXY, $options['proxy']);
        }

        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');

        $timeout = $options['timeout'] ?? ini_get('default_socket_timeout');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

        // Callback for response headers
        $headers = array();
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $line) use (&$headers) {
            if ($trim = trim($line)) {
                $headers[] = $trim;
            }

            return strlen($line);
        });

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        ob_start();
        curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $body = ob_get_clean();

        if (!$err && $options['follow_location'] && $open_basedir &&
            preg_grep('/HTTP\/1\.\d 3\d{2}/', $headers) &&
            preg_match('/^Location: (.+)$/m', implode(PHP_EOL, $headers), $loc)) {
            --$options['max_redirects'];

            if ('/' == $loc[1][0]) {
                $parts = parse_url($url);
                $loc[1] = $parts['scheme'].'://'.$parts['host'].
                    ((isset($parts['port']) && !in_array($parts['port'], array(80, 443)))
                        ? ':'.$parts['port'] : '').$loc[1];
            }

            return $this->request($loc[1], $options, 'curl');
        }

        return array(
            'body' => $body,
            'headers' => $headers,
            'engine' => 'curl',
            'cached' => false,
            'error' => $err,
        );
    }

    /**
     * HTTP request via PHP stream wrapper.
     *
     * @param string $url
     * @param array  $options
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected function requestStream(string $url, array $options): array
    {
        $eol = "\r\n";

        if (isset($options['proxy'])) {
            $options['proxy'] = preg_replace('/https?/i', 'tcp', $options['proxy']);
            $options['request_fulluri'] = true;

            if (preg_match('/socks4?/i', $options['proxy'])) {
                return $this->requestSocket($url, $options, true);
            }
        }

        $options['headers'] = implode($eol, $options['headers']);
        $body = file_get_contents($url, false, stream_context_create(array('http' => $options)));
        $headers = $http_response_header ?? array();
        $err = '';

        if (is_string($body)) {
            $match = null;
            foreach ($headers as $header) {
                if (preg_match('/Content-Encoding: (.+)/i', $header, $match)) {
                    switch ($match[1]) {
                        case 'gzip':
                            $body = gzdecode($body);
                            break;
                        case 'deflate':
                            $body = gzuncompress($body);
                            break;
                    }

                    break;
                }
            }
        } else {
            $tmp = error_get_last();
            $err = $tmp['message'];
        }

        return array(
            'body' => $body,
            'headers' => $headers,
            'engine' => 'stream',
            'cached' => false,
            'error' => $err,
        );
    }

    /**
     * HTTP request via low-level TCP/IP socket.
     *
     * @param string $url
     * @param array  $options
     * @param bool   $fromStream
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected function requestSocket(string $url, array $options, bool $fromStream = false): array
    {
        $eol = "\r\n";
        $headers = array();
        $body = '';
        $parts = parse_url($url);
        $hostname = $parts['host'];
        $proxy = false;

        if ('https' === $parts['scheme']) {
            $parts['host'] = 'ssl://'.$parts['host'];
        }

        if (empty($parts['port'])) {
            $parts['port'] = 'https' === $parts['scheme'] ? 443 : 80;
        }

        if (empty($parts['path'])) {
            $parts['path'] = '/';
        }

        if (empty($parts['query'])) {
            $parts['query'] = '';
        }

        if (isset($options['proxy'])) {
            $req = $url;
            $pp = parse_url($options['proxy']);
            $proxy = $pp['scheme'];
            if ('https' == $pp['scheme']) {
                $pp['host'] = 'ssl://'.$pp['host'];
            }

            if (empty($pp['port'])) {
                $pp['port'] = 'https' === $pp['scheme'] ? 443 : 80;
            }

            $socket = fsockopen($pp['host'], $pp['port'], $code, $err);
        } else {
            $req = $parts['path'].($parts['query'] ? ('?'.$parts['query']) : '');
            $socket = fsockopen($parts['host'], $parts['port'], $code, $err);
        }

        if ($socket) {
            stream_set_blocking($socket, true);
            stream_set_timeout($socket, intval($options['timeout'] ?? ini_get('default_socket_timeout')));

            if ('socks4' == $proxy) {
                // SOCKS4; http://en.wikipedia.org/wiki/SOCKS#Protocol
                $packet = "\x04\x01".pack('n', $parts['port']).
                    pack('H*', dechex(ip2long(gethostbyname($hostname))))."\0";
                fputs($socket, $packet, strlen($packet));
                $response = fread($socket, 9);

                if (8 == strlen($response) && (0 == ord($response[0]) || 4 == ord($response[0])) && 90 == ord($response[1])) {
                    $options['headers'][] = 'Host: '.$hostname;
                } else {
                    $err = 'Socket Status '.ord($response[1]);
                }
            }

            fputs($socket, $options['method'].' '.$req.' HTTP/1.0'.$eol);
            fputs($socket, implode($eol, $options['headers']).$eol.$eol);

            if (isset($options['content'])) {
                fputs($socket, $options['content'].$eol);
            }

            // Get response
            $content = '';
            while (!feof($socket) &&
                ($info = stream_get_meta_data($socket)) &&
                !$info['timed_out'] && !connection_aborted() &&
                $str = fgets($socket, 4096)) {
                $content .= $str;
            }

            fclose($socket);

            $html = explode($eol.$eol, $content, 2);
            $body = $html[1] ?? '';
            $headers = array_merge($headers, $current = explode($eol, $html[0]));
            $match = null;

            foreach ($current as $header) {
                if (preg_match('/Content-Encoding: (.+)/i', $header, $match)) {
                    switch ($match[1]) {
                        case 'gzip':
                            $body = gzdecode($body);
                            break;
                        case 'deflate':
                            $body = gzuncompress($body);
                            break;
                    }

                    break;
                }
            }

            if ($options['follow_location'] &&
                preg_grep('/HTTP\/1\.\d 3\d{2}/', $headers) &&
                preg_match('/Location: (.+?)'.preg_quote($eol).'/',
                $html[0], $loc)) {
                --$options['max_redirects'];

                return $this->request($loc[1], $options, $fromStream ? 'stream' : 'socket');
            }
        }

        return array(
            'body' => $body,
            'headers' => $headers,
            'engine' => 'socket',
            'cached' => false,
            'error' => $err,
        );
    }
}
