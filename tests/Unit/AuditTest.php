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

    public function testIsSuccess()
    {
        $this->audit->validate();
        $this->assertTrue($this->audit->isSuccess());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No validation has been processed, run validate method first
     */
    public function testIsSuccessException()
    {
        $this->audit->isSuccess();
    }

    public function testIsFailed()
    {
        $this->audit->validate([
            'foo'=>'bar'
        ], [
            'foo'=>'is_int'
        ]);
        $this->assertTrue($this->audit->isFailed());
    }

    public function testGetErrors()
    {
        $this->assertEquals([], $this->audit->getErrors());
    }

    public function testGetValidated()
    {
        $this->assertEquals([], $this->audit->getValidated());
    }

    public function testCompact()
    {
        $this->assertEquals('foo:comma:bar', $this->audit->compact(['foo','bar']));
    }

    public function testEncode()
    {
        $this->assertEquals(':comma:foo:vbar:', $this->audit->encode(',foo|'));
    }

    public function testDecode()
    {
        $this->assertEquals(',foo|', $this->audit->decode(':comma:foo:vbar:'));
    }

    public function testValidate()
    {
        $this->audit->setRule('foo', function($val, $foo) {
            return $val ? $val . $foo : false;
        });
        $this->audit->setMessage('foo', 'Foo {key} {1}');

        $this->audit->setRules([
            'foo'=>'trim|required|foo[baz]',
            'email'=>'email[false]',
        ]);

        // set via method
        $this->audit->validate([
            'foo'=>'  bar  ',
            'email'=>'foo@bar.com'
        ]);
        $this->assertTrue($this->audit->isSuccess());
        $this->assertEquals(['foo'=>'barbaz','email'=>'foo@bar.com'], $this->audit->getValidated());

        // expect error
        $this->audit->setRules([
            'foo'=>'foo[baz]',
            'email'=>'email[false]',
        ]);
        $this->audit->validate([]);
        $this->assertFalse($this->audit->isSuccess());
        $this->assertEquals([], $this->audit->getValidated());
        $this->assertEquals(['Foo'=>['Foo Foo baz'],'Email'=>['This value is not a valid email address.']], $this->audit->getErrors());

        // skip when required false
        $this->audit->validate(['bar'=>'notemail'], ['foo'=>'required|email','bar'=>'required|email']);
        $this->assertEquals([
            'Foo'=>['This value should not be blank.'],
            'Bar'=>['This value is not a valid email address.'],
        ], $this->audit->getErrors());
    }

    public function messageProvider()
    {
        return [
            ['foo','','required','This value should not be blank.'],
            ['foo','','lenmin[1]','This value is too short. It should have 1 characters or more.'],
            ['f','foo','lenmax[1]','This value is too long. It should have 1 characters or less.'],
            ['fc00::','0.1.2.3','isprivate','This value is not a private ip address.'],
            ['190.1.1.0','10.10.10.10','ispublic','This value is not a public ip address.'],
            ['127.0.0.1','193.194.195.196','isreserved','This value is not a reserved ip address.'],
            [2,1,'min[2]','This value should be 2 or more.'],
            [2,3,'max[2]','This value should be 2 or less.'],
            ['abc@google.com','notemail','email','This value is not a valid email address.'],
            ['http://www.example.com/space%20here.html','noturl','url','This value is not a valid url.'],
            ['30.88.29.1','256.256.0.0','ipv4','This value is not a valid ipv4 address.'],
            ['0:0:0:0:0:0:0:1','FF01::101::2','ipv6','This value is not a valid ipv6 address.'],
            ['foo','bar','equal[foo]','This value should be equal to foo.'],
            ['bar','foo','notequal[foo]','This value should not be equal to foo.'],
            ['bar','foo','equalfield[foo]','This value should be equal to value of foo.',['foo'=>'bar']],
            ['foo','bar','notequalfield[foo]','This value should not be equal to value of foo.',['foo'=>'bar']],
            [1,'1','identical[1,integer]','This value should be identical to integer 1.'],
            ['1',1,'notidentical[1,integer]','This value should not be identical to integer 1.'],
            [1,2,'lt[2]','This value should be less than 2.'],
            [3,2,'gt[2]','This value should be greater than 2.'],
            [2,3,'lte[2]','This value should be less than or equal to 2.'],
            [2,1,'gte[2]','This value should be greater than or equal to 2.'],
            ['1',1,'type[string]','This value should be of type string.'],
            [[1],[],'countmin[1]','This collection should contain 1 elements or more.'],
            [[1],[1,2],'countmax[1]','This collection should contain 1 elements or less.'],
            ['2010-10-10','foo','date','This value is not a valid date.'],
            ['2010-10-10 00:00:00','foo','datetime','This value is not a valid datetime.'],
            ['foo','baz','regex[/foo:vbar:bar/]','This value is not valid.'],
            ['bar',null,'regex[/foo:vbar:bar/]'],
            [1,3,'choice[1:comma:2]','The value you selected is not a valid choice.'],
            [[1,2],[3],'choices[1:comma:2]','One or more of the given values is invalid.'],
        ];
    }

    /** @dataProvider messageProvider */
    public function testValidateMessage($trueVal, $falseVal, $rule, $message = null, array $extra = [])
    {
        $data = ['field'=>$trueVal] + $extra;
        $rules = array_fill_keys(array_keys($extra), 'required') + ['field'=>$rule];

        $this->audit->validate($data, $rules);
        $this->assertTrue($this->audit->isSuccess());

        if ($message) {
            $data['field'] = $falseVal;

            $this->audit->validate($data, $rules);
            $error = $this->audit->getErrors();

            $this->assertTrue($this->audit->isFailed());
            $this->assertContains($message, $error['Field']);
        }
    }

    public function testValidateDotAndCustomField()
    {
        $rules = [
            'foo.bar' => 'required',
            'foo.baz.qux|Custom Field' => 'required',
        ];
        $data = [
            'foo' => [
                'bar' => '',
                // baz is not supplied
            ],
        ];
        $this->audit->validate($data, $rules);
        $error = $this->audit->getErrors();

        $this->assertFalse($this->audit->isSuccess());
        $this->assertContains('This value should not be blank.', $error['Foo - Bar']);
        $this->assertContains('This value should not be blank.', $error['Custom Field']);

        // invalid
        $data2 = [
            'foo' => [
                'bar' => '',
                // not an array
                'baz' => 'foo',
            ],
        ];
        $this->audit->validate($data2, $rules);
        $this->assertFalse($this->audit->isSuccess());

        // valid
        $data3 = [
            'foo' => [
                'bar' => 'foo',
                'baz' => [
                    'qux' => 'foo',
                ],
            ],
        ];
        $this->audit->validate($data3, $rules);

        $this->assertTrue($this->audit->isSuccess());
    }

    public function testValidateConstruct()
    {
        $audit = new Audit([
            'foo' => function($val, $foo) {
                return $val ? $val . $foo : false;
            }
        ], [
            'foo' => 'Foo {key} {arg1}'
        ]);

        $audit->validate([
            'foo'=>'  bar  ',
        ], [
            'foo'=>'trim|required|foo[baz]',
        ]);

        $this->assertTrue($audit->isSuccess());
        $this->assertEquals(['foo'=>'barbaz'], $audit->getValidated());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Rule declaration is invalid, given "invalid-rule"
     */
    public function testValidateException1()
    {
        $this->audit->validate([], [
            'foo' => 'invalid-rule'
        ]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Rule "foo" does not exists
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

    public function testEqualField()
    {
        $this->assertTrue($this->audit->equalField('foo', 'bar', ['validated'=>['bar'=>'foo']]));
    }

    public function testNotEqualField()
    {
        $this->assertTrue($this->audit->notEqualField('foo', 'bar', ['validated'=>['bar'=>'baz']]));
    }

    public function testType()
    {
        $this->assertTrue($this->audit->type('', 'string'));
        $this->assertTrue($this->audit->type('foo', 'string'));
        $this->assertTrue($this->audit->type(1, 'integer'));
        $this->assertTrue($this->audit->type([], 'array'));
    }

    public function testEqual()
    {
        $this->assertTrue($this->audit->equal('foo', 'foo'));
        $this->assertTrue($this->audit->equal(1, 1));
    }

    public function testNotEqual()
    {
        $this->assertTrue($this->audit->notequal('bar', 'foo'));
        $this->assertTrue($this->audit->notequal(2, 1));
    }

    public function testIdentical()
    {
        $this->assertTrue($this->audit->identical('foo', 'foo'));
        $this->assertTrue($this->audit->identical(1, 1));

        $this->assertFalse($this->audit->identical(1, '1'));
    }

    public function testNotIdentical()
    {
        $this->assertTrue($this->audit->notIdentical('foo', 1));
        $this->assertTrue($this->audit->notIdentical(1, '1'));
    }

    public function testLt()
    {
        $this->assertTrue($this->audit->lt(1, 2));
        $this->assertTrue($this->audit->lt(4.9, 5));
    }

    public function testGt()
    {
        $this->assertTrue($this->audit->gt(3, 2));
        $this->assertTrue($this->audit->gt(5, 4.9));
    }

    public function testLte()
    {
        $this->assertTrue($this->audit->lte(1, 2));
        $this->assertTrue($this->audit->lte(2, 2));
        $this->assertTrue($this->audit->lte(4.9, 5));
        $this->assertTrue($this->audit->lte(5, 5));
    }

    public function testGte()
    {
        $this->assertTrue($this->audit->gte(3, 2));
        $this->assertTrue($this->audit->gte(2, 2));
        $this->assertTrue($this->audit->gte(5, 4.9));
        $this->assertTrue($this->audit->gte(4.9, 4.9));
    }

    public function testMin()
    {
        $this->assertTrue($this->audit->min(3, 2));
        $this->assertTrue($this->audit->min(5.1, 5));
    }

    public function testMax()
    {
        $this->assertTrue($this->audit->max(1, 2));
        $this->assertTrue($this->audit->max(4.9, 4.9));
    }

    public function testLenMin()
    {
        $this->assertTrue($this->audit->lenMin('foo', 3));
        $this->assertFalse($this->audit->lenMin('fo', 3));
    }

    public function testLenMax()
    {
        $this->assertTrue($this->audit->lenMax('foo', 3));
        $this->assertFalse($this->audit->lenMax('foobar', 3));
    }

    public function testCountMin()
    {
        $this->assertTrue($this->audit->countMin([1], 1));
        $this->assertFalse($this->audit->countMin([], 1));
    }

    public function testCountMax()
    {
        $this->assertTrue($this->audit->countMax([1], 1));
        $this->assertFalse($this->audit->countMax([1,2], 1));
    }

    public function testCdate()
    {
        $this->assertEquals('2010-10-10', $this->audit->cdate('Oct 10, 2010'));
        $this->assertEquals('Octt 10 2010', $this->audit->cdate('Octt 10 2010'));
    }

    public function testDate()
    {
        $this->assertTrue($this->audit->date('2010-10-10'));
    }

    public function testDatetime()
    {
        $this->assertTrue($this->audit->datetime('2010-10-10 00:00:00'));
    }

    public function testRegex()
    {
        $this->assertTrue($this->audit->regex('foo', '/foo/'));
    }

    public function testChoice()
    {
        $this->assertTrue($this->audit->choice(1, [1,2]));
    }

    public function testChoices()
    {
        $this->assertTrue($this->audit->choices('foo', ['foo','bar','baz']));
        $this->assertTrue($this->audit->choices(['foo','bar'], ['foo','bar','baz']));

        $this->assertFalse($this->audit->choices('qux', ['foo','bar','baz']));
        $this->assertFalse($this->audit->choices(['foo','qux'], ['foo','bar','baz']));
    }

    public function testUrl()
    {
        $this->assertFalse($this->audit->url('http://www.example.com/space here.html'));

        $this->assertTrue($this->audit->url('http://www.example.com/space%20here.html'));
    }

    public function testEmail()
    {
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
        $this->assertTrue($this->audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]'));
        $this->assertTrue($this->audit->email('"very.unusual.@.unusual.com"@google.com'));
        $this->assertTrue($this->audit->email('!#$%&\'*+-/=?^_`{}|~@google.com'));
        $this->assertTrue($this->audit->email('""@google.com'));
    }

    public function testIpv4()
    {
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
        $this->assertFalse($this->audit->isDesktop(''));

        $this->assertTrue($this->audit->isDesktop('bsd'));
        $this->assertTrue($this->audit->isDesktop('linux'));
        $this->assertTrue($this->audit->isDesktop('os x'));
        $this->assertTrue($this->audit->isDesktop('solaris'));
        $this->assertTrue($this->audit->isDesktop('windows'));
    }

    public function testIsMobile()
    {
        $this->assertFalse($this->audit->isMobile(''));

        $this->assertTrue($this->audit->isMobile('android'));
        $this->assertTrue($this->audit->isMobile('blackberry'));
        $this->assertTrue($this->audit->isMobile('phone'));
        $this->assertTrue($this->audit->isMobile('ipod'));
        $this->assertTrue($this->audit->isMobile('palm'));
        $this->assertTrue($this->audit->isMobile('windows ce'));
    }

    public function testIsBot()
    {
        $this->assertFalse($this->audit->isBot(''));

        $this->assertTrue($this->audit->isBot('bot'));
        $this->assertTrue($this->audit->isBot('crawl'));
        $this->assertTrue($this->audit->isBot('slurp'));
        $this->assertTrue($this->audit->isBot('spider'));
    }

    public function testMod10()
    {
        $this->assertFalse($this->audit->mod10(''));

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

        $this->assertEquals('', $this->audit->card('1234567890'));
    }
}
