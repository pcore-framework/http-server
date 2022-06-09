<?php

declare(strict_types=1);

namespace PCore\HttpServer\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ResponseEmitterInterface
 * @package PCore\HttpServer\Contracts
 * @github https://github.com/pcore-framework/http-server
 */
interface ResponseEmitterInterface
{

    public function emit(ResponseInterface $psrResponse, $sender = null): void;

}