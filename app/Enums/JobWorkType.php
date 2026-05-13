<?php

namespace App\Enums;

enum JobWorkType: string
{
    case Hybrid = 'hybrid';
    case Remote = 'remote';
    case Fulltime = 'fulltime';
    case Contract = 'contract';
    case Onsite = 'onsite';
    case Freelancing = 'freelancing';
}
