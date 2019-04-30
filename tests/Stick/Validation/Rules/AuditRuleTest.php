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

namespace Fal\Stick\Test\Validation\Rules;

use Fal\Stick\Fw;
use Fal\Stick\Validation\Audit;
use Fal\Stick\Validation\Rules\AuditRule;
use Fal\Stick\Validation\Context;
use Fal\Stick\TestSuite\MyTestCase;

class AuditRuleTest extends MyTestCase
{
    private $audit;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->audit = new AuditRule(new Audit(new Fw()));
    }

    public function testHas()
    {
        $this->assertTrue($this->audit->has('url'));
        $this->assertTrue($this->audit->has('isbot'));
        $this->assertTrue($this->audit->has('isdesktop'));
        $this->assertTrue($this->audit->has('ismobile'));
    }

    public function testValidate()
    {
        $value = new Context(array(
            'url' => 'http://localhost',
        ));
        $value->setField('url');

        $this->assertTrue($this->audit->validate('url', $value));
    }
}
