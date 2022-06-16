<?php

declare(strict_types=1);

namespace PCore\HttpServer\Middlewares;

use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use PCore\Routing\Exceptions\{MethodNotAllowedException, RouteNotFoundException};
use PCore\Routing\{Route, RouteCollector};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class RoutingMiddleware
 * @package PCore\HttpServer\Middlewares
 * @github https://github.com/pcore-framework/http-server
 */
class RoutingMiddleware implements MiddlewareInterface
{

    public function __construct(protected RouteCollector $routeCollector)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws MethodNotAllowedException
     * @throws RouteNotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->routeCollector->resolve($request);
        $handler->unshiftMiddlewares($route->getMiddlewares());
        return $handler->handle($request->withAttribute(Route::class, $route));
    }

}