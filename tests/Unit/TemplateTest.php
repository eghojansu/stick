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
use Fal\Stick\App;
use Fal\Stick\Template;
use Fal\Stick\Test\fixture\classes\ProfileObj;
use Fal\Stick\Test\fixture\classes\UserObj;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    protected $template;
    protected $data;

    public function setUp()
    {
        error_clear_last();

        $this->template = new Template(new App, TEMP . 'template/', FIXTURE . 'template/');
        $this->template->addFunction('foo', 'trim');
        $this->template->addFunction('filter', 'trim');
        $this->template->addFunction('filter2', 'trim');
        $this->template->addFunction('filter3', 'trim');
        $this->data = [
            'date' => new \DateTime(),
            'user' => new UserObj(new ProfileObj('foo'), ['foo','bar'], 19),
            'foo' => '<p>Foo is paragraf</p>',
            'student' => [
                'name' => 'foo',
                'grades' => [
                    'first' => '1st grade',
                    'second' => '2nd grade',
                ],
            ],
            'baz' => ['qux'=>['quux'=>'bleh']],
            'bar' => ['baz'=>'qux'],
            'records' => [
                ['name'=>'foo'],
            ],
            'names' => ['foo'],
            'checkvar' => 'foo',
            'a' => 1,
            'b' => 2,
        ];
    }

    public function tearDown()
    {
        foreach (glob(TEMP . 'template/*') as $file) {
            unlink($file);
        }
    }

    public function testAddFunction()
    {
        $this->assertEquals($this->template, $this->template->addFunction('foo', 'bar'));
    }

    public function testCall()
    {
        $this->template->addFunction('xtrim', 'trim');
        $this->template->addFunction('closure', function($foo) {
            return trim($foo);
        });

        $this->assertEquals('foo', $this->template->call('xtrim', ' foo '));
        $this->assertEquals('foo', $this->template->call('closure', ' foo '));
    }

    public function testBeforeRender()
    {
        $this->template->beforeRender(function($content) {
            return $content . 'beforerender';
        });
        $this->template->render('source.html', $this->data);
        $parsed = f\read(FIXTURE . 'template/source.php') . 'beforerender';
        $actual = f\read(TEMP . 'template/' . f\hash(FIXTURE . 'template/source.html') . '.php');

        $this->assertEquals($parsed, $actual);
    }

    public function testAfterRender()
    {
        $this->template->afterRender(function($content) {
            return $content . 'afterrender';
        });
        $this->template->render('source.html', $this->data);
        $parsed = f\read(FIXTURE . 'template/source.php') . 'afterrender';
        $actual = f\read(TEMP . 'template/' . f\hash(FIXTURE . 'template/source.html') . '.php') . 'afterrender';

        $this->assertEquals($parsed, $actual);
    }

    public function testRender()
    {
        $rendered = $this->template->render('source.html', $this->data);
        $parsed = f\read(FIXTURE . 'template/source.php');
        $actual = f\read(TEMP . 'template/' . f\hash(FIXTURE . 'template/source.html') . '.php');

        $this->assertEquals($parsed, $actual);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage View file does not exists: foo
     */
    public function testRenderException()
    {
        $this->template->render('foo');
    }
}
