<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Comment;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RequestWorkflowController extends Controller
{
    public function __construct(
        private RequestWorkflowService $workflow
    ) {}

    public function submit(
        Request $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        $actor = $request->user();

        abort_unless(
            $actor->role === UserRole::EMPLOYEE
            && $serviceRequest->employee_id === $actor->id,
            403,
            'Only the request owner can submit this request.'
        );

        $updated = $this->workflow->transition(
            $serviceRequest,
            $actor,
            RequestStatus::SUBMITTED
        );

        return response()->json([
            'message' => 'Service request submitted successfully.',
            'data' => new ServiceRequestResource($updated),
        ]);
    }

    public function decision(
        Request $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        $actor = $request->user();

        abort_unless(
            $actor->role === UserRole::MANAGER
            && $serviceRequest->employee()
                ->where('manager_id', $actor->id)
                ->exists(),
            403,
            'Only the employee’s manager can make this decision.'
        );

        $validated = $request->validate([
            'decision' => [
                'required',
                'string',
                Rule::in(['approved', 'rejected', 'returned']),
            ],
            'comment' => [
                Rule::requiredIf(
                    in_array($request->input('decision'), ['rejected', 'returned'], true)
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $updated = $this->workflow->transition(
            $serviceRequest,
            $actor,
            RequestStatus::from($validated['decision']),
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Manager decision recorded successfully.',
            'data' => new ServiceRequestResource($updated),
        ]);
    }

    public function assign(
        Request $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        $actor = $request->user();

        abort_unless(
            $actor->role === UserRole::OPERATIONS,
            403,
            'Only Operations users can assign requests.'
        );

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'operations'),
            ],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = DB::transaction(function () use (
            $serviceRequest,
            $actor,
            $validated
        ) {
            $updated = $this->workflow->transition(
                $serviceRequest,
                $actor,
                RequestStatus::ASSIGNED,
                $validated['comment'] ?? null
            );

            $updated->assigned_to = $validated['assigned_to'];
            $updated->save();

            return $updated->fresh(['employee', 'assignee']);
        });

        return response()->json([
            'message' => 'Service request assigned successfully.',
            'data' => new ServiceRequestResource($updated),
        ]);
    }

    public function updateStatus(
        Request $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        $actor = $request->user();

        abort_unless(
            $actor->role === UserRole::OPERATIONS
            && $serviceRequest->assigned_to === $actor->id,
            403,
            'Only the assigned Operations user can update this request.'
        );

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['in_progress', 'completed']),
            ],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->workflow->transition(
            $serviceRequest,
            $actor,
            RequestStatus::from($validated['status']),
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Service request status updated successfully.',
            'data' => new ServiceRequestResource($updated),
        ]);
    }

    public function addComment(
        Request $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        Gate::authorize('view', $serviceRequest);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $comment = Comment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'created_at' => now(),
        ]);

        RequestActivity::create([
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $request->user()->id,
            'action' => 'comment_added',
            'comment' => $validated['comment'],
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => [
                'id' => $comment->id,
                'service_request_id' => $comment->service_request_id,
                'user_id' => $comment->user_id,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at,
            ],
        ], 201);
    }
}
