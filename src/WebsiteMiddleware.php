<?php
namespace Germania\PsrWebsites;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;

class WebsiteMiddleware
{

    /**
     * @var ContainerInterface
     */
    public $websites;

    /**
     * @var Callable
     */
    public $render;

    /**
     * @var string
     */
    public $template;

    /**
     * @var array
     */
    public $defaults = array();

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var StreamFactoryInterface
     */
    public $stream_factory;



    /**
     * @param ContainerInterface  $websites   A ContainerInterface instance that returns a website for the current route name
     * @param Callable            $render     Rendering callable that receives template file and variables context
     * @param string              $template   Template file
     * @param array               $defaults   Default variables context
     * @param LoggerInterface     $logger     Optional: PSR-3 Logger
     */
    public function __construct( ContainerInterface $websites, Callable $render, $template, array $defaults, LoggerInterface $logger = null)
    {
        $this->websites  = $websites;
        $this->render    = $render;
        $this->template  = $template;
        $this->defaults  = $defaults;
        $this->setStreamFactory( new \Nyholm\Psr7\Factory\Psr17Factory() );
        $this->logger    = $logger ?: new NullLogger;
    }

    public function setStreamFactory( StreamFactoryInterface $stream_factory ) 
    {
        $this->stream_factory = $stream_factory;
    }



    /**
     * @param  Psr\Http\Message\ServerRequestInterface  $request  PSR7 request
     * @param  Psr\Http\Message\ResponseInterface       $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        // ---------------------------------------
        //  1. Get current Website object,
        //     add as attribute to Request.
        //     The website will be available within route controllers.
        // ---------------------------------------
        $current_route_name = $request->getAttribute('route')->getName();

        $website_factory = $this->websites;
        $website = $website_factory->get( $current_route_name );

        $request = $request->withAttribute('website', $website);


        // ---------------------------------------
        //  2. Call next middleware.
        //     Page content (not HTML head and stuff!)
        //     should be created there...
        // ---------------------------------------
        $content_response = $next($request, $response);

        $this->logger->debug("Content response", array_merge(
            [ 'status' => $content_response->getStatusCode() ],
            $content_response->getHeaders()
        ));

        // ---------------------------------------
        // 3. Create template variables
        // ---------------------------------------

        $vars = array_merge(
            $this->defaults, [
                'title'    =>  $website->getTitle(),
                'id'       =>  $website->getDomId(),
                'base_url' =>  $request->getUri()->getBaseUrl(),
                'content'  =>  (string) $content_response->getBody()
            ]
        );

        $vars['javascripts'] = isset($vars['javascripts'])
        ? array_merge($vars['javascripts'], $website->getJavascripts() ?: array())
        : ($website->getJavascripts() ?: array());

        $vars['stylesheets'] = isset($vars['stylesheets'])
        ? array_merge($vars['stylesheets'], $website->getStylesheets() ?: array())
        : ($website->getStylesheets() ?: array());


        // ---------------------------------------
        // 5. After that, render 'full page' template
        //    and store in new Response Body.
        // ---------------------------------------

        // Output global website template
        $this->logger->debug("Render page template…");

        $render = $this->render;
        $rendered_result = $render( $this->template, $vars);

        $this->logger->debug("Finish page template render; write response");


        $full_html_response_body = $this->stream_factory->createStream($rendered_result);

        // ---------------------------------------
        // 6. Replace old Response Body with new one
        // ---------------------------------------

        return $content_response->withHeader('Content-Type', 'text/html')
                                ->withBody($full_html_response_body);
    }



}
