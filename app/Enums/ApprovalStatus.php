<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case SKIPPED = 'SKIPPED';
}
