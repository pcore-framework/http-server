<?php

declare(strict_types=1);

namespace PCore\HttpServer\ResponseEmitter;

use PCore\HttpMessage\Cookie;
use PCore\HttpMessage\Stream\{FileStream, StringStream};
use PCore\HttpServer\Contracts\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

/**
 * Class SwooleResponseEmitter
 * @package PCore\HttpServer\ResponseEmitter
 * @github https://github.com/pcore-framework/http-server
 */
class SwooleResponseEmitter implements ResponseEmitterInterface
{

    /**
     * @param ResponseInterface $psrResponse
     * @param Response $sender
     * @return void
     */
    public function emit(ResponseInterface $psrResponse, $sender = null): void
    {
        $sender->status($psrResponse->getStatusCode());
        foreach ($psrResponse->getHeader('Set-Cookie') as $cookies) {
            foreach ($cookies as $cookieString) {
                $this->sendCookie(Cookie::parse($cookieString), $sender);
            }
        }
        $psrResponse = $psrResponse->withoutHeader('Set-Cookie');
        foreach ($psrResponse->getHeaders() as $key => $value) {
            $sender->header($key, implode(', ', $value));
        }
        $body = $psrResponse->getBody();
        switch (true) {
            case $body instanceof FileStream:
                $sender->sendfile($body->getMetadata('uri'), $body->tell(), max($body->getLength(), 0));
                break;
            case $body instanceof StringStream:
                $sender->end($body->getContents());
                break;
            default:
                $sender->end();
        }
        $body?->close();
    }

    /**
     * @param Cookie $cookie
     * @param Response $response
     * @return void
     */
    protected function sendCookie(Cookie $cookie, Response $response): void
    {
        $response->cookie(
            $cookie->getName(), $cookie->getValue(),
            $cookie->getExpires(), $cookie->getPath(),
            $cookie->getDomain(), $cookie->isSecure(),
            $cookie->isHttponly(), $cookie->getSamesite()
        );
    }

}