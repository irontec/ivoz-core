<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Cgrates;

use Graze\GuzzleHttp\JsonRpc\ClientInterface;
use Graze\GuzzleHttp\JsonRpc\Message\Request;
use Graze\GuzzleHttp\JsonRpc\Message\RequestInterface;
use Graze\GuzzleHttp\JsonRpc\Message\Response;
use GuzzleHttp\Promise\Promise;

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
        return $this->createRequest();
    }

    public function request($id, $method, array $params = null)
    {
        return new Request(
            'POST',
            '/uri',
            [],
            $this->fixedResponse
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
        return $this->createPromise();
    }

    public function sendAll(array $requests)
    {
        return [$this->createRequest()];
    }

    public function sendAllAsync(array $requests)
    {
        return $this->createPromise();
    }


    private function createRequest()
    {
        return new Request(
            'POST',
            '/uri',
            [],
            '[]'
        );
    }

    private function createPromise()
    {
        return new Promise();
    }
}
