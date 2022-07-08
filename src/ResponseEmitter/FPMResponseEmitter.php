<?php

declare(strict_types=1);

namespace PCore\HttpServer\ResponseEmitter;

use PCore\HttpMessage\Cookie;
use PCore\HttpServer\Contracts\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class FPMResponseEmitter
 * @package PCore\HttpServer\ResponseEmitter
 * @github https://github.com/pcore-framework/http-server
 */
class FPMResponseEmitter implements ResponseEmitterInterface
{

    /**
     * @param ResponseInterface $psrResponse
     * @param null $sender
     * @return void
     */
    public function emit(ResponseInterface $psrResponse, $sender = null): void
    {
        header(sprintf('HTTP/%s %d %s', $psrResponse->getProtocolVersion(), $psrResponse->getStatusCode(), $psrResponse->getReasonPhrase()), true);
        /** @var string[] $cookies */
        foreach ($psrResponse->getHeader('Set-Cookie') as $cookie) {
            $cookie = Cookie::parse($cookie);
            setcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpires(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttponly()
            );
        }
        $psrResponse = $psrResponse->withoutHeader('Set-Cookie');
        foreach ($psrResponse->getHeaders() as $name => $value) {
            header($name . ': ' . implode(', ', $value));
        }
        $body = $psrResponse->getBody();
        echo $body?->getContents();
        $body?->close();
    }

}