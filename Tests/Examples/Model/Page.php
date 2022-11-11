<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Model;

use RevisionTen\CQRS\Model\Aggregate;

final class Page extends Aggregate
{
    public ?string $title = null;
}
