<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Requests\UpdateServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ServiceRequest::with(['employee', 'assignee']);

        // Restrict results according to the logged-in user's role.
        if ($user->role === UserRole::EMPLOYEE) {
            $query->where('employee_id', $user->id);
        } elseif ($user->role === UserRole::MANAGER) {
            $query->whereHas('employee', function ($employeeQuery) use ($user) {
                $employeeQuery->where('manager_id', $user->id);
            });
        } else {
            $query->where('assigned_to', $user->id);
        }

        // Optional filters.
        if ($request->filled('status')) {
            $request->validate([
                'status' => [
                    'in:draft,submitted,returned,approved,rejected,assigned,in_progress,completed',
                ],
            ]);

            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $request->validate([
                'priority' => ['in:low,medium,high'],
            ]);

            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('request_number', 'like', "%{$search}%");
            });
        }

        // Only allow supported sorting columns.
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        if (! in_array($sortBy, ['created_at', 'title', 'priority'], true)) {
            $sortBy = 'created_at';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $requests = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return ServiceRequestResource::collection($requests);
    }

    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        Gate::authorize('create', ServiceRequest::class);

        $serviceRequest = $request->user()->serviceRequests()->create([
            'request_number' => 'FD-'.Str::upper(Str::random(8)),
            'title' => $request->validated()['title'],
            'description' => $request->validated()['description'],
            'priority' => $request->validated()['priority'] ?? 'medium',
            'status' => 'draft',
        ]);

        $serviceRequest->load(['employee', 'assignee']);

        return response()->json([
            'message' => 'Service request created successfully.',
            'data' => ServiceRequestResource::make($serviceRequest),
        ], 201);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        Gate::authorize('view', $serviceRequest);

        $serviceRequest->load(['employee', 'assignee', 'comments.user', 'activities.actor']);

        return ServiceRequestResource::make($serviceRequest);
    }

    public function update(
        UpdateServiceRequestRequest $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        Gate::authorize('update', $serviceRequest);

        $serviceRequest->update($request->validated());

        $serviceRequest->load(['employee', 'assignee']);

        return response()->json([
            'message' => 'Service request updated successfully.',
            'data' => ServiceRequestResource::make($serviceRequest),
        ]);
    }
}
