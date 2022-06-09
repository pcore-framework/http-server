<?php

declare(strict_types=1);

namespace PCore\HttpServer;

use PCore\Aop\Collectors\AbstractCollector;
use PCore\Di\Context;
use PCore\Di\Exceptions\NotFoundException;
use PCore\Di\Reflection;
use PCore\Routing\Annotations\{AutoController, Controller};
use PCore\Routing\Contracts\MappingInterface;
use PCore\Routing\{Router, Route};
use PCore\Utils\Str;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * Class RouteCollector
 * @package PCore\HttpServer
 * @github https://github.com/pcore-framework/http-server
 */
class RouteCollector extends AbstractCollector
{

    protected static ?Router $router = null;

    /**
     * Имя класса текущего контроллера
     */
    protected static string $class = '';

    /**
     * @param string $class
     * @param object $attribute
     * @return void
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public static function collectClass(string $class, object $attribute): void
    {
        if ($attribute instanceof Controller) {
            self::$class = $class;
            self::$router = new Router([
                'prefix' => $attribute->prefix,
                'middlewares' => $attribute->middlewares,
            ], Context::getContainer()->make(\PCore\Routing\RouteCollector::class));
        }
        if ($attribute instanceof AutoController) {
            $router = new Router([
                'prefix' => $attribute->prefix,
                'middlewares' => $attribute->middlewares,
            ]);
            foreach (Reflection::class($class)->getMethods() as $reflectionMethod) {
                if ($reflectionMethod->isPublic() && !$reflectionMethod->isStatic() && !$reflectionMethod->isAbstract()) {
                    $action = $reflectionMethod->getName();
                    /** @var \PCore\Routing\RouteCollector $routeCollector */
                    $routeCollector = Context::getContainer()->make(\PCore\Routing\RouteCollector::class);
                    $routeCollector->add((new Route(
                        $attribute->methods,
                        $attribute->prefix . Str::snake($action, '-'),
                        [$class, $action],
                        $router,
                    )));
                }
            }
        }
    }

    /**
     * @param string $class
     * @param string $method
     * @param object $attribute
     * @return void
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public static function collectMethod(string $class, string $method, object $attribute): void
    {
        if ($attribute instanceof MappingInterface && self::$class === $class && !is_null(self::$router)) {
            /** @var \PCore\Routing\RouteCollector $routeCollector */
            $routeCollector = Context::getContainer()->make(\PCore\Routing\RouteCollector::class);
            $routeCollector->add((new Route(
                $attribute->methods,
                self::$router->getPrefix() . $attribute->path,
                [$class, $method],
                self::$router,
                $attribute->domain,
            ))->middlewares($attribute->middlewares));
        }
    }

}