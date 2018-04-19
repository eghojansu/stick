<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Security;

use Fal\Stick\Security\SimpleUser;
use Fal\Stick\Security\SimpleUserTransformer;
use PHPUnit\Framework\TestCase;

class SimpleUserTransformerTest extends TestCase
{
    private $transfomer;

    public function setUp()
    {
        $this->transfomer = new SimpleUserTransformer();
    }

    public function tearDown()
    {
        error_clear_last();
    }

    public function testTransform()
    {
        $expected = new SimpleUser('1','foo','bar');
        $res = $this->transfomer->transform(['id'=>'1','username'=>'foo','password'=>'bar']);

        $this->assertEquals($expected, $res);

        $expected = new SimpleUser('1','foo','bar');
        $res = $this->transfomer->transform(['username'=>'foo','password'=>'bar','id'=>'1']);

        $this->assertEquals($expected, $res);

        $expected = new SimpleUser('1','foo','bar',['qux'],true);
        $res = $this->transfomer->transform(['id'=>'1','username'=>'foo','password'=>'bar','roles'=>['qux'],'expired'=>true]);

        $this->assertEquals($expected, $res);
    }
}
