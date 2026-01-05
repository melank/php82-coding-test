<?php

declare(strict_types=1);

/**
 * 配送手段を表す Enum
 */
enum ShippingMethod: string
{
    case Standard = 'standard';
    case Express = 'express';
}
