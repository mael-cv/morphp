<?php
namespace Maelcv\Morpion\Tests\Functional;

use Facebook\WebDriver\WebDriverBy;

class SampleWebTest extends WebtestBase
{
    public function testHomePage()
    {
        $this->driver->get('https://github.com/labasse');

        $this->assertEquals('Navigation Menu', $this->driver->findElement(WebDriverBy::tagName('h2'))->getText());
        $this->assertEquals('labasse (Labasse)', $this->driver->getTitle());
    }
}
