<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait ApiResponseHelper
{
    protected function apiResponse(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function apiResource(string $type, string $iri, array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(array_merge(['@id' => $iri, '@type' => $type], $data), $status);
    }

    protected function apiCollection(string $context, string $iri, array $members, int $total): JsonResponse
    {
        return new JsonResponse([
            '@context' => $context,
            '@id'      => $iri,
            '@type'    => 'hydra:Collection',
            'hydra:member'     => $members,
            'hydra:totalItems' => $total,
        ]);
    }

    protected function apiError(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            '@context'         => '/api/v1/contexts/Error',
            '@type'            => 'hydra:Error',
            'hydra:title'      => Response::$statusTexts[$status] ?? 'Error',
            'hydra:description' => $message,
            'status'           => $status,
        ], $status);
    }

    protected function handleException(\Throwable $e): JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        }

        return $this->apiError('An unexpected error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
