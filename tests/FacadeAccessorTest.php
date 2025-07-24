<?php

use LaravelLux\Html\HtmlFacade;
use LaravelLux\Html\FormFacade;

class FacadeAccessorTest extends PHPUnit\Framework\TestCase
{
    public function testHtmlFacadeAccessorReturnsHtml()
    {
        $ref = new ReflectionMethod(HtmlFacade::class, 'getFacadeAccessor');
        $ref->setAccessible(true);
        $this->assertEquals('html', $ref->invoke(null));
    }

    public function testFormFacadeAccessorReturnsForm()
    {
        $ref = new ReflectionMethod(FormFacade::class, 'getFacadeAccessor');
        $ref->setAccessible(true);
        $this->assertEquals('form', $ref->invoke(null));
    }
}
