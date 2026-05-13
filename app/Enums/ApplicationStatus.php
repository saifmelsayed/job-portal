<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
