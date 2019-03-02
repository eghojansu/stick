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

namespace Fal\Stick\Test\Web\Security;

use Fal\Stick\Web\Security\Jwt;
use PHPUnit\Framework\TestCase;

class JwtTest extends TestCase
{
    private $jwt;

    public function setUp()
    {
        $this->jwt = new Jwt('foo');
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
     * @dataProvider encodeDecodeProvider
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
     * @dataProvider encodeDecodeProvider
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
     * @dataProvider decodeExceptionProvider
     */
    public function testDecodeException($expected, $token, $exception = 'UnexpectedValueException', $calls = null)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($expected);

        foreach ($calls ?? array() as $call => $arguments) {
            $this->jwt->$call(...$arguments);
        }

        $this->jwt->decode($token);
    }

    public function encodeDecodeProvider()
    {
        $private = openssl_get_privatekey('file:///'.TEST_FIXTURE.'ssh/private.key');
        $public = openssl_get_publickey('file:///'.TEST_FIXTURE.'ssh/public.pem');

        return array(
            array(
                array('foo' => 'bar'),
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.MekmeDm4rnoablDhbHYq06RqWmaRcjNsdGfHAkXqOk4',
                'foo',
                null,
                'HS256',
            ),
            array(
                array('foo' => 'bar'),
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJmb28iOiJiYXIifQ.9SHQiEMFss4OWYjjDe4dZSK3i9W_S4UTlZp9uv2cyWmck6o7XIEHmriwuQLAk8-o',
                'foo',
                null,
                'HS384',
            ),
            array(
                array('foo' => 'bar'),
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJmb28iOiJiYXIifQ.QDZd0vch2-eMuIUc9q3FnfNHwk8aYarxDEJwkKRpkh8l4Nk2Xn3F7x2swe52jO8MO7M39e-Phgqo_WuE8adj9Q',
                'foo',
                null,
                'HS512',
            ),
            array(
                array('foo' => 'bar'),
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.MrO2rlSt5l1T8kgilGJh41Dv7ETUUmztYjOsjXx2q-cSWRhbv3ciWwhF_vyzvn7GEBUlTjWIIUQapl2dSicTcc_6MkDsIJl9MNTq6-ueeng4ACfX1ehy1rZ2lo3ql3zUEuSdLom9_0p3SXBJrw1xFAGGMW4ymXyRnjnN-qEQA0o',
                $private,
                $public,
                'RS256',
            ),
            array(
                array('foo' => 'bar'),
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzM4NCJ9.eyJmb28iOiJiYXIifQ.T0aslk80zM0VGKWb-ngtx86-67v1LczPRAX-BRqgy1igduj4rwueIxX4Zp4zfZmOGCc43vzE9Codj4JnfokIxlQnnfiRMAOtFeSuGsZZy_s9oYZ6QppBe5RHKivpurlMpWkNwD_c-i0laf4TuSKGjJqaPaESL1kCbSxvmBt2byw',
                $private,
                $public,
                'RS384',
            ),
            array(
                array('foo' => 'bar'),
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJmb28iOiJiYXIifQ.IN0N6Ehp6x89V2Pl4hKuIxJZvr_-Rh_RZPnbEcsZoBiDh3Wp8wehLteecqlE_RNzaaU9GaU9JrzVpyic9ybP1RK1XdxXPoHxokgRMatr-NHpxLks9TnXAnfkt51lqbHUQTVZsui-z15z2_-LlpTatattZL6qzjqTmZvZHoqMkuM',
                $private,
                $public,
                'RS512',
            ),
        );
    }

    public function decodeExceptionProvider()
    {
        return array(
            array(
                'Wrong number of segments.',
                'foo.bar',
            ),
            array(
                'Invalid header encoding.',
                base64_encode(json_encode(null)).'.'.base64_encode(json_encode(array())).'.'.'foo',
            ),
            array(
                'Invalid claims encoding.',
                base64_encode(json_encode(array())).'.'.base64_encode(json_encode(null)).'.'.'foo',
            ),
            array(
                'Empty algorithm.',
                base64_encode(json_encode(array())).'.'.base64_encode(json_encode(array())).'.'.'foo',
                'LogicException',
            ),
            array(
                'Algorithm is not allowed.',
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.MekmeDm4rnoablDhbHYq06RqWmaRcjNsdGfHAkXqOk4',
                'LogicException',
                array(
                    'setSupportedAlgorithms' => array(array('RS256')),
                ),
            ),
            array(
                'Signature verification failed',
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.MekmeDm4rnoablDhbHYq06RqWmaRcjNsdGfHAkXqOk4',
                'UnexpectedValueException',
                array(
                    'setKey' => array('foo', 'bar'),
                ),
            ),
            array(
                'Token can not be used right now.',
                // token nbf is 2050-10-10
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJuYmYiOjI1NDg5NDc2MDAsImZvbyI6ImJhciJ9.-EJxuzLsbSBnN3AxRhBSwiRIYRlNdEajaq_Da6MAmg8',
                'RuntimeException',
            ),
            array(
                'Token can not be used right now.',
                // token iat is 2050-10-10
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjI1NDg5NDc2MDAsImZvbyI6ImJhciJ9.pGaZoaU1CKffyH87M3cqhcQXyPeeJoV7G7SbC_Af8-8',
                'RuntimeException',
            ),
            array(
                'Token expired.',
                // token iat is 2050-10-10
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjEyODY2NDM2MDAsImZvbyI6ImJhciJ9.tDm7nkF37O7vWz7sMqvKMZScJyORM09gZaJo9SMrd8M',
                'RuntimeException',
            ),
            array(
                'Algorithm is not supported.',
                // token header[alg] is foo
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJmb28ifQ.eyJmb28iOiJiYXIifQ.YNt7nopdnvWMOjVaI6qyLK6zPvdobh_T8YuDlg9aHhY',
                'LogicException',
            ),
        );
    }
}
