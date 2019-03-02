<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 23:11
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\FileBag;
use PHPUnit\Framework\TestCase;

class FileBagTest extends TestCase
{
    private $bag;

    public function setup()
    {
        $this->bag = new FileBag();
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($expected, $value)
    {
        $this->bag->set('foo', $value);

        $this->assertEquals($expected, $this->bag->get('foo'));
    }

    public function testSetException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('An uploaded file must be an array.');

        $this->bag->set('foo', 'bar');
    }

    public function setProvider()
    {
        return array(
            array(array(), array()),
            array(
                null,
                array('name' => 'bar', 'type' => null, 'size' => null, 'tmp_name' => null, 'error' => UPLOAD_ERR_NO_FILE),
            ),
            array(
                array('name' => 'bar', 'type' => null, 'size' => null, 'tmp_name' => null, 'error' => null),
                array('name' => 'bar', 'type' => null, 'size' => null, 'tmp_name' => null, 'error' => null),
            ),
            array(
                array(array('name' => 'bar', 'type' => null, 'size' => null, 'tmp_name' => null, 'error' => null)),
                array('name' => array('bar'), 'type' => array(null), 'size' => array(null), 'tmp_name' => array(null), 'error' => array(null)),
            ),
        );
    }
}
