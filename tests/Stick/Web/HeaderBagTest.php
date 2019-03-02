<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 23:29
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\HeaderBag;
use PHPUnit\Framework\TestCase;

class HeaderBagTest extends TestCase
{
    private $bag;

    public function setup()
    {
        $this->bag = new HeaderBag();
    }

    public function testFirst()
    {
        $this->assertNull($this->bag->first('foo'));
    }

    public function testSet()
    {
        $this->bag->set('Foo', 'bar');
        $this->bag->set('BAR', array('baz'));
        $this->bag->set('QUX_QUUX', array('baz'));
        $this->bag->set('FOO_FOO', array('baz'));

        $expected = array(
            'Foo' => array('bar'),
            'Bar' => array('baz'),
            'Qux-Quux' => array('baz'),
            'Foo-Foo' => array('baz'),
        );

        $this->assertEquals($expected, $this->bag->all());
    }

    public function testUpdate()
    {
        $this->bag->set('Foo', 'bar');

        $this->assertEquals(array('foo'), $this->bag->update('FOO', 'foo')->get('Foo'));
    }
}
