<?php
declare(strict_types=1);

namespace App\Support;

final class Paginator
{
    public readonly int $pages;
    public readonly int $offset;

    public function __construct(public readonly int $total, public readonly int $page, public readonly int $perPage = 25)
    {
        $this->pages = max(1, (int) ceil($total / $perPage));
        $this->offset = (min(max(1, $page), $this->pages) - 1) * $perPage;
    }

    public function current(): int
    {
        return min(max(1, $this->page), $this->pages);
    }
}
