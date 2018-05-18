<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Security;

use Fal\Stick\Security\Jwt;
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
        $this->assertEquals(Jwt::ALG_HS256, $this->jwt->getAlgorithm());
    }

    public function testSetAlgorithm()
    {
        $this->assertEquals(Jwt::ALG_HS384, $this->jwt->setAlgorithm(Jwt::ALG_HS384)->getAlgorithm());
    }

    public function testGetSupportedAlgorithms()
    {
        $this->assertNull($this->jwt->getSupportedAlgorithms());
    }

    public function testSetSupportedAlgorithms()
    {
        $set = [Jwt::ALG_HS384];

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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Key may not be empty
     */
    public function testSetKeyException()
    {
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

    public function decodeEncodeProvider()
    {
        return [
            [
                ['foo' => 'bar'],
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.MekmeDm4rnoablDhbHYq06RqWmaRcjNsdGfHAkXqOk4',
                'foo',
                null,
                Jwt::ALG_HS256,
            ],
            [
                ['foo' => 'bar'],
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJmb28iOiJiYXIifQ.9SHQiEMFss4OWYjjDe4dZSK3i9W_S4UTlZp9uv2cyWmck6o7XIEHmriwuQLAk8-o',
                'foo',
                null,
                Jwt::ALG_HS384,
            ],
            [
                ['foo' => 'bar'],
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJmb28iOiJiYXIifQ.QDZd0vch2-eMuIUc9q3FnfNHwk8aYarxDEJwkKRpkh8l4Nk2Xn3F7x2swe52jO8MO7M39e-Phgqo_WuE8adj9Q',
                'foo',
                null,
                Jwt::ALG_HS512,
            ],
            [
                ['foo' => 'bar'],
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.MrO2rlSt5l1T8kgilGJh41Dv7ETUUmztYjOsjXx2q-cSWRhbv3ciWwhF_vyzvn7GEBUlTjWIIUQapl2dSicTcc_6MkDsIJl9MNTq6-ueeng4ACfX1ehy1rZ2lo3ql3zUEuSdLom9_0p3SXBJrw1xFAGGMW4ymXyRnjnN-qEQA0o',
                openssl_get_privatekey('file:///'.FIXTURE.'key/private.key'),
                openssl_get_publickey('file:///'.FIXTURE.'key/public.pem'),
                Jwt::ALG_RS256,
            ],
            [
                ['foo' => 'bar'],
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzM4NCJ9.eyJmb28iOiJiYXIifQ.T0aslk80zM0VGKWb-ngtx86-67v1LczPRAX-BRqgy1igduj4rwueIxX4Zp4zfZmOGCc43vzE9Codj4JnfokIxlQnnfiRMAOtFeSuGsZZy_s9oYZ6QppBe5RHKivpurlMpWkNwD_c-i0laf4TuSKGjJqaPaESL1kCbSxvmBt2byw',
                openssl_get_privatekey('file:///'.FIXTURE.'key/private.key'),
                openssl_get_publickey('file:///'.FIXTURE.'key/public.pem'),
                Jwt::ALG_RS384,
            ],
            [
                ['foo' => 'bar'],
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJmb28iOiJiYXIifQ.IN0N6Ehp6x89V2Pl4hKuIxJZvr_-Rh_RZPnbEcsZoBiDh3Wp8wehLteecqlE_RNzaaU9GaU9JrzVpyic9ybP1RK1XdxXPoHxokgRMatr-NHpxLks9TnXAnfkt51lqbHUQTVZsui-z15z2_-LlpTatattZL6qzjqTmZvZHoqMkuM',
                openssl_get_privatekey('file:///'.FIXTURE.'key/private.key'),
                openssl_get_publickey('file:///'.FIXTURE.'key/public.pem'),
                Jwt::ALG_RS512,
            ],
        ];
    }

    /** @dataProvider decodeEncodeProvider */
    public function testEncode($raw, $expected, $key1, $key2, $algorithm)
    {
        $res = $this->jwt
            ->setKey($key1, $key2)
            ->setAlgorithm($algorithm)
            ->encode($raw);

        $this->assertEquals($expected, $res);
    }

    /** @dataProvider decodeEncodeProvider */
    public function testDecode($expected, $encoded, $key1, $key2, $algorithm)
    {
        $res = $this->jwt
            ->setKey($key1, $key2)
            ->setAlgorithm($algorithm)
            ->decode($encoded);

        $this->assertEquals($expected, $res);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Algorithm is not supported
     */
    public function testEncodeException()
    {
        $this->jwt->setAlgorithm('foo');
        $this->jwt->encode(['foo' => 'bar']);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Wrong number of segments
     */
    public function testDecodeException()
    {
        $this->jwt->decode('foo.bar');
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Invalid header encoding
     */
    public function testDecodeException2()
    {
        $token = base64_encode(json_encode(null)).'.'.
               base64_encode(json_encode([])).'.'.'foo';

        $this->jwt->decode($token);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Invalid claims encoding
     */
    public function testDecodeException3()
    {
        $token = base64_encode(json_encode([])).'.'.
               base64_encode(json_encode(null)).'.'.'foo';

        $this->jwt->decode($token);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Empty algorithm
     */
    public function testDecodeException4()
    {
        $token = base64_encode(json_encode([])).'.'.
               base64_encode(json_encode([])).'.'.'foo';

        $this->jwt->decode($token);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Algorithm is not allowed
     */
    public function testDecodeException5()
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.MekmeDm4rnoablDhbHYq06RqWmaRcjNsdGfHAkXqOk4';

        $this->jwt->setSupportedAlgorithms([Jwt::ALG_RS256]);
        $this->jwt->decode($token);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Signature verification failed
     */
    public function testDecodeException6()
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.MekmeDm4rnoablDhbHYq06RqWmaRcjNsdGfHAkXqOk4';

        $this->jwt->setKey('foo', 'bar');
        $this->jwt->decode($token);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionRegex /Cannot handle token prior to/
     */
    public function testDecodeException7()
    {
        // token nbf is 2050-10-10
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJuYmYiOjI1NDg5NDc2MDAsImZvbyI6ImJhciJ9.-EJxuzLsbSBnN3AxRhBSwiRIYRlNdEajaq_Da6MAmg8';

        $this->jwt->decode($token);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionRegex /Cannot handle token prior to/
     */
    public function testDecodeException8()
    {
        // token iat is 2050-10-10
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjI1NDg5NDc2MDAsImZvbyI6ImJhciJ9.pGaZoaU1CKffyH87M3cqhcQXyPeeJoV7G7SbC_Af8-8';

        $this->jwt->decode($token);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expired token
     */
    public function testDecodeException9()
    {
        // token exp is 2010-10-10
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjEyODY2NDM2MDAsImZvbyI6ImJhciJ9.tDm7nkF37O7vWz7sMqvKMZScJyORM09gZaJo9SMrd8M';

        $this->jwt->decode($token);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Algorithm is not supported
     */
    public function testDecodeException10()
    {
        // token header[alg] is foo
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJmb28ifQ.eyJmb28iOiJiYXIifQ.YNt7nopdnvWMOjVaI6qyLK6zPvdobh_T8YuDlg9aHhY';
        $this->jwt->decode($token);
    }
}
