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

namespace Fal\Stick\Test\Validation;

use Fal\Stick\Fw;
use Fal\Stick\Validation\Audit;
use Fal\Stick\TestSuite\MyTestCase;

class AuditTest extends MyTestCase
{
    private $fw;
    private $audit;

    protected function setUp(): void
    {
        $this->fw = new Fw();
        $this->audit = new Audit($this->fw);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::url
     */
    public function testUrl($expected, $str)
    {
        $this->assertEquals($expected, $this->audit->url($str));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::email
     */
    public function testEmail($expected, $str, $mx = true)
    {
        // all mx, set to false
        $mx = false;

        $this->assertEquals($expected, $this->audit->email($str, $mx));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::ipv4
     */
    public function testIpv4($expected, $addr)
    {
        $this->assertEquals($expected, $this->audit->ipv4($addr));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::ipv6
     */
    public function testIpv6($expected, $addr)
    {
        $this->assertEquals($expected, $this->audit->ipv6($addr));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::isPrivate
     */
    public function testIsPrivate($expected, $addr)
    {
        $this->assertEquals($expected, $this->audit->isPrivate($addr));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::isReserved
     */
    public function testIsReserved($expected, $addr)
    {
        $this->assertEquals($expected, $this->audit->isReserved($addr));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::isPublic
     */
    public function testIsPublic($expected, $addr)
    {
        $this->assertEquals($expected, $this->audit->isPublic($addr));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::userAgent
     */
    public function testIsDesktop($type, $agent)
    {
        $this->fw->set('AGENT', $agent);

        $this->assertEquals('desktop' === $type, $this->audit->isDesktop());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::userAgent
     */
    public function testIsMobile($type, $agent)
    {
        $this->fw->set('AGENT', $agent);

        $this->assertEquals('mobile' === $type, $this->audit->isMobile());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::userAgent
     */
    public function testIsBot($type, $agent)
    {
        $this->fw->set('AGENT', $agent);

        $this->assertEquals('bot' === $type, $this->audit->isBot());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::mod10
     */
    public function testMod10($expected, $id)
    {
        $this->assertEquals($expected, $this->audit->mod10($id));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::card
     */
    public function testCard($expected, $id)
    {
        $this->assertEquals($expected, $this->audit->card($id));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\AuditProvider::entropy
     */
    public function testEntropy($expected, $str)
    {
        $this->assertEquals($expected, $this->audit->entropy($str));
    }
}
