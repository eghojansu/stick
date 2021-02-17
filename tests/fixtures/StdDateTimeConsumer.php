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

namespace Fixtures;

class StdDateTimeConsumer
{
    public $date;
    public $std;

    public function __construct(\DateTime $date, \stdClass $std)
    {
        $this->date = $date;
        $this->std = $std;
    }

    public function addStdProperty(string $name = 'foo', $value = 'bar')
    {
        $this->std->$name = $value;

        return $this;
    }

    public static function createStd()
    {
        return new \stdClass();
    }
}
