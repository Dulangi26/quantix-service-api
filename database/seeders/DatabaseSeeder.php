<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create the Manager.
        $manager = User::factory()->create([
            'name' => 'FlowDesk Demo Manager',
            'email' => 'manager@flowdesk.test',
            'password' => 'password',
            'role' => UserRole::MANAGER,
        ]);

        // Create two Employees reporting to this Manager.
        $employeeOne = User::factory()->create([
            'name' => 'Alex Employee',
            'email' => 'employee1@flowdesk.test',
            'password' => 'password',
            'role' => UserRole::EMPLOYEE,
            'manager_id' => $manager->id,
        ]);

        $employeeTwo = User::factory()->create([
            'name' => 'Sam Employee',
            'email' => 'employee2@flowdesk.test',
            'password' => 'password',
            'role' => UserRole::EMPLOYEE,
            'manager_id' => $manager->id,
        ]);

        // Create an Employee outside this Manager's reporting structure.
        $employeeThree = User::factory()->create([
            'name' => 'Taylor Employee',
            'email' => 'employee3@flowdesk.test',
            'password' => 'password',
            'role' => UserRole::EMPLOYEE,
            'manager_id' => null,
        ]);

        // Create the Operations user.
        $operations = User::factory()->create([
            'name' => 'FlowDesk Operations',
            'email' => 'operations@flowdesk.test',
            'password' => 'password',
            'role' => UserRole::OPERATIONS,
        ]);

        // Draft request.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeOne->id,
            'title' => 'Office Equipment Request',
            'status' => RequestStatus::DRAFT,
        ]);

        // Submitted requests.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeOne->id,
            'title' => 'New Software License',
            'status' => RequestStatus::SUBMITTED,
            'submitted_at' => now()->subDays(2),
        ]);

        ServiceRequest::factory()->create([
            'employee_id' => $employeeTwo->id,
            'title' => 'Additional Monitor Request',
            'status' => RequestStatus::SUBMITTED,
            'submitted_at' => now()->subDay(),
        ]);

        // Returned request.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeTwo->id,
            'title' => 'Office Access Request',
            'status' => RequestStatus::RETURNED,
            'submitted_at' => now()->subDays(3),
        ]);

        // Approved requests.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeOne->id,
            'title' => 'Training Budget Request',
            'status' => RequestStatus::APPROVED,
            'submitted_at' => now()->subDays(4),
            'approved_at' => now()->subDays(2),
        ]);

        ServiceRequest::factory()->create([
            'employee_id' => $employeeTwo->id,
            'title' => 'Business Travel Request',
            'status' => RequestStatus::APPROVED,
            'submitted_at' => now()->subDays(3),
            'approved_at' => now()->subDay(),
        ]);

        // Rejected request.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeOne->id,
            'title' => 'Premium Subscription Request',
            'status' => RequestStatus::REJECTED,
            'submitted_at' => now()->subDays(5),
        ]);

        // Assigned request.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeOne->id,
            'assigned_to' => $operations->id,
            'title' => 'Network Equipment Installation',
            'status' => RequestStatus::ASSIGNED,
            'submitted_at' => now()->subDays(5),
            'approved_at' => now()->subDays(3),
        ]);

        // In-progress request.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeTwo->id,
            'assigned_to' => $operations->id,
            'title' => 'Workstation Setup',
            'status' => RequestStatus::IN_PROGRESS,
            'submitted_at' => now()->subDays(6),
            'approved_at' => now()->subDays(4),
        ]);

        // Completed request.
        ServiceRequest::factory()->create([
            'employee_id' => $employeeThree->id,
            'assigned_to' => $operations->id,
            'title' => 'Email Account Configuration',
            'status' => RequestStatus::COMPLETED,
            'submitted_at' => now()->subDays(8),
            'approved_at' => now()->subDays(6),
            'completed_at' => now()->subDay(),
        ]);
    }
}
