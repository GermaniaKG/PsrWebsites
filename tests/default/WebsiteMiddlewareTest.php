<?php
namespace tests;

use Germania\Websites\WebsiteInterface;
use Germania\PsrWebsites\WebsiteMiddleware;
use Psr\Log\NullLogger;
use Psr\Container\ContainerInterface;
use Slim\Http\Environment;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Route;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;


class WebsiteMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provideCtorArgs
     */
    public function testInstantiation( $template, $defaults, $logger )
    {

        $website_mock = $this->prophesize( WebsiteInterface::class );
        $website_mock->getTitle()->willReturn( "TheTitle" );
        $website_mock->getDomId()->willReturn( "dom-id" );
        $website_mock->getJavascripts()->willReturn( array("javascript") );
        $website_mock->getStylesheets()->willReturn( array("stylesheet") );
        $website = $website_mock->reveal();


        $container_mock = $this->prophesize( ContainerInterface::class );
        $container_mock->get( Argument::any() )->willReturn( $website );
        $container = $container_mock->reveal();

        $render = function($tpl, $content) {
            return $tpl;
        };

        $sut = new WebsiteMiddleware($container, $render, $template, $defaults, $logger);
        $this->assertIsCallable($sut);
    }

    /**
     * @dataProvider provideCtorArgs
     */
    public function testInvokation( $template, $defaults, $logger )
    {
        $route_name = "TheRouteName";

        $route_mock = $this->prophesize( Route::class );
        $route_mock->getName()->willReturn( $route_name );
        $route = $route_mock->reveal();

        $website_mock = $this->prophesize( WebsiteInterface::class );
        $website_mock->getTitle()->willReturn( "TheTitle" );
        $website_mock->getDomId()->willReturn( "dom-id" );
        $website_mock->getJavascripts()->willReturn( array("javascript") );
        $website_mock->getStylesheets()->willReturn( array("stylesheet") );
        $website = $website_mock->reveal();

        $container_mock = $this->prophesize( ContainerInterface::class );
        $container_mock->get( $route_name )->willReturn( $website );
        $container = $container_mock->reveal();

        // Must return the template name
        $render = function($tpl, $content) {
            return $tpl;
        };



        $request = $this->createRequest()->withAttribute('route', $route);
        $response = new Response;
        $next = function( $request, $response ) {
            return $response;
        };

        // Setup and execute Middleware SUT
        $sut = new WebsiteMiddleware($container, $render, $template, $defaults, $logger);
        $response = $sut($request, $response, $next);

        // Assertion
        $body = (string) $response->getBody();
        $this->assertEquals($body, $template);
    }


    public function provideCtorArgs()
    {
        $template = 'template.tpl';

        $defaults = array('foo' => 'bar');

        $logger = new NullLogger;

        return array(
            [ $template, $defaults, $logger ]
        );
    }

    protected function createRequest()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123&foo=bar',
            'SERVER_NAME' => 'example.com',
            'CONTENT_TYPE' => 'application/json;charset=utf8',
            'CONTENT_LENGTH' => 15
        ]);

        return Request::createFromEnvironment( $env );
    }
}
