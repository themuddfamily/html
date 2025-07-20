<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use LaravelLux\Html\HtmlBuilder;
use Mockery as m;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[\AllowDynamicProperties]
class HelpersTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('app')) {
            function app($abstract = null, array $parameters = []) {
                $container = Container::getInstance() ?: new Container();
                Container::setInstance($container);
                if (is_null($abstract)) {
                    return $container;
                }
                return $container->make($abstract, $parameters);
            }
        }

        $this->container = new Container();
        Container::setInstance($this->container);

        $this->urlGenerator = new UrlGenerator(new RouteCollection(), Request::create('/foo', 'GET'));
        $this->viewFactory = m::mock(Factory::class);
        $this->htmlBuilder = new HtmlBuilder($this->urlGenerator, $this->viewFactory);

        $this->container->instance('html', $this->htmlBuilder);
    }

    protected function tearDown(): void
    {
        m::close();
        Container::setInstance(null);
    }

    public function testLinkTo()
    {
        $expected = $this->htmlBuilder->link('http://example.com', 'Example', ['class' => 'link']);
        $result = link_to('http://example.com', 'Example', ['class' => 'link']);

        $this->assertEquals($expected, $result);
    }

    public function testLinkToAsset()
    {
        $expected = $this->htmlBuilder->linkAsset('style.css', 'Style');
        $result = link_to_asset('style.css', 'Style');

        $this->assertEquals($expected, $result);
    }

    public function testLinkToRoute()
    {
        $routes = new RouteCollection();
        $routes->add(new Route(['GET'], 'foo', ['as' => 'foo']));
        $this->urlGenerator->setRoutes($routes);

        $expected = $this->htmlBuilder->linkRoute('foo', 'Foo');
        $result = link_to_route('foo', 'Foo');

        $this->assertEquals($expected, $result);
    }

    public function testLinkToAction()
    {
        $routes = new RouteCollection();
        $routes->add(new Route(['GET'], 'bar', ['uses' => 'HomeController@index', 'controller' => 'HomeController@index']));
        $this->urlGenerator->setRoutes($routes);

        $expected = $this->htmlBuilder->linkAction('HomeController@index', 'Index');
        $result = link_to_action('HomeController@index', 'Index');

        $this->assertEquals($expected, $result);
    }
}
