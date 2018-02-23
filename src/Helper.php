<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * App helper
 */
class Helper
{
    /** @var array */
    protected $options;

    /**
     * Class constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (empty($options['serializer'])) {
            $options['serializer'] = extension_loaded('igbinary') ? 'igbinary' : 'php';
        }

        $this->options = $options;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get option
     *
     *  @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : null;
    }

    /**
     * Set option
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setOption(string $name, $value): Helper
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Return string representation of PHP value
     *
     * @param mixed $arg
     * @return string
     */
    public function serialize($arg): string
    {
        switch ($this->options['serializer']) {
            case 'igbinary':
                return igbinary_serialize($arg);
            default:
                return serialize($arg);
        }
    }

     /**
     * Return PHP value derived from string
     *
     * @param mixed $arg
     * @return mixed
     */
    public function unserialize($arg)
    {
        switch ($this->options['serializer']) {
            case 'igbinary':
                return igbinary_unserialize($arg);
            default:
                return unserialize($arg);
        }
    }
}
