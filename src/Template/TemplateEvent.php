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

namespace Fal\Stick\Template;

use Fal\Stick\Event;

class TemplateEvent extends Event
{
    /**
     * Template file.
     *
     * @var string
     */
    private $file;

    /**
     * Mime.
     *
     * @var string
     */
    private $mime;

    /**
     * Data.
     *
     * @var array
     */
    private $data;

    /**
     * Template content.
     *
     * @var string|null
     */
    private $content;

    /**
     * Class constructor.
     *
     * @param string      $file
     * @param array|null  $data
     * @param string|null $mime
     * @param string|null $content
     */
    public function __construct(string $file, array $data = null, string $mime = null, string $content = null)
    {
        $this->file = $file;
        $this->mime = $mime;
        $this->data = (array) $data;
        $this->content = $content;
    }

    /**
     * Returns template file.
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Returns mime.
     *
     * @return string|null
     */
    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Sets data.
     *
     * @param array $data
     *
     * @return TemplateEvent
     */
    public function setData(array $data): TemplateEvent
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Merge data.
     *
     * @param array $data
     *
     * @return TemplateEvent
     */
    public function mergeData(array $data): TemplateEvent
    {
        $this->data = $data + $this->data;

        return $this;
    }

    /**
     * Returns template content.
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set template content.
     *
     * @param string $content
     *
     * @return TemplateEvent
     */
    public function setContent(string $content): TemplateEvent
    {
        $this->content = $content;
        $this->stopPropagation();

        return $this;
    }
}
