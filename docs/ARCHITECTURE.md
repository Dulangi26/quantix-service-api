# FlowDesk – System Architecture

## 1. Overview

FlowDesk is a RESTful API built with Laravel for managing employee service requests through a role-based approval workflow.

The application supports three user roles:

- **Employee:** Creates, updates, submits and tracks service requests.
- **Manager:** Reviews requests submitted by direct reports and approves, rejects or returns them.
- **Operations:** Assigns approved requests, starts processing and completes requests.

The system uses a layered architecture to separate HTTP handling, business logic, data access and authorization.

## 2. Technology Stack

- **Backend:** PHP 8.2+, Laravel 12
- **Database:** MySQL 8
- **Authentication:** Laravel Sanctum
- **Validation:** Laravel Form Requests
- **API Responses:** Laravel API Resources
- **Notifications:** Laravel Database Notifications with queued processing
- **Testing:** PHPUnit
- **Code Formatting:** Laravel Pint
- **API Documentation:** Postman Collection

## 3. Application Architecture

The application follows a layered structure:

### Controllers

Located in `app/Http/Controllers/Api/`.

Controllers receive API requests, authorize actions, validate input through Form Requests and return JSON responses.

### Form Requests

Located in `app/Http/Requests/`.

Form Requests handle request validation and keep validation rules separate from controller logic.

### Models

Located in `app/Models/`.

Eloquent models represent database entities and define relationships between users, service requests, comments and activities.

### Policies

Located in `app/Policies/`.

`ServiceRequestPolicy` controls access to service requests based on the authenticated user's role and relationship with the request.

### Services

Located in `app/Services/`.

`RequestWorkflowService` contains the business rules for status transitions. It uses database transactions and row locking to protect workflow updates.

### API Resources

Located in `app/Http/Resources/`.

API Resources control the JSON representation of users and service requests.

### Notifications

Located in `app/Notifications/`.

`ServiceRequestStatusChanged` provides queued database notifications when request statuses change.

## 4. Database Design

The main database entities are:

- **users:** Stores employee, manager and operations accounts, including role and manager relationship.
- **service_requests:** Stores request details, ownership, assignment, priority, status and workflow timestamps.
- **comments:** Stores comments added by users to service requests.
- **request_activities:** Maintains an audit history of workflow transitions and other request activities.
- **notifications:** Stores database notifications for users.

### Key Relationships

- A manager can have multiple direct reports.
- An employee can create multiple service requests.
- A service request belongs to an employee.
- A service request can be assigned to an operations user.
- A service request can have multiple comments.
- A service request can have multiple activity records.
- A user can create multiple comments and activity records.

Foreign keys and indexes support referential integrity and common filtering operations.

## 5. Authentication and Authorization

Laravel Sanctum provides API token authentication.

Users authenticate through the login endpoint and receive a bearer token. Protected routes require a valid token.

Role-based authorization is enforced through the service request policy and controller-level role checks.

- Employees can access their own requests.
- Managers can review requests belonging to their direct reports.
- Operations users can access requests assigned to them and perform operational actions.

## 6. Service Request Workflow

The workflow is managed centrally by `RequestWorkflowService`.

Supported transitions:

| Current Status | Allowed Next Status |
|---|---|
| Draft | Submitted |
| Returned | Submitted |
| Submitted | Approved, Rejected, Returned |
| Approved | Assigned |
| Assigned | In Progress |
| In Progress | Completed |

Rejected and Completed requests are final states.

Invalid transitions are rejected with a validation error.

The workflow service records the previous status, new status, actor, timestamp and optional comment in the request activity history.

Database transactions and row-level locking help maintain consistency during concurrent workflow updates.

## 7. Audit Trail and Notifications

Each workflow transition creates an activity record containing the actor and transition details.

Comments are stored separately and also recorded in the activity history.

Status-change notifications are dispatched to relevant users through Laravel's notification system and queued for processing.

## 8. Testing

Feature tests cover:

- Employee submission of their own draft request.
- Manager approval of a direct report's request.
- Prevention of unauthorized access to another employee's request.
- Prevention of reopening completed requests.

The test suite uses an isolated SQLite in-memory database to avoid modifying the development MySQL database.

## 9. Performance and Maintainability

The implementation includes:

- Pagination for request listings.
- Filtering by status and priority.
- Search and sorting options.
- Eager loading of related records.
- Database indexes for frequently queried fields.
- Form Request validation.
- Reusable workflow business logic.
- Consistent API resource responses.