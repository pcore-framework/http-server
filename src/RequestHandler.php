<?php

declare(strict_types=1);

namespace PCore\HttpServer;

use PCore\HttpServer\Exceptions\InvalidMiddlewareException;
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

    public function __construct(
        protected ContainerInterface $container,
        protected array $middlewares = []
    )
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException|RouteNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middlewares === []) {
            return $this->handleRequest($request);
        }
        return $this->handleMiddleware(array_shift($this->middlewares), $request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException|RouteNotFoundException
     */
    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($route = $request->getAttribute(Route::class)) {
            $action = $route->getAction();
            if (is_string($action)) {
                $action = explode('@', $action, 2);
            }
            $parameters = $route->getParameters();
            $parameters['request'] = $request;
            return $this->container->call($action, $parameters);
        }
        throw new RouteNotFoundException('Маршрут не совпадает', 404);
    }

    /**
     * @param string $middleware
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    protected function handleMiddleware(string $middleware, ServerRequestInterface $request): ResponseInterface
    {
        $handler = is_null($this->container) ? new $middleware() : $this->container->make($middleware);
        if ($handler instanceof MiddlewareInterface) {
            return $handler->process($request, $this);
        }
        throw new InvalidMiddlewareException(sprintf('Промежуточное программное обеспечение `%s должен быть экземпляром Psr\Http\Server\MiddlewareInterface.', $middleware));
    }

    /**
     * Добавить промежуточное программное обеспечение в хвост
     *
     * @param array $middlewares
     * @return void
     */
    public function appendMiddlewares(array $middlewares): void
    {
        array_push($this->middlewares, ...$middlewares);
    }

    /**
     * Вставить промежуточное ПО после текущего промежуточного ПО
     *
     * @param array $middlewares
     * @return void
     */
    public function prependMiddlewares(array $middlewares): void
    {
        array_unshift($this->middlewares, ...$middlewares);
    }

}