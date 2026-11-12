<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ServiceRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $requestNumber,
        public string $requestTitle,
        public string $fromStatus,
        public string $toStatus,
        public string $actorName,
        public ?string $comment = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'request_number' => $this->requestNumber,
            'title' => $this->requestTitle,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'actor_name' => $this->actorName,
            'comment' => $this->comment,
            'message' => "Request {$this->requestNumber} changed from {$this->fromStatus} to {$this->toStatus}.",
        ];
    }
}
