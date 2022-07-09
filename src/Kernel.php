<?php

declare(strict_types=1);

namespace PCore\HttpServer;

use PCore\HttpServer\Events\OnRequest;
use PCore\Routing\{RouteCollector, Router};
use PCore\Routing\Exceptions\RouteNotFoundException;
use Psr\Container\{ContainerExceptionInterface, ContainerInterface};
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use ReflectionException;

/**
 * Class Kernel
 * @package PCore\HttpServer
 * @github https://github.com/pcore-framework/http-server
 */
class Kernel
{

    /**
     * Глобальное промежуточное программное обеспечение
     */
    protected array $middlewares = [
        'PCore\HttpServer\Middlewares\ExceptionHandleMiddleware',
        'PCore\HttpServer\Middlewares\RoutingMiddleware'
    ];

    /**
     * @param RouteCollector $routeCollector сборщик маршрутов
     * @param ContainerInterface $container контейнер
     * @param ?EventDispatcherInterface $eventDispatcher диспетчер событий
     */
    final public function __construct(
        protected RouteCollector $routeCollector,
        protected ContainerInterface $container,
        protected ?EventDispatcherInterface $eventDispatcher = null,
    )
    {
        $this->map(new Router([], $routeCollector));
    }

    /**
     * Регистрация маршрута
     *
     * @param Router $router
     * @return void
     */
    protected function map(Router $router): void
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException|RouteNotFoundException
     */
    public function through(ServerRequestInterface $request): ResponseInterface
    {
        $response = (new RequestHandler($this->container, $this->middlewares))->handle($request);
        $this->eventDispatcher?->dispatch(new OnRequest($request, $response));
        return $response;
    }

}