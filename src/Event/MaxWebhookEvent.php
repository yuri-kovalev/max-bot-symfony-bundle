<?php

namespace MaxMessenger\Bot\Bundle\Event;

use MaxMessenger\Bot\Model\Response\Update;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

final class MaxWebhookEvent extends Event
{
    private ?Response $response;

    public function __construct(private readonly Request $request, private readonly Update $update)
    {
        $this->response = null;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUpdate(): Update
    {
        return $this->update;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }
}
