<?php

namespace PCore\HttpServer\Events;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class OnRequest
 * @package PCore\HttpServer\Events
 * @github https://github.com/pcore-framework/http-server
 */
class OnRequest
{

    public function __construct(public ServerRequestInterface $request, public ResponseInterface $response)
    {
    }

}