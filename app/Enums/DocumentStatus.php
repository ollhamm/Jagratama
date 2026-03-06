<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case IN_REVIEW = 'IN_REVIEW';
    case REJECTED = 'REJECTED';
    case APPROVED = 'APPROVED';
    case COMPLETED = 'COMPLETED';
}
