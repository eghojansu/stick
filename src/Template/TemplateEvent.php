<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public function __construct($file, array $data = null, $mime = null, $content = null)
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
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns mime.
     *
     * @return string|null
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function getData()
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
    public function setData(array $data)
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
    public function mergeData(array $data)
    {
        $this->data = $data + $this->data;

        return $this;
    }

    /**
     * Returns template content.
     *
     * @return string|null
     */
    public function getContent()
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
    public function setContent($content)
    {
        $this->content = $content;
        $this->stopPropagation();

        return $this;
    }
}
