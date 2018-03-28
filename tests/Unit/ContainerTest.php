<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick as f;
use Fal\Stick\Container;
use Fal\Stick\Test\fixture\CommonClass;
use Fal\Stick\Test\fixture\ControllerClass;
use Fal\Stick\Test\fixture\DepA;
use Fal\Stick\Test\fixture\DepDateTime;
use Fal\Stick\Test\fixture\DepDepAIndB;
use Fal\Stick\Test\fixture\IndA;
use Fal\Stick\Test\fixture\IndB;
use Fal\Stick\Test\fixture\ResourceClass;
use Fal\Stick\Test\fixture\UserEntity;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    public function tearDown()
    {
        $this->container->clears(explode('|', Container::GLOBALS));
    }

    public function testSetOnUpdate()
    {
        $this->container->setOnUpdate(function($key, $val, $container) {
            if ($key === 'foo') {
                $container['foo'] = 'baz';
            }
        });
        $this->container->set('foo', 'bar');
        $this->assertEquals('baz', $this->container->get('foo'));
    }

    public function testHeaders()
    {
        $this->container->headers(['Location'=>'/foo','Content-Length'=>'0']);
        $this->assertEquals(['/foo'], $this->container->getHeader('Location'));
        $this->assertEquals(['0'], $this->container->getHeader('Content-Length'));
    }

    public function testHeader()
    {
        $this->container->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->container->getHeader('Location'));
    }

    public function testGetHeaders()
    {
        $this->container->header('Location', '/foo');
        $this->assertEquals(['Location'=>['/foo']], $this->container->getHeaders());
    }

    public function testGetHeader()
    {
        $this->container->header('Foo', 'foo');
        $this->container->header('bar', 'bar');
        $this->assertEquals(['foo'], $this->container->getHeader('foo'));
        $this->assertEquals(['foo'], $this->container->getHeader('Foo'));
        $this->assertEquals(['bar'], $this->container->getHeader('bar'));
    }

    public function testRemoveHeader()
    {
        $this->container->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->container->getHeader('Location'));
        $this->container->removeHeader('Location');
        $this->assertEmpty($this->container['RHEADERS']);

        $this->container->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->container->getHeader('location'));
        $this->container->removeHeader();
        $this->assertEmpty($this->container['RHEADERS']);
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->container->call('trim', ' foo '));
        $this->assertEquals('foobar', $this->container->call(CommonClass::class.'->prefixFoo', 'bar'));
        $this->assertEquals('quxquux', $this->container->call(CommonClass::class.'::prefixQux', 'quux'));
        $this->assertEquals('foobarbaz', $this->container->call(function(...$args) {
            return implode('', $args);
        }, ['foo','bar','baz']));
    }

    public function testService()
    {
        $this->assertEquals($this->container, $this->container->service('container'));
        $this->assertEquals($this->container, $this->container->service(Container::class));

        $inda = $this->container->service(IndA::class);
        $this->assertInstanceof(IndA::class, $inda);

        // service
        $this->container->set('SERVICE.foo', DepA::class);
        $foo = $this->container->service('foo');
        $this->assertInstanceof(DepA::class, $foo);
        $this->assertEquals($foo, $this->container->service(DepA::class));

        // service with placeholder parameter
        $this->container->set('bar', 'baz');
        $this->container->set('SERVICE.bar', [
            'class' => DepDepAIndB::class,
            'params' => [
                'depa' => '%foo%',
                'indb' => IndB::class,
                'foo' => '%bar%'
            ],
        ]);
        $bar = $this->container->service('bar');
        $this->assertInstanceof(DepDepAIndB::class, $bar);
        $this->assertEquals($foo, $bar->depa);
        $this->assertEquals('baz', $bar->foo);

        // Service with global class dependency
        $depdt = $this->container->service(DepDateTime::class);
        $this->assertInstanceof(DepDateTime::class, $depdt);

        $dt = $this->container->service(\DateTime::class);
        $this->assertInstanceof(\DateTime::class, $dt);

        // service with closure constructor
        $this->container->set('SERVICE.closure', function(Container $container) {
            return new \DateTime();
        });
        $closure = $this->container->service('closure');
        $this->assertInstanceof(\DateTime::class, $closure);
    }

    public function testRef()
    {
        $foo = $this->container->ref('foo');
        $this->assertNull($foo);

        $foo =& $this->container->ref('foo');
        $foo = 'bar';
        $this->assertEquals('bar', $this->container->get('foo'));

        $bar =& $this->container->ref('bar');
        $bar = new \StdClass;
        $bar->foo = 'baz';
        $this->assertEquals('baz', $this->container->get('bar.foo'));
        $this->assertNull($this->container->get('bar.baz'));
    }

    public function testGet()
    {
        $this->assertNull($this->container->get('foo'));
        $this->assertEquals('bar', $this->container->get('foo', 'bar'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->container->set('foo', 'bar')->get('foo'));
        $this->assertEquals('bar', $this->container->set('COOKIE.foo', 'bar')->get('COOKIE.foo'));
        $this->assertEquals('bar', $this->container->set('POST.foo', 'bar')->get('POST.foo'));
        $this->assertEquals('bar', $this->container->get('REQUEST.foo'));

        // update timezone
        $this->container->set('TZ', 'Asia/Jakarta');
        $this->assertEquals('Asia/Jakarta', date_default_timezone_get());

        // serializer
        $this->container->set('SERIALIZER', 'php');
        $raw = ['foo'=>'bar'];
        $serialized = serialize($raw);
        $this->assertEquals($serialized, f\serialize($raw));
        $this->assertEquals($raw, f\unserialize($serialized));

        // URI
        $this->container->set('URI', '/foo');
        $this->assertEquals('/foo', $_SERVER['REQUEST_URI']);

        // JAR
        $this->assertEquals('foo.com', $this->container->set('JAR.domain', 'foo.com')->get('JAR.domain'));
        $this->assertEquals(true, $this->container->set('JAR.secure', true)->get('JAR.secure'));
        $this->assertEquals('foo.com', $this->container->set('JAR', $this->container['JAR'])->get('JAR.domain'));

        // SET COOKIE with domain
        $this->container->set('COOKIE.domain', 'foo');
        $this->assertEquals('foo', $_COOKIE['domain']);
        $this->assertContains('Domain=foo.com', $this->container->getHeader('Set-Cookie')[1]);
        $this->assertContains('Secure', $this->container->getHeader('Set-Cookie')[1]);

        // Object with getter and setter
        $obj = new UserEntity;
        $obj->setFirstName('foo');
        $this->container->set('user', $obj);
        $this->assertEquals('foo', $this->container->get('user.firstname'));
    }

    public function testExists()
    {
        $this->assertFalse($this->container->exists('foo'));
        $this->assertTrue($this->container->set('foo', 'bar')->exists('foo'));
    }

    public function testClear()
    {
        $this->assertFalse($this->container->set('foo', 'bar')->clear('foo')->exists('foo'));

        $this->assertFalse($this->container->exists('foo.bar'));
        $this->container->clear('foo.bar');
        $this->assertFalse($this->container->exists('foo.bar'));

        // obj remove
        $this->container->set('foo', new \StdClass);
        $this->assertFalse($this->container->exists('foo.bar'));
        $this->container->set('foo.bar', 'baz');
        $this->assertEquals('baz', $this->container->get('foo.bar'));
        $this->container->clear('foo.bar');
        $this->assertFalse($this->container->exists('foo.bar'));

        // reset
        $init = $this->container['URI'];

        // change
        $this->container['URI'] = '/foo';
        $this->assertNotEquals($init, $this->container['URI']);

        unset($this->container['URI']);
        $this->assertEquals($init, $this->container['URI']);

        // REQUEST
        $this->container->set('GET.foo', 'bar');
        $this->assertEquals('bar', $this->container['REQUEST.foo']);
        $this->container->clear('GET.foo');
        $this->assertNull($this->container['REQUEST.foo']);

        // SESSION
        $this->container->set('SESSION.foo', 'bar');
        $this->assertEquals('bar', $this->container['SESSION.foo']);
        $this->container->clear('SESSION.foo');
        $this->assertNull($this->container['SESSION.foo']);

        // SERVICE
        $this->container->set('SERVICE.foo', CommonClass::class);
        $this->assertEquals(['class'=>CommonClass::class, 'keep'=>true], $this->container->get('SERVICE.foo'));
        $this->container->clear('SERVICE.foo');
        $this->assertNull($this->container->get('SERVICE.foo'));

        // COOKIE
        $this->container->set('COOKIE.foo', 'bar');
        $this->assertContains('foo=bar', $this->container->getHeader('Set-Cookie')[0]);
        $this->assertTrue(isset($_COOKIE['foo']));
        $this->container->clear('COOKIE.foo');
        $this->assertContains('foo=bar', $this->container->getHeader('Set-Cookie')[0]);
        $this->assertFalse(isset($_COOKIE['foo']));
    }

    public function testSets()
    {
        $this->assertEquals('bar', $this->container->sets(['foo'=>'bar'], 'baz.')->get('baz.foo'));
    }

    public function testClears()
    {
        $this->container->sets(['foo'=>'bar','bar'=>'foo']);
        $this->container->clears(['foo','bar']);

        $this->assertFalse($this->container->exists('foo'));
        $this->assertFalse($this->container->exists('bar'));
    }

    public function testFlash()
    {
        $this->assertEquals('bar', $this->container->set('foo','bar')->flash('foo'));
        $this->assertNull($this->container->get('foo'));
    }

    public function testCopy()
    {
        $this->assertEquals('bar', $this->container->set('foo', 'bar')->copy('foo', 'bar')->get('bar'));
    }

    public function testConcat()
    {
        $this->assertEquals('barbaz', $this->container->set('foo', 'bar')->concat('foo', 'baz'));
        $this->assertEquals('barbaz', $this->container->concat('foo', 'baz', '', true)->get('foo'));
    }

    public function testFlip()
    {
        $this->assertEquals(['baz'=>'bar'], $this->container->set('foo', ['bar'=>'baz'])->flip('foo'));
        $this->assertEquals(['baz'=>'bar'], $this->container->flip('foo', true)->get('foo'));
    }

    public function testPush()
    {
        $this->assertEquals(['bar'], $this->container->set('foo', [])->push('foo', 'bar')->get('foo'));
    }

    public function testPop()
    {
        $this->assertEquals('bar', $this->container->set('foo', ['bar'])->pop('foo'));
    }

    public function testUnshift()
    {
        $this->assertEquals(['bar'], $this->container->set('foo', [])->unshift('foo', 'bar')->get('foo'));
    }

    public function testShift()
    {
        $this->assertEquals('bar', $this->container->set('foo', ['bar'])->shift('foo'));
    }

    public function testMerge()
    {
        $this->assertEquals(['foo','bar'], $this->container->set('foo', ['foo'])->merge('foo', ['bar']));
        $this->assertEquals(['foo','bar'], $this->container->merge('foo', ['bar'], true)->get('foo'));
        $this->assertEquals(['bar'], $this->container->merge('bar', ['bar'], true)->get('bar'));
    }

    public function testExtend()
    {
        $this->assertEquals(['foo'=>'bar'], $this->container->set('foo', [])->extend('foo', ['foo'=>'bar']));
        $this->assertEquals(['foo'=>'bar'], $this->container->extend('foo', ['foo'=>'bar'], true)->get('foo'));
    }

    public function testOffsetget()
    {
        $this->container['foo'] = 'bar';
        $this->assertEquals('bar', $this->container['foo']);
    }

    public function testOffsetset()
    {
        $this->container['foo'] = 'bar';
        $this->assertEquals('bar', $this->container['foo']);
    }

    public function testOffsetexists()
    {
        $this->assertFalse(isset($this->container['foo']));
    }

    public function testOffsetunset()
    {
        unset($this->container['foo']);
        $this->assertFalse(isset($this->container['foo']));
    }

    public function testMagicget()
    {
        $this->container->foo = 'bar';
        $this->assertEquals('bar', $this->container->foo);
    }

    public function testMagicset()
    {
        $this->container->foo = 'bar';
        $this->assertEquals('bar', $this->container->foo);
    }

    public function testMagicexists()
    {
        $this->assertFalse(isset($this->container->foo));
    }

    public function testMagicunset()
    {
        unset($this->container->foo);
        $this->assertFalse(isset($this->container->foo));
    }
}
