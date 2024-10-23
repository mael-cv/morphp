<?php
namespace Maelcv\Morpion\Tests\Functional;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use PHPUnit\Framework\TestCase;

class WebtestBase extends TestCase
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;

    public function setUp(): void
    {
        $host = 'http://localhost:4444';
        $capabilities = DesiredCapabilities::microsoftEdge();
        $this->driver = RemoteWebDriver::create($host, $capabilities);
    }

    public function tearDown(): void
    {
        $this->driver->quit();
    }
}
