<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Controller;

use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\CommandBus;
use RevisionTen\CQRS\Tests\Examples\Command\PageCreateCommand;
use RevisionTen\CQRS\Tests\Examples\Model\Page;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PageController extends AbstractController
{
    public function createPage(CommandBus $commandBus, AggregateFactory $aggregateFactory, string $title)
    {
        $aggregateUuid = Uuid::uuid1()->toString();

        $pageCreateCommand = new PageCreateCommand(1, null, $aggregateUuid, 0, [
            'title' => $title,
        ]);

        // Execute Command.
        $success = $commandBus->dispatch($pageCreateCommand);

        if (!$success) {
            return new Response('fail', 500);
        }

        $aggregate = $aggregateFactory->build($aggregateUuid, Page::class);

        return new JsonResponse([
            'aggregate' => $aggregate,
        ], 200);
    }
}
