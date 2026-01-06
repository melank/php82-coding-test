<?php

declare(strict_types=1);

enum OrderStatus: string
{
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
