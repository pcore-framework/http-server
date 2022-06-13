<?php

declare(strict_types=1);

namespace PCore\HttpServer;

use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use BadMethodCallException;
use PCore\HttpServer\Exceptions\InvalidMiddlewareException;
use PCore\Routing\Route;
use Psr\Container\{ContainerInterface, ContainerExceptionInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use ReflectionException;

/**
 * Class RequestHandler
 * @package PCore\Http\Server
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
     * @throws ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ([] === $this->middlewares) {
            return $this->handleRequest($request);
        }
        return $this->handleMiddleware(array_shift($this->middlewares), $request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Route $route */
        $route = $request->getAttribute(Route::class);
        $params = $route->getParameters();
        $params[] = $request;
        $action = $route->getAction();
        if (is_string($action)) {
            $action = explode('@', $action, 2);
        }
        if (!is_callable($action) && is_array($action)) {
            [$controller, $action] = $action;
            $action = [$this->container->make($controller), $action];
        }
        if (!is_callable($action)) {
            throw new BadMethodCallException('Данное действие не является вызываемым значением.');
        }
        return $this->container->call($action, $params);
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
        throw new InvalidMiddlewareException(sprintf('Промежуточное программное обеспечение `%s должно реализовывать интерфейс `Psr\Http\Server\MiddlewareInterface`.', $middleware));
    }

    /**
     * Добавить промежуточное программное обеспечение в хвост
     *
     * @param array $middlewares
     * @return void
     */
    public function pushMiddlewares(array $middlewares): void
    {
        array_push($this->middlewares, ...$middlewares);
    }

    /**
     * Вставить промежуточное программное обеспечение из головы
     *
     * @param array $middlewares
     * @return void
     */
    public function unshiftMiddlewares(array $middlewares): void
    {
        array_unshift($this->middlewares, ...$middlewares);
    }

}