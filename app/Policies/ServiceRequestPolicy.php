<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return match ($user->role) {
            UserRole::EMPLOYEE => $serviceRequest->employee_id === $user->id,

            UserRole::MANAGER => $serviceRequest->employee()->where('manager_id', $user->id)->exists(),

            UserRole::OPERATIONS => $serviceRequest->assigned_to === $user->id,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::EMPLOYEE;
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::EMPLOYEE
            && $serviceRequest->employee_id === $user->id
            && in_array($serviceRequest->status->value, ['draft', 'returned'], true);
    }
}
