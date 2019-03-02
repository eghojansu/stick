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

namespace Fal\Stick\Web\Security\Event;

use Fal\Stick\Web\Security\Auth;

/**
 * Vote event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class VoteEvent extends AuthEvent
{
    /**
     * @var bool
     */
    protected $granted;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * Class constructor.
     *
     * @param Auth       $auth
     * @param bool       $granted
     * @param mixed      $data
     * @param array|null $attributes
     */
    public function __construct(Auth $auth, bool $granted, $data = null, array $attributes = null)
    {
        parent::__construct($auth);

        $this->granted = $granted;
        $this->data = $data;
        $this->attributes = $attributes;
    }

    /**
     * Returns true if granted.
     *
     * @return bool
     */
    public function isGranted(): bool
    {
        return $this->granted;
    }

    /**
     * Returns data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns attributes.
     *
     * @return array|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }
}
