<?php

namespace PCore\HttpServer\Contracts;

use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Throwable;

/**
 * Interface ExceptionHandlerInterface
 * @package PCore\HttpServer\Contracts
 * @github https://github.com/pcore-framework/http-server
 */
interface ExceptionHandlerInterface
{
    public function handle(Throwable $throwable, ServerRequestInterface $request): ?ResponseInterface;

    public function isValid(Throwable $throwable): bool;
}