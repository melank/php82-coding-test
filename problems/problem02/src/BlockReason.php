<?php

declare(strict_types=1);

namespace problems\problem02\src;

enum BlockReason {
    case TooManyFailures;
}