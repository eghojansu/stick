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
 * Binary file response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class BinaryFileResponse
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var string
     */
    protected $mime;

    /**
     * @var bool
     */
    protected $inline;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $filepath;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var int
     */
    protected $expire;

    /**
     * Class constructor.
     *
     * @param Fw          $fw
     * @param string|null $filename
     * @param int         $expire
     * @param string|null $mime
     * @param bool        $inline
     */
    public function __construct(Fw $fw, string $filename = null, int $expire = 0, string $mime = null, bool $inline = false)
    {
        $this->fw = $fw;
        $this->filename = $filename;
        $this->expire = $expire;
        $this->mime = $mime;
        $this->inline = $inline;
    }

    /**
     * Allow call as function.
     *
     * @return int
     */
    public function __invoke()
    {
        return $this->send();
    }

    /**
     * Returns filepath to send.
     *
     * @return string|null
     */
    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    /**
     * Sets filepath to send.
     *
     * @param string $filepath
     *
     * @return BinaryFileResponse
     */
    public function setFilepath(string $filepath): BinaryFileResponse
    {
        if (!is_file($filepath)) {
            throw new \LogicException(sprintf('File not exists: %s.', $filepath));
        }

        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Returns file content.
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets file content.
     *
     * @param string $content
     *
     * @return BinaryFileResponse
     */
    public function setContent(string $content): BinaryFileResponse
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Send file.
     *
     * @return int
     */
    public function send(): int
    {
        if (null === $this->content && null === $this->filepath) {
            throw new \LogicException('Response has no content.');
        }

        $name = $this->filename ?? basename($this->filepath ?? 'response.txt');
        $mime = $this->mime ?? Mime::type($name);
        $size = $this->content ? strlen($this->content) : filesize($this->filepath);
        $disposition = $this->inline ? 'inline' : 'attachment';

        $this->fw->expire($this->expire);
        $this->fw->hset('Content-Disposition', $disposition.'; filename="'.$name.'"');
        $this->fw->hset('Accept-Ranges', 'bytes');
        $this->fw->hset('Content-Type', $mime);
        $this->fw->hset('Content-Length', $size);

        $this->fw->sendHeaders();

        if ($this->content) {
            echo $this->content;
        } else {
            readfile($this->filepath);
        }

        $this->fw->rem('RESPONSE');

        return $size;
    }
}
