<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
