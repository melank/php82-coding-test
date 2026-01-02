<?php

declare(strict_types=1);

namespace problems\problem01\src;

final class InMemoryProductCatalog
{
    public readonly array $catalog;

    public function __construct(array $catalog) {
        $this->catalog = $catalog;
    }
}
