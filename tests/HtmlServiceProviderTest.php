<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Contracts\Session\Session;
use LaravelLux\Html\FormBuilder;
use LaravelLux\Html\HtmlBuilder;
use LaravelLux\Html\HtmlServiceProvider;
use Mockery as m;

class SimpleConfig
{
    protected array $items = [];

    public function get($key, $default = null)
    {
        return $this->items[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }
}


class TestContainer extends Container
{
    protected bool $console = false;

    public function runningInConsole(): bool
    {
        return $this->console;
    }

    public function setConsole(bool $console): void
    {
        $this->console = $console;
    }
}

class HtmlServiceProviderStub extends HtmlServiceProvider
{
    public function __construct($app)
    {
        $this->app = $app;
    }
}

class HtmlServiceProviderPublishStub extends HtmlServiceProvider
{
    public array $published = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    protected function publishes(array $paths, $group = null)
    {
        $this->published = $paths;
    }
}

#[\AllowDynamicProperties]
class HtmlServiceProviderTest extends PHPUnit\Framework\TestCase
{
    protected TestContainer $app;
    protected $blade;

    protected function setUp(): void
    {
        $this->app = new TestContainer();
        $this->app->instance('config', new SimpleConfig());
        $this->app->instance('view', m::mock(Factory::class));

        $url = new UrlGenerator(new RouteCollection(), Request::create('/', 'GET'));
        $this->app->instance('url', $url);
        $this->app->instance('request', Request::create('/', 'GET'));
        $session = m::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('token')->andReturn('token');
        $this->app->instance('session.store', $session);

        $this->blade = m::mock(BladeCompiler::class);
        $bladeMock = $this->blade;
        $this->app->singleton('blade.compiler', function () use ($bladeMock) {
            return $bladeMock;
        });
    }

    protected function tearDown(): void
    {
        m::close();
    }

    protected function getProvider(): HtmlServiceProvider
    {
        return new HtmlServiceProviderStub($this->app);
    }

    public function testRegisterBindsHtmlAndForm()
    {
        $provider = $this->getProvider();
        $provider->register();

        $html = $this->app->make('html');
        $form = $this->app->make('form');

        $this->assertInstanceOf(HtmlBuilder::class, $html);
        $this->assertSame($html, $this->app->make(HtmlBuilder::class));
        $this->assertInstanceOf(FormBuilder::class, $form);
        $this->assertSame($form, $this->app->make(FormBuilder::class));
    }

    public function testRegisterBladeDirectives()
    {
        $directives = [];
        $this->blade->shouldReceive('directive')->andReturnUsing(function ($name, $handler) use (&$directives) {
            $directives[$name] = $handler;
        });

        $provider = $this->getProvider();
        $provider->register();

        // Resolving the blade compiler triggers the afterResolving callbacks
        $this->app->make('blade.compiler');

        $this->assertArrayHasKey('html_entities', $directives);
        $this->assertIsCallable($directives['html_entities']);
        $this->assertEquals('<?php echo Html::entities(\'bar\'); ?>', $directives['html_entities']("'bar'"));

        $this->assertArrayHasKey('form_open', $directives);
        $this->assertIsCallable($directives['form_open']);
        $this->assertEquals('<?php echo Form::open(); ?>', $directives['form_open'](''));
    }

    public function testBootPublishesConfigurationAndProvidesServices()
    {
        if (!function_exists('config_path')) {
            function config_path($path = '') { return '/config/'.$path; }
        }

        $this->app->setConsole(true);

        $provider = new HtmlServiceProviderPublishStub($this->app);
        $provider->boot();

        $expected = [dirname(__DIR__).'/src/../config/html-forms.php' => config_path('html-forms.php')];
        $this->assertEquals($expected, $provider->published);

        $this->assertEquals([
            'html',
            'form',
            HtmlBuilder::class,
            FormBuilder::class,
        ], $provider->provides());
    }
}

