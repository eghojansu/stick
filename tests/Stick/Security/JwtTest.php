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

namespace Fal\Stick\Test\Security;

use Fal\Stick\Security\Jwt;
use Fal\Stick\TestSuite\MyTestCase;

class JwtTest extends MyTestCase
{
    protected function createInstance()
    {
        return new Jwt('foo');
    }

    public function testGetAlgorithm()
    {
        $this->assertEquals('HS256', $this->jwt->getAlgorithm());
    }

    public function testSetAlgorithm()
    {
        $this->assertEquals('HS384', $this->jwt->setAlgorithm('HS384')->getAlgorithm());
    }

    public function testGetSupportedAlgorithms()
    {
        $this->assertNull($this->jwt->getSupportedAlgorithms());
    }

    public function testSetSupportedAlgorithms()
    {
        $set = array('HS384');

        $this->assertEquals($set, $this->jwt->setSupportedAlgorithms($set)->getSupportedAlgorithms());
    }

    public function testGetEncodeKey()
    {
        $this->assertEquals('foo', $this->jwt->getEncodeKey());
    }

    public function testGetDecodeKey()
    {
        $this->assertEquals('foo', $this->jwt->getDecodeKey());
    }

    public function testSetKey()
    {
        $this->jwt->setKey('bar', 'baz');
        $this->assertEquals('bar', $this->jwt->getEncodeKey());
        $this->assertEquals('baz', $this->jwt->getDecodeKey());
    }

    public function testSetKeyException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Key may not be empty.');

        $this->jwt->setKey(null);
    }

    public function testGetLeeway()
    {
        $this->assertEquals(0, $this->jwt->getLeeway());
    }

    public function testSetLeeway()
    {
        $this->assertEquals(30, $this->jwt->setLeeway(30)->getLeeway());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\JwtProvider::encodeDecode
     */
    public function testEncode($raw, $expected, $key1, $key2, $algorithm)
    {
        $res = $this->jwt
            ->setKey($key1, $key2)
            ->setAlgorithm($algorithm)
            ->encode($raw);

        $this->assertEquals($expected, $res);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\JwtProvider::encodeDecode
     */
    public function testDecode($expected, $encoded, $key1, $key2, $algorithm)
    {
        $res = $this->jwt
            ->setKey($key1, $key2)
            ->setAlgorithm($algorithm)
            ->decode($encoded);

        $this->assertEquals($expected, $res);
    }

    public function testEncodeDecode()
    {
        $this->jwt->setKey('foo');

        $encoded = $this->jwt->encode(array('foo' => 'bar'));
        $decoded = $this->jwt->decode($encoded);

        $this->assertTrue(is_string($encoded));
        $this->assertEquals(array('foo' => 'bar'), $decoded);
    }

    public function testEncodeException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Algorithm is not supported.');

        $this->jwt->setAlgorithm('foo');
        $this->jwt->encode(array('foo' => 'bar'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\JwtProvider::decodeException
     */
    public function testDecodeException($expected, $token, $exception = 'UnexpectedValueException', $calls = null)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($expected);

        foreach ($calls ?: array() as $call => $arguments) {
            $this->jwt->$call(...$arguments);
        }

        $this->jwt->decode($token);
    }
}
