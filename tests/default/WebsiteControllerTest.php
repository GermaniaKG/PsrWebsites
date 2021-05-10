<?php
namespace tests;

use Germania\PsrWebsites\WebsiteController;
use Psr\Log\NullLogger;
use Germania\Websites\WebsiteInterface;
use Slim\Http\Environment;
use Slim\Http\Response;
use Slim\Http\Request;
use Prophecy\PhpUnit\ProphecyTrait;

class WebsiteControllerTest extends \PHPUnit\Framework\TestCase
{

    use ProphecyTrait;

    /**
     * @dataProvider provideCtorArgs
     */
    public function testInstantiation( $template, $defaults, $logger )
    {
        $render = function($tpl, $content) {
            return $tpl;
        };

        $sut = new WebsiteController($render, $defaults, $logger);
        $this->assertIsCallable($sut);
    }

    /**
     * @dataProvider provideCtorArgs
     */
    public function testInvokation( $template, $defaults, $logger )
    {
        // Must return the template name
        $render = function($tpl, $content) {
            return $tpl;
        };

        $website_mock = $this->prophesize( WebsiteInterface::class );
        $website_mock->getContentFile()->willReturn( $template );
        $website = $website_mock->reveal();

        $request = $this->createRequest()->withAttribute('website', $website);

        $response = new Response;
        $next = function( $request, $response ) {
            return $response;
        };

        // Setup Middleware
        $sut = new WebsiteController($render, $defaults, $logger);

        // Execute Middelware
        $response = $sut($request, $response, $next);

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
