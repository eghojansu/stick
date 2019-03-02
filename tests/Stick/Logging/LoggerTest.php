<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 14:26
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Logging;

use Fal\Stick\Logging\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private $logger;

    public function setup()
    {
        $this->logger = new Logger();
    }

    public function testGetDirectory()
    {
        $this->assertEquals('', $this->logger->getDirectory());
    }

    public function testSetDirectory()
    {
        $dir = TEST_TEMP.'logger-dir/';

        if (is_dir($dir)) {
            rmdir($dir);
        }

        $this->assertEquals($dir, $this->logger->setDirectory($dir)->getDirectory());
    }

    public function testGetLogLevelThreshold()
    {
        $this->assertEquals(3, $this->logger->getLogLevelThreshold());
    }

    public function testSetLogLevelThreshold()
    {
        $this->assertEquals(1, $this->logger->setLogLevelThreshold(1)->getLogLevelThreshold());
    }

    public function testSetLogLevelThresholdException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid log level');

        $this->logger->setLogLevelThreshold(-1);
    }

    public function testLog()
    {
        $this->logger->setDirectory($dir = TEST_TEMP.'test-log/');
        $pattern = $dir.'*.log';

        foreach (glob($pattern) as $file) {
            unlink($file);
        }

        $this->logger->log(0, 'Emergency');
        $this->logger->log(3, 'Error');
        $this->logger->log(6, 'Info');
        // disable
        $this->logger->setDirectory('');
        $this->logger->log(1, 'Alert');

        $files = glob($pattern);

        $this->assertCount(1, $files);

        $content = file_get_contents($files[0]);

        $this->assertContains('Emergency', $content);
        $this->assertContains('Error', $content);
        $this->assertNotContains('Info', $content);
        $this->assertNotContains('Alert', $content);
    }

    public function testLogFiles()
    {
        $this->assertCount(0, $this->logger->logFiles());

        $this->logger->setDirectory($dir = TEST_TEMP.'test-log/');
        $pattern = $dir.'*.log';

        foreach (glob($pattern) as $file) {
            unlink($file);
        }

        $this->logger->log(0, 'Emergency');

        $this->assertCount(1, $this->logger->logFiles());
        $this->assertCount(0, $this->logger->logFiles('invalid date'));
        $this->assertCount(1, $this->logger->logFiles('today'));
        $this->assertCount(0, $this->logger->logFiles('yesterday'));
    }
}
