<?php

declare(strict_types=1);

namespace RevisionTen\CQRS;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function define;
use function defined;

class CQRSBundle extends Bundle
{
    public const VERSION = '3.0.0';

    private function setConstants(): void
    {
        if (!defined('CODE_BAD_REQUEST')) {
            define('CODE_BAD_REQUEST', 400);
        }
        if (!defined('CODE_OK')) {
            define('CODE_OK', 200);
        }
        if (!defined('CODE_CREATED')) {
            define('CODE_CREATED', 201);
        }
        if (!defined('CODE_ERROR')) {
            define('CODE_ERROR', 500);
        }
        if (!defined('CODE_DEFAULT')) {
            define('CODE_DEFAULT', 0);
        }
        if (!defined('CODE_CONFLICT')) {
            define('CODE_CONFLICT', 409);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->setConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $this->setConstants();
    }
}
