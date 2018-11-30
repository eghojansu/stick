<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 30, 2018 22:45
 */

namespace Fal\Stick;

/**
 * Application framework environment setup, *totally optional*.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class Kernel
{
    /**
     * @var Fw
     */
    private $fw;

    /**
     * @var string
     */
    private $environment;

    /**
     * Class constructor.
     *
     * @param string $environment
     */
    public function __construct(string $environment = 'prod')
    {
        $this->fw = Fw::createFromGlobals();
        $this->fw->setRule('kernel', $this);
        $this->setEnvironment($environment);
        $this->boot();
    }

    /**
     * Static instance creator.
     *
     * @param string $environment
     *
     * @return Kernel
     */
    public static function create(string $environment = 'prod'): Kernel
    {
        return new static($environment);
    }

    /**
     * Returns framework instance.
     *
     * @return Fw
     */
    public function getFw(): Fw
    {
        return $this->fw;
    }

    /**
     * Returns environment name.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Sets environment name.
     *
     * @param string $environment
     *
     * @return Kernel
     */
    public function setEnvironment(string $environment): Kernel
    {
        $this->environment = $environment;
        $this->fw['DEBUG'] = 'prod' !== $environment;

        return $this;
    }

    /**
     * Start listening.
     */
    public function run(): void
    {
        $this->fw->run();
    }

    /**
     * Descendant class can override this method to add their construction logic.
     */
    protected function boot(): void
    {
        // To be overriden by descendant
    }
}
