<?php

declare(strict_types=1);

namespace PCore\HttpServer\Middlewares;

use PCore\Http\Message\Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

/**
 * Class CorsMiddleware
 * @package PCore\HttpServer\Middlewares
 * @github https://github.com/pcore-framework/http-server
 */
class CorsMiddleware implements MiddlewareInterface
{

    /**
     * @var array разрешить все домены `*`
     */
    protected array $allowOrigin = [];

    /**
     * @var array дополнительные заголовки ответа
     */
    protected array $addedHeaders = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age' => 1800,
        'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With'
    ];

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $allowOrigin = in_array('*', $this->allowOrigin) ? '*' : $request->getHeaderLine('Origin');
        if ('' !== $allowOrigin) {
            $headers = $this->addedHeaders;
            $headers['Access-Control-Allow-Origin'] = $allowOrigin;
            if (0 === strcasecmp($request->getMethod(), 'OPTIONS')) {
                return new Response(204, $headers);
            }
            $response = $handler->handle($request);
            foreach ($headers as $name => $header) {
                $response = $response->withHeader($name, $header);
            }
            return $response;
        }
        return $handler->handle($request);
    }

}