<?php

declare(strict_types=1);

namespace PCore\HttpServer\Middlewares;

use InvalidArgumentException;
use PCore\HttpServer\Contracts\{ExceptionHandlerInterface, StoppableExceptionHandlerInterface};
use Psr\Container\{ContainerExceptionInterface, ContainerInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use ReflectionException;
use Throwable;


/**
 * Class ExceptionHandleMiddleware
 * @package PCore\HttpServer\Middlewares
 * @github https://github.com/pcore-framework/http-server
 */
class ExceptionHandleMiddleware implements MiddlewareInterface
{

    /**
     * @var ExceptionHandlerInterface[]|string[]
     */
    protected array $exceptionHandlers = [];

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(ContainerInterface $container)
    {
        foreach ($this->exceptionHandlers as $key => $exceptionHandler) {
            $this->exceptionHandlers[$key] = $container->make($exceptionHandler);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $throwable) {
            $finalResponse = null;
            foreach ($this->exceptionHandlers as $exceptionHandler) {
                if ($exceptionHandler->isValid($throwable)) {
                    if ($response = $exceptionHandler->handle($throwable, $request)) {
                        $finalResponse = $response;
                    }
                    if ($exceptionHandler instanceof StoppableExceptionHandlerInterface) {
                        return $finalResponse instanceof ResponseInterface
                            ? $finalResponse
                            : throw new InvalidArgumentException('Окончательный обработчик исключений должен возвращать экземпляр Psr\Http\Message\ResponseInterface');
                    }
                }
            }
            throw $throwable;
        }
    }
}