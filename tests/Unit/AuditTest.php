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

use Fal\Stick\Audit;
use PHPUnit\Framework\TestCase;

class AuditTest extends TestCase
{
    private $audit;

    public function setUp()
    {
        $this->audit = new Audit();
    }

    public function testGetSetMessage()
    {
        $this->assertEquals('Foo', $this->audit->setMessage('foo', 'Foo')->getMessage('foo'));
    }

    public function testGetSetMessages()
    {
        $this->assertContains('Foo', $this->audit->setMessages(['foo'=>'Foo'])->getMessages());
    }

    public function testSetRule()
    {
        $this->audit->setRule('foo', function() {
            return true;
        });
        $this->audit->setMessage('foo', 'Foo {arg0}');

        $rules = ['bar'=>'foo'];
        $data  = ['bar'=>'baz'];

        $this->audit->validate($data, $rules);

        $this->assertEquals($data, $this->audit->getValidated());
    }

    public function testSetGetRules()
    {
        $this->assertEquals([], $this->audit->getRules());
        $this->assertEquals(['foo'=>'bar'], $this->audit->setRules(['foo'=>'bar'])->getRules());
    }

    public function testSetGetData()
    {
        $this->assertEquals([], $this->audit->getData());
        $this->assertEquals(['foo'=>'bar'], $this->audit->setData(['foo'=>'bar'])->getData());
    }

    public function testSuccess()
    {
        $this->audit->validate();
        $this->assertTrue($this->audit->success());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Run validate method first
     */
    public function testSuccessException()
    {
        $this->audit->success();
    }

    public function testFail()
    {
        $this->audit->validate([
            'foo'=>'bar'
        ], [
            'foo'=>'is_int'
        ]);
        $this->assertTrue($this->audit->fail());
    }

    public function testGetErrors()
    {
        $this->assertEquals([], $this->audit->getErrors());
    }

    public function testGetValidated()
    {
        $this->assertEquals([], $this->audit->getValidated());
    }

    public function testValidate()
    {
        $this->audit->setRule('foo', function($val, $foo, $_audit = null) {
            return $val ? $_audit['id'] . $val . $foo : false;
        });
        $this->audit->setMessage('foo', 'Foo {key} {arg1}');

        $this->audit->setRules([
            'foo'=>'trim|required|foo[baz]',
            'email'=>'email[false]',
        ]);

        // set via method
        $this->audit->validate([
            'foo'=>'  bar  ',
            'email'=>'foo@bar.com'
        ]);
        $this->assertTrue($this->audit->success());
        $this->assertEquals(['foo'=>'foobarbaz','email'=>'foo@bar.com'], $this->audit->getValidated());

        // expect error
        $this->audit->setRules([
            'foo'=>'foo[baz]',
            'email'=>'email[false]',
        ]);
        $this->audit->validate([]);
        $this->assertFalse($this->audit->success());
        $this->assertEquals([], $this->audit->getValidated());
        $this->assertEquals(['foo'=>['Foo foo baz'],'email'=>['Invalid data']], $this->audit->getErrors());
    }

    public function testValidateConstruct()
    {
        $audit = new Audit([
            'foo' => function($val, $foo, $_audit = null) {
                return $val ? $_audit['id'] . $val . $foo : false;
            }
        ], [
            'foo' => 'Foo {key} {arg1}'
        ]);

        $audit->validate([
            'foo'=>'  bar  ',
        ], [
            'foo'=>'trim|required|foo[baz]',
        ]);

        $this->assertTrue($audit->success());
        $this->assertEquals(['foo'=>'foobarbaz'], $audit->getValidated());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid rule declaration: invalid-rule
     */
    public function testValidateException1()
    {
        $this->audit->validate([], [
            'foo' => 'invalid-rule'
        ]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Rule not found: foo
     */
    public function testValidateException2()
    {
        $this->audit->validate([], [
            'foo' => 'foo'
        ]);
    }

    /**
     * @expectedException ArgumentCountError
     * @expectedExceptionMessage Validator foo expect at least 2 parameters, 1 given
     */
    public function testValidateException3()
    {
        $this->audit->setRule('foo', function($val, $foo) {
            return $val . $foo;
        });
        $this->audit->validate([], [
            'foo' => 'foo'
        ]);
    }

    public function testRequired()
    {
        $this->assertFalse($this->audit->required(null));
        $this->assertFalse($this->audit->required(''));

        $this->assertTrue($this->audit->required('foo'));
        $this->assertTrue($this->audit->required(1));
        $this->assertTrue($this->audit->required(0));
    }

    public function testUrl()
    {
        $this->assertFalse($this->audit->url('http://www.example.com/space here.html'));

        $this->assertTrue($this->audit->url('http://www.example.com/space%20here.html'));
    }

    public function testEmail()
    {
        $this->assertFalse($this->audit->email('Abc.google.com', false));
        $this->assertFalse($this->audit->email('Abc.@google.com', false));
        $this->assertFalse($this->audit->email('Abc..123@google.com', false));
        $this->assertFalse($this->audit->email('A@b@c@google.com', false));
        $this->assertFalse($this->audit->email('a"b(c)d,e:f;g<h>i[j\k]l@google.com', false));
        $this->assertFalse($this->audit->email('just"not"right@google.com', false));
        $this->assertFalse($this->audit->email('this is"not\allowed@google.com', false));
        $this->assertFalse($this->audit->email('this\ still\"not\\allowed@google.com', false));

        $this->assertTrue($this->audit->email('niceandsimple@google.com', false));
        $this->assertTrue($this->audit->email('very.common@google.com', false));
        $this->assertTrue($this->audit->email('a.little.lengthy.but.fine@google.com', false));
        $this->assertTrue($this->audit->email('disposable.email.with+symbol@google.com', false));
        $this->assertTrue($this->audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]', false));
        $this->assertTrue($this->audit->email('"very.unusual.@.unusual.com"@google.com', false));
        $this->assertTrue($this->audit->email('!#$%&\'*+-/=?^_`{}|~@google.com', false));
        $this->assertTrue($this->audit->email('""@google.com', false));

        // with domain verification (require internet connection)
        $this->assertFalse($this->audit->email('Abc.google.com'));
        $this->assertFalse($this->audit->email('Abc.@google.com'));
        $this->assertFalse($this->audit->email('Abc..123@google.com'));
        $this->assertFalse($this->audit->email('A@b@c@google.com'));
        $this->assertFalse($this->audit->email('a"b(c)d,e:f;g<h>i[j\k]l@google.com'));
        $this->assertFalse($this->audit->email('just"not"right@google.com'));
        $this->assertFalse($this->audit->email('this is"not\allowed@google.com'));
        $this->assertFalse($this->audit->email('this\ still\"not\\allowed@google.com'));

        $this->assertTrue($this->audit->email('niceandsimple@google.com'));
        $this->assertTrue($this->audit->email('very.common@google.com'));
        $this->assertTrue($this->audit->email('a.little.lengthy.but.fine@google.com'));
        $this->assertTrue($this->audit->email('disposable.email.with+symbol@google.com'));
        $this->assertTrue($this->audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]', false));
        $this->assertTrue($this->audit->email('"very.unusual.@.unusual.com"@google.com'));
        $this->assertTrue($this->audit->email('!#$%&\'*+-/=?^_`{}|~@google.com'));
        $this->assertTrue($this->audit->email('""@google.com'));
    }

    public function testIpv4()
    {
        $this->assertFalse($this->audit->ipv4(''));
        $this->assertFalse($this->audit->ipv4('...'));
        $this->assertFalse($this->audit->ipv4('hello, world'));
        $this->assertFalse($this->audit->ipv4('256.256.0.0'));
        $this->assertFalse($this->audit->ipv4('255.255.255.'));
        $this->assertFalse($this->audit->ipv4('.255.255.255'));
        $this->assertFalse($this->audit->ipv4('172.300.256.100'));

        $this->assertTrue($this->audit->ipv4('30.88.29.1'));
        $this->assertTrue($this->audit->ipv4('192.168.100.48'));
    }

    public function testIpv6()
    {
        $this->assertFalse($this->audit->ipv6(''));
        $this->assertFalse($this->audit->ipv6('FF01::101::2'));
        $this->assertFalse($this->audit->ipv6('::1.256.3.4'));
        $this->assertFalse($this->audit->ipv6('2001:DB8:0:0:8:800:200C:417A:221'));
        $this->assertFalse($this->audit->ipv6('FF02:0000:0000:0000:0000:0000:0000:0000:0001'));

        $this->assertTrue($this->audit->ipv6('::'));
        $this->assertTrue($this->audit->ipv6('::1'));
        $this->assertTrue($this->audit->ipv6('2002::'));
        $this->assertTrue($this->audit->ipv6('::ffff:192.0.2.128'));
        $this->assertTrue($this->audit->ipv6('0:0:0:0:0:0:0:1'));
        $this->assertTrue($this->audit->ipv6('2001:DB8:0:0:8:800:200C:417A'));
    }

    public function testIsPrivate()
    {
        $this->assertFalse($this->audit->isPrivate('0.1.2.3'));
        $this->assertFalse($this->audit->isPrivate('201.176.14.4'));

        $this->assertTrue($this->audit->isPrivate('fc00::'));
        $this->assertTrue($this->audit->isPrivate('10.10.10.10'));
        $this->assertTrue($this->audit->isPrivate('172.16.93.7'));
        $this->assertTrue($this->audit->isPrivate('192.168.3.5'));
    }

    public function testIsReserved()
    {
        $this->assertFalse($this->audit->isReserved('193.194.195.196'));

        $this->assertTrue($this->audit->isReserved('::1'));
        $this->assertTrue($this->audit->isReserved('127.0.0.1'));
        $this->assertTrue($this->audit->isReserved('0.1.2.3'));
        $this->assertTrue($this->audit->isReserved('169.254.1.2'));
        $this->assertTrue($this->audit->isReserved('240.241.242.243'));
    }

    public function testIsPublic()
    {
        $this->assertFalse($this->audit->isPublic('10.10.10.10'));

        $this->assertTrue($this->audit->isPublic('190.1.1.0'));
        $this->assertTrue($this->audit->isPublic('180.1.1.0'));
    }

    public function testIsDesktop()
    {
        $this->assertTrue($this->audit->isDesktop('bsd'));
        $this->assertTrue($this->audit->isDesktop('linux'));
        $this->assertTrue($this->audit->isDesktop('os x'));
        $this->assertTrue($this->audit->isDesktop('solaris'));
        $this->assertTrue($this->audit->isDesktop('windows'));
    }

    public function testIsMobile()
    {
        $this->assertTrue($this->audit->isMobile('android'));
        $this->assertTrue($this->audit->isMobile('blackberry'));
        $this->assertTrue($this->audit->isMobile('phone'));
        $this->assertTrue($this->audit->isMobile('ipod'));
        $this->assertTrue($this->audit->isMobile('palm'));
        $this->assertTrue($this->audit->isMobile('windows ce'));
    }

    public function testIsBot()
    {
        $this->assertTrue($this->audit->isBot('bot'));
        $this->assertTrue($this->audit->isBot('crawl'));
        $this->assertTrue($this->audit->isBot('slurp'));
        $this->assertTrue($this->audit->isBot('spider'));
    }

    public function testMod10()
    {
        $this->assertTrue($this->audit->mod10('61789372994'));
        $this->assertTrue($this->audit->mod10('49927398716'));
        $this->assertFalse($this->audit->mod10('abd'));
    }

    public function testCard()
    {
        $type = 'American Express';
        $this->assertEquals($type, $this->audit->card('378282246310005'));
        $this->assertEquals($type, $this->audit->card('371449635398431'));
        $this->assertEquals($type, $this->audit->card('378734493671000'));

        $type = 'Diners Club';
        $this->assertEquals($type, $this->audit->card('30569309025904'));
        $this->assertEquals($type, $this->audit->card('38520000023237'));

        $type = 'Discover';
        $this->assertEquals($type, $this->audit->card('6011111111111117'));
        $this->assertEquals($type, $this->audit->card('6011000990139424'));

        $type = 'JCB';
        $this->assertEquals($type, $this->audit->card('3530111333300000'));
        $this->assertEquals($type, $this->audit->card('3566002020360505'));

        $type = 'MasterCard';
        $this->assertEquals($type, $this->audit->card('5555555555554444'));
        $this->assertEquals($type, $this->audit->card('2221000010000015'));
        $this->assertEquals($type, $this->audit->card('5105105105105100'));

        $type = 'Visa';
        $this->assertEquals($type, $this->audit->card('4222222222222'));
        $this->assertEquals($type, $this->audit->card('4111111111111111'));
        $this->assertEquals($type, $this->audit->card('4012888888881881'));

        $this->assertFalse($this->audit->card('1234567890'));
    }
}
