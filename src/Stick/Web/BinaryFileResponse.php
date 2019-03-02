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

use Fal\Stick\Util;

/**
 * Binary file response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class BinaryFileResponse extends ChunkedResponse
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var bool
     */
    protected $deleteFileAfterSend = false;

    /**
     * Class constructor.
     *
     * @param string $filename The file to stream
     * @param int    $status   The response status code
     * @param array  $headers  An array of response headers
     */
    public function __construct(string $filename, int $status = null, array $headers = null)
    {
        parent::__construct(null, $status, $headers);

        $this->setFile($filename);
    }

    /**
     * Sets the file to stream.
     *
     * @param string $filename           The file to stream
     * @param string $contentDisposition
     * @param bool   $autoEtag
     * @param bool   $autoLastModified
     *
     * @return BinaryFileResponse
     *
     * @throws LogicException
     */
    public function setFile(string $filename, string $contentDisposition = null, bool $autoEtag = false, bool $autoLastModified = true): BinaryFileResponse
    {
        if (!is_readable($filename)) {
            throw new \LogicException('File must be readable.');
        }

        $this->filename = $filename;

        if ($autoEtag) {
            $this->setAutoEtag();
        }

        if ($autoLastModified) {
            $this->setAutoLastModified();
        }

        if ($contentDisposition) {
            $this->setContentDisposition($contentDisposition);
        }

        return $this;
    }

    /**
     * Gets the file.
     *
     * @return File The file to stream
     */
    public function getFile(): string
    {
        return $this->filename;
    }

    /**
     * Automatically sets the Last-Modified header according the file modification date.
     *
     * @return BinaryFileResponse
     */
    public function setAutoLastModified(): BinaryFileResponse
    {
        $date = \DateTime::createFromFormat('U', ''.filemtime($this->filename), new \DateTimeZone('UTC'));

        $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s').' GMT');

        return $this;
    }

    /**
     * Automatically sets the ETag header according to the checksum of the file.
     *
     * @return BinaryFileResponse
     */
    public function setAutoEtag(): BinaryFileResponse
    {
        $etag = base64_encode(hash_file('sha256', realpath($this->filename), true));
        $this->headers->set('ETag', '"'.$etag.'"');

        return $this;
    }

    /**
     * Sets the Content-Disposition header with the given filename.
     *
     * @param string $disposition ResponseHeaderBag::DISPOSITION_INLINE or ResponseHeaderBag::DISPOSITION_ATTACHMENT
     * @param string $filename    Optionally use this UTF-8 encoded filename instead of the real name of the file
     *
     * @return BinaryFileResponse
     */
    public function setContentDisposition(string $disposition, string $filename = null): BinaryFileResponse
    {
        $mFile = $filename ?? basename($this->filename);
        $dispositionHeader = sprintf('%s; filename="%s"', $disposition, $mFile);

        $this->headers->set('Content-Disposition', $dispositionHeader);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(Request $request): Response
    {
        if (!$this->headers->exists('Content-Type')) {
            $this->headers->set('Content-Type', Util::mime($this->filename));
        }

        if ('HTTP/1.0' !== $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }

        if (false !== $fileSize = filesize($this->filename)) {
            $this->headers->set('Content-Length', $fileSize);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sendContent(): Response
    {
        if (!$this->isSuccessful()) {
            return parent::sendContent();
        }

        if (0 >= $this->kbps) {
            $this->sendStream();
        } else {
            $this->sendChunked();
        }

        if ($this->deleteFileAfterSend && file_exists($this->filename)) {
            unlink($this->filename);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException when the content is not null
     */
    public function setContent($content): Response
    {
        if (null !== $content) {
            throw new \LogicException('The content cannot be set on a BinaryFileResponse instance.');
        }

        return $this;
    }

    /**
     * If this is set to true, the file will be unlinked after the request is send
     * Note: If the X-Sendfile header is used, the deleteFileAfterSend setting will not be used.
     *
     * @param bool $shouldDelete
     *
     * @return BinaryFileResponse
     */
    public function deleteFileAfterSend(bool $shouldDelete = true): BinaryFileResponse
    {
        $this->deleteFileAfterSend = $shouldDelete;

        return $this;
    }

    /**
     * Send file stream.
     */
    protected function sendStream(): void
    {
        $out = fopen('php://output', 'wb');
        $file = fopen($this->filename, 'rb');

        stream_copy_to_stream($file, $out);

        fclose($out);
        fclose($file);
    }

    /**
     * Send file per kbps.
     */
    protected function sendChunked(): void
    {
        $ctr = 0;
        $now = microtime(true);
        $file = fopen($this->filename, 'rb');

        while (false !== $part = fgets($file, 1024)) {
            // Throttle output
            ++$ctr;

            if ($ctr / $this->kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep(intval(1e6 * ($ctr / $this->kbps - $elapsed)));
            }

            echo $part;
        }

        fclose($file);
    }
}
