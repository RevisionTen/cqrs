<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Controller;

use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\CommandBus;
use RevisionTen\CQRS\Tests\Examples\Command\PageCreateCommand;
use RevisionTen\CQRS\Tests\Examples\Model\Page;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PageController extends Controller
{
    public function createPage($title)
    {
        $aggregateUuid = Uuid::uuid1()->toString();

        $success = false;
        $successCallback = function ($commandBus, $event) use (&$success) { $success = true; };

        $pageCreateCommand = new PageCreateCommand(1, null, $aggregateUuid, 0, [
            'title' => $title,
        ], $successCallback);

        // Execute Command.
        $this->get('commandbus')->dispatch($pageCreateCommand);

        if (!$success) {
            return new Response('fail', 500);
        }

        /** @var AggregateFactory $aggregateFactory */
        $aggregateFactory = $this->get('aggregatefactory');
        $aggregate = $aggregateFactory->build($aggregateUuid, Page::class);

        return new JsonResponse($aggregate ?? 'fail', 200);
    }
}
