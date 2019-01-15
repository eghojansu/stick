<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:29
 */

namespace Fal\Stick\Security\Event;

use Fal\Stick\Security\Auth;
use Fal\Stick\Security\AuthEvent;

class VoteEvent extends AuthEvent
{
    protected $data;
    protected $attributes;
    protected $granted;

    public function __construct(Auth $auth, $data, $attributes, $granted = true)
    {
        parent::__construct($auth);

        $this->data = $data;
        $this->attributes = $attributes;
        $this->granted = $granted;
    }

    public function isGranted()
    {
        return $this->granted;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}
