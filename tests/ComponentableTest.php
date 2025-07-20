<?php

use LaravelLux\Html\Componentable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Mockery as m;

#[\AllowDynamicProperties]
class DummyComponentable
{
    use Componentable;

    public function __construct(Factory $view)
    {
        $this->view = $view;
    }
}

#[\AllowDynamicProperties]
class ComponentableTest extends PHPUnit\Framework\TestCase
{
    protected DummyComponentable $component;
    protected $viewFactory;

    protected function setUp(): void
    {
        $this->viewFactory = m::mock(Factory::class);
        $this->component = new DummyComponentable($this->viewFactory);

        $ref = new ReflectionProperty(DummyComponentable::class, 'components');
        $ref->setAccessible(true);
        $ref->setValue([]);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testComponentRegistrationAndHasComponent()
    {
        DummyComponentable::component('foo', 'components.foo', []);

        $this->assertTrue(DummyComponentable::hasComponent('foo'));
        $this->assertFalse(DummyComponentable::hasComponent('bar'));
    }

    public function testDynamicComponentCallMapsArguments()
    {
        DummyComponentable::component('alert', 'components.alert', ['type' => 'info', 'message']);

        $view = m::mock(View::class);
        $this->viewFactory->shouldReceive('make')
            ->once()
            ->with('components.alert', ['type' => 'error', 'message' => 'Disk full'])
            ->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered');

        $result = $this->component->alert('error', 'Disk full');

        $this->assertEquals('rendered', $result);
    }
}
