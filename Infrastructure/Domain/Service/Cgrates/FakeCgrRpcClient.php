<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Cgrates;

use Graze\GuzzleHttp\JsonRpc\ClientInterface;
use Graze\GuzzleHttp\JsonRpc\Message\Request;
use Graze\GuzzleHttp\JsonRpc\Message\RequestInterface;
use Graze\GuzzleHttp\JsonRpc\Message\Response;

class FakeCgrRpcClient implements ClientInterface
{

    private $fixedResponse = '{"error": null}';

    public function __construct(
        string $fixedResponse = '{"error": null}'
    ) {
        $this->fixedResponse = $fixedResponse;
    }

    public function notification($method, array $params = null)
    {
    }

    public function request($id, $method, array $params = null)
    {
        return new Request(
            'POST',
            '/uri',
            [],
            '[]'
        );
    }

    public function send(RequestInterface $request)
    {
        return new Response(
            200,
            [],
            $this->fixedResponse
        );
    }

    public function sendAsync(RequestInterface $request)
    {
    }

    public function sendAll(array $requests)
    {
    }

    public function sendAllAsync(array $requests)
    {
    }
}
