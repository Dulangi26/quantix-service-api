<?php

namespace App\Enums;

enum RequestStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case RETURNED = 'returned';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
