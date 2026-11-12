<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,

            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'name' => $this->employee->name,
                    'email' => $this->employee->email,
                ];
            }),

            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', function () {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                ] : null;
            }),

            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status->value,

            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'comments' => $this->whenLoaded('comments', function () {
                return $this->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'user' => $comment->user ? [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'email' => $comment->user->email,
                        ] : null,
                        'created_at' => $comment->created_at?->toISOString(),
                    ];
                });
            }),

            'activities' => $this->whenLoaded('activities', function () {
                return $this->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'action' => $activity->action,
                        'from_status' => $activity->from_status,
                        'to_status' => $activity->to_status,
                        'comment' => $activity->comment,
                        'actor' => $activity->actor ? [
                            'id' => $activity->actor->id,
                            'name' => $activity->actor->name,
                        ] : null,
                        'created_at' => $activity->created_at?->toISOString(),
                    ];
                });
            }),
        ];
    }
}
