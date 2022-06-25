<?php

declare(strict_types=1);

namespace PCore\HttpServer\Middlewares;

use PCore\HttpMessage\Response;
use PCore\HttpServer\Exceptions\HttpException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use Throwable;

/**
 * Class ExceptionHandleMiddleware
 * @package PCore\HttpServer\Middlewares
 * @github https://github.com/pcore-framework/http-server
 */
class ExceptionHandleMiddleware implements MiddlewareInterface
{

    protected array $httpExceptions = [
        'PCore\Routing\Exceptions\MethodNotAllowedException',
        'PCore\Routing\Exceptions\RouteNotFoundException'
    ];

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable, $request);
        }
    }

    /**
     * @param Throwable $throwable
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleException(Throwable $throwable, ServerRequestInterface $request): ResponseInterface
    {
        $this->reportException($throwable, $request);
        return $this->renderException($throwable, $request);
    }

    /**
     * @param Throwable $throwable
     * @param ServerRequestInterface $request
     * @return void
     */
    protected function reportException(Throwable $throwable, ServerRequestInterface $request): void
    {
    }

    /**
     * @param Throwable $throwable
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function renderException(Throwable $throwable, ServerRequestInterface $request): ResponseInterface
    {
        $message = sprintf('%s: %s in %s +%d', $throwable::class, $throwable->getMessage(), $throwable->getFile(), $throwable->getLine());
        return new Response(
            $this->getStatusCode($throwable),
            [],
            sprintf(
                <<<EOT
<html><head><meta charset="utf-8"><title>%s</title></head><body><pre style="font-size: 1.5em; white-space: break-spaces"><p><b>%s</b></p><b>Трассировка</b><br>%s</pre></body></html>
EOT,
                $message,
                $message,
                $throwable->getTraceAsString(),
            )
        );
    }

    /**
     * @param Throwable $throwable
     * @return int
     */
    protected function getStatusCode(Throwable $throwable): int
    {
        $statusCode = 500;
        if (in_array($throwable::class, $this->httpExceptions) || $throwable instanceof HttpException) {
            $statusCode = $throwable->getCode();
        }
        return (int)$statusCode;
    }

}