<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_own_draft_request(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => RequestStatus::DRAFT,
        ]);

        $response = $this->actingAs($employee)
            ->postJson("/api/requests/{$serviceRequest->id}/submit");

        $response->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('request_activities', [
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $employee->id,
            'from_status' => 'draft',
            'to_status' => 'submitted',
        ]);
    }

    public function test_manager_can_approve_direct_report_request(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'manager_id' => $manager->id,
        ]);

        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => RequestStatus::SUBMITTED,
        ]);

        $response = $this->actingAs($manager)
            ->postJson("/api/requests/{$serviceRequest->id}/decision", [
                'decision' => 'approved',
                'comment' => 'Approved for processing.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('request_activities', [
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $manager->id,
            'from_status' => 'submitted',
            'to_status' => 'approved',
        ]);
    }

    public function test_employee_cannot_view_another_employees_request(): void
    {
        $employeeOne = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $employeeTwo = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employeeTwo->id,
        ]);

        $response = $this->actingAs($employeeOne)
            ->getJson("/api/requests/{$serviceRequest->id}");

        $response->assertForbidden();
    }

    public function test_completed_request_cannot_be_reopened(): void
    {
        $operations = User::factory()->create([
            'role' => UserRole::OPERATIONS,
        ]);

        $serviceRequest = ServiceRequest::factory()->create([
            'assigned_to' => $operations->id,
            'status' => RequestStatus::COMPLETED,
        ]);

        $response = $this->actingAs($operations)
            ->postJson("/api/requests/{$serviceRequest->id}/status", [
                'status' => 'in_progress',
                'comment' => 'Trying to reopen the request.',
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseCount('request_activities', 0);
    }
}
