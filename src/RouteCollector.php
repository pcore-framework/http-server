<?php

declare(strict_types=1);

namespace PCore\HttpServer;

use PCore\Aop\Collectors\AbstractCollector;
use PCore\Di\Context;
use PCore\Di\Exceptions\NotFoundException;
use PCore\Di\Reflection;
use PCore\Routing\{Router};
use PCore\Routing\Annotations\{AutoController, Controller};
use PCore\Routing\Contracts\MappingInterface;
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
    /**
     * Соответствующий текущему контроллеру
     *
     * @var Router|null
     */
    protected static ?Router $router = null;

    /**
     * Имя класса текущего контроллера
     */
    protected static string $class = '';

    /**
     * Методы которые необходимы игнорировать
     */
    protected const IGNORE_METHODS = [
        '__construct',
        '__destruct',
        '__call',
        '__callStatic',
        '__get',
        '__set',
        '__isset',
        '__unset',
        '__sleep',
        '__wakeup',
        '__serialize',
        '__unserialize',
        '__toString',
        '__invoke',
        '__set_state',
        '__clone',
        '__debugInfo'
    ];

    /**
     * @param string $class
     * @param object $attribute
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public static function collectClass(string $class, object $attribute): void
    {
        $routeCollector = Context::getContainer()->make(\PCore\Routing\RouteCollector::class);
        $router = new Router(
            $attribute->prefix,
            $attribute->patterns,
            middlewares: $attribute->middlewares,
            routeCollector: $routeCollector
        );
        if ($attribute instanceof Controller) {
            self::$router = $router;
            self::$class = $class;
        } elseif ($attribute instanceof AutoController) {
            foreach (Reflection::class($class)->getMethods() as $reflectionMethod) {
                $methodName = $reflectionMethod->getName();
                if (!self::isIgnoredMethod($methodName) && $reflectionMethod->isPublic() && !$reflectionMethod->isAbstract()) {
                    $router->request($attribute->prefix . Str::snake($methodName, '-'), [$class, $methodName], $attribute->methods);
                }
            }
        }
    }

    /**
     * @param string $class
     * @param string $method
     * @param object $attribute
     * @throws NotFoundException
     */
    public static function collectMethod(string $class, string $method, object $attribute): void
    {
        if ($attribute instanceof MappingInterface && self::$class === $class && !is_null(self::$router)) {
            self::$router->request($attribute->path, [$class, $method], $attribute->methods, $attribute->middlewares);
        }
    }

    /**
     * Это игнорируемый метод
     *
     * @param string $method
     * @return bool
     */
    protected static function isIgnoredMethod(string $method): bool
    {
        return in_array($method, self::IGNORE_METHODS);
    }

}