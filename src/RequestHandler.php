<?php

declare(strict_types=1);

namespace PCore\HttpServer;

use PCore\Routing\Exceptions\RouteNotFoundException;
use PCore\Routing\Route;
use Psr\Container\{ContainerExceptionInterface, ContainerInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use ReflectionException;

/**
 * Class RequestHandler
 * @package PCore\HttpServer
 * @github https://github.com/pcore-framework/http-server
 */
class RequestHandler implements RequestHandlerInterface
{

    /**
     * Есть ли у контейнера метод make
     */
    private bool $hasMakeMethod;

    public function __construct(
        protected ContainerInterface $container,
        protected array $middlewares = []
    )
    {
        $this->hasMakeMethod = method_exists($this->container, 'make');
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($middleware = array_shift($this->middlewares)) {
            return $this->handleMiddleware(
                $this->hasMakeMethod
                    ? $this->container->make($middleware)
                    : new $middleware(),
                $request
            );
        }
        return $this->handleRequest($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ReflectionException
     */
    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($route = $request->getAttribute(Route::class)) {
            $parameters = $route->getParameters();
            $parameters['request'] = $request;
            return $this->container->call($route->getAction(), $parameters);
        }
        throw new RouteNotFoundException('Нет маршрута в атрибутах запроса', 404);
    }

    /**
     * Работа с промежуточным ПО
     *
     * @param MiddlewareInterface $middleware
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handleMiddleware(MiddlewareInterface $middleware, ServerRequestInterface $request): ResponseInterface
    {
        return $middleware->process($request, $this);
    }

    /**
     * Вставить промежуточное ПО после текущего промежуточного ПО
     *
     * @param array $middlewares
     * @return void
     */
    public function appendMiddlewares(array $middlewares): void
    {
        array_unshift($this->middlewares, ...$middlewares);
    }

}