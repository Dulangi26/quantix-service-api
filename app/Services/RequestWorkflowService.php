<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RequestWorkflowService
{
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'returned' => ['submitted'],
        'submitted' => ['approved', 'rejected', 'returned'],
        'approved' => ['assigned'],
        'assigned' => ['in_progress'],
        'in_progress' => ['completed'],
        'rejected' => [],
        'completed' => [],
    ];

    public function transition(
        ServiceRequest $serviceRequest,
        User $actor,
        RequestStatus $newStatus,
        ?string $comment = null
    ): ServiceRequest {
        $result = DB::transaction(function () use (
            $serviceRequest,
            $actor,
            $newStatus,
            $comment
        ) {
            $lockedRequest = ServiceRequest::query()
                ->lockForUpdate()
                ->findOrFail($serviceRequest->id);

            $oldStatus = $lockedRequest->status;
            $allowed = self::TRANSITIONS[$oldStatus->value] ?? [];

            if (! in_array($newStatus->value, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change status from {$oldStatus->value} to {$newStatus->value}.",
                    ],
                ]);
            }

            $lockedRequest->status = $newStatus;

            if ($newStatus === RequestStatus::SUBMITTED) {
                $lockedRequest->submitted_at = now();
            }

            if ($newStatus === RequestStatus::APPROVED) {
                $lockedRequest->approved_at = now();
            }

            if ($newStatus === RequestStatus::COMPLETED) {
                $lockedRequest->completed_at = now();
            }

            $lockedRequest->save();

            RequestActivity::create([
                'service_request_id' => $lockedRequest->id,
                'actor_id' => $actor->id,
                'action' => 'status_changed',
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'comment' => $comment,
                'created_at' => now(),
            ]);

            return [
                'request' => $lockedRequest->fresh([
                    'employee.manager',
                    'assignee',
                    'activities.actor',
                ]),
                'old_status' => $oldStatus->value,
            ];
        });

        $updatedRequest = $result['request'];

        $this->sendStatusNotification(
            $updatedRequest,
            $actor,
            $result['old_status'],
            $newStatus->value,
            $comment
        );

        return $updatedRequest;
    }

    private function sendStatusNotification(
        ServiceRequest $serviceRequest,
        User $actor,
        string $oldStatus,
        string $newStatus,
        ?string $comment
    ): void {
        $recipients = collect();

        if ($newStatus === RequestStatus::SUBMITTED->value) {
            // Notify the employee's manager when a request is submitted.
            if ($serviceRequest->employee?->manager) {
                $recipients->push($serviceRequest->employee->manager);
            }
        } else {
            // Notify the employee about decisions and operational updates.
            if ($serviceRequest->employee) {
                $recipients->push($serviceRequest->employee);
            }
        }

        // Notify the assigned Operations user when a request is assigned.
        if (
            $newStatus === RequestStatus::ASSIGNED->value
            && $serviceRequest->assignee
        ) {
            $recipients->push($serviceRequest->assignee);
        }

        // Avoid notifying the user who performed the action.
        $recipients = $recipients
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new ServiceRequestStatusChanged(
                requestNumber: $serviceRequest->request_number,
                requestTitle: $serviceRequest->title,
                fromStatus: $oldStatus,
                toStatus: $newStatus,
                actorName: $actor->name,
                comment: $comment,
            )
        );
    }
}
