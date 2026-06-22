<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Controller;

use MaxMessenger\Bot\Bundle\Event\MaxWebhookEvent;
use MaxMessenger\Bot\Bundle\Exception\InvalidWebhookSecretException;
use MaxMessenger\Bot\Bundle\Max\MaxUpdateHandler;
use MaxMessenger\Bot\Bundle\Service\MaxBotFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class WebhookController
{
    public function __construct(
        private ?string $webhookSecret,
        private EventDispatcherInterface $eventDispatcher,
        private MaxUpdateHandler $maxUpdateHandler,
        private MaxBotFactory $maxBotFactory,
    ) {
    }

    public function indexAction(Request $request): JsonResponse
    {
        try {
            $this->assertRequest($request);

            $body = \trim($request->getContent());
            if ('' == $body) {
                throw new BadRequestHttpException('Request body cannot be empty.');
            }

            $maxBot = $this->maxBotFactory->create();
            $update = $maxBot::makeUpdateFromString($body);

            $isHandled = $this->maxUpdateHandler->handle($maxBot, $update);
        } catch (InvalidWebhookSecretException $exception) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $exception->getMessage()],
                Response::HTTP_FORBIDDEN,
            );
        } catch (BadRequestHttpException $exception) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $event = $this->eventDispatcher->dispatch(new MaxWebhookEvent($request, $update));

        return $event->getResponse() ?: new JsonResponse(
            ['status' => 'ok', 'handled' => $isHandled],
            Response::HTTP_OK,
        );
    }

    private function assertRequest(Request $request): void
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            throw new BadRequestHttpException('Webhook endpoint accepts only POST requests.');
        }

        $contentType = $request->headers->get('Content-Type', '');
        if (!\is_string($contentType) || !\str_contains($contentType, 'application/json')) {
            throw new BadRequestHttpException('Webhook request must have Content-Type: application/json.');
        }

        if (null === $this->webhookSecret) {
            return;
        }

        $headerSecret = $request->headers->get('X-Max-Bot-Api-Secret');
        if (!\is_string($headerSecret) || !\hash_equals($this->webhookSecret, $headerSecret)) {
            throw new InvalidWebhookSecretException();
        }
    }
}
