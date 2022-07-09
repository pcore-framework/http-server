<?php

declare(strict_types=1);

namespace PCore\HttpServer\ResponseEmitter;

use PCore\HttpMessage\Cookie;
use PCore\HttpMessage\Stream\FileStream;
use PCore\HttpServer\Contracts\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Response;

/**
 * Class WorkerManResponseEmitter
 * @package PCore\HttpServer\ResponseEmitter
 * @github https://github.com/pcore-framework/http-server
 */
class WorkerManResponseEmitter implements ResponseEmitterInterface
{

    /**
     * @param TcpConnection $sender
     */
    public function emit(ResponseInterface $psrResponse, $sender = null): void
    {
        $response = new Response($psrResponse->getStatusCode());
        $cookies = $psrResponse->getHeader('Set-Cookie');
        $psrResponse = $psrResponse->withoutHeader('Set-Cookie');
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $response->header($name, implode(', ', $values));
        }
        $body = $psrResponse->getBody();
        if ($body instanceof FileStream) {
            $sender->send($response->withFile($body->getMetadata('uri'), $body->tell(), $body->getLength()));
        } else {
            /** @var string[] $cookies */
            foreach ($cookies as $cookie) {
                $cookie = Cookie::parse($cookie);
                $response->cookie(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getMaxAge(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $cookie->isSecure(),
                    $cookie->isHttponly(),
                    $cookie->getSamesite()
                );
            }
            $sender->send($response->withBody((string)$body?->getContents()));
        }
        $body?->close();
        $sender->close();
    }

}