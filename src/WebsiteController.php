<?php
namespace Germania\PsrWebsites;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class WebsiteController
{

    /**
     * @var Callable
     */
    public $render;

    /**
     * @var array
     */
    public $defaults = array();

    /**
     * @var LoggerInterface
     */
    public $logger;


    /**
     * @param Callable              $render     Rendering callable that receives template file and variables context
     * @param array                 $defaults   Default variables context
     * @param LoggerInterface|null  $logger     Optional: PSR-3 Logger
     */
    public function __construct( Callable $render, array $defaults, LoggerInterface $logger = null)
    {
        $this->render    = $render;
        $this->defaults  = $defaults;
        $this->logger    = $logger ?: new NullLogger;
    }



    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
	public function __invoke(Request $request, Response $response, $args) {

        // Shortcuts
        $logger    = $this->logger;


        // ---------------------------------------
        // 1. Retrieve current website object
        // ---------------------------------------
        $website   = $request->getAttribute('website');
        $website_content_file = $website->getContentFile();


        // ---------------------------------------
        // 2. Render page content include
        // ---------------------------------------
        $logger->debug("Render page content…");

        $render = $this->render;
        $content = $render( $website_content_file, array_merge(
            $this->defaults, [
                'request'     => $request,
                'response'    => $response,
                'args'        => $args,
                'logger'      => $logger
            ]
        ));


        // ---------------------------------------
        // 3. Write response
        // ---------------------------------------
        $logger->debug("Finish page template…, write to response");
        $response->write( $content );
        return $response;


	}
}
