# FlowDesk – Service Request & Approval API

A role-based RESTful API built with Laravel for managing internal employee service requests through a structured approval and fulfilment workflow.

## 1. Overview

FlowDesk allows employees to submit service requests, managers to review requests from their direct reports, and Operations users to manage approved requests through assignment and completion.

### Main Features

- Token-based authentication using Laravel Sanctum.
- Role-based access control for Employees, Managers and Operations.
- Create, view, update and track service requests.
- Manager approval, rejection and return workflows.
- Operations assignment and request status management.
- Comments and request activity audit trail.
- Database-backed queued notifications.
- Request filtering, searching, sorting and pagination.
- Input validation and consistent JSON API responses.

## 2. Technology Stack

| Component | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Database | MySQL |
| Authentication | Laravel Sanctum |
| ORM | Eloquent |
| Validation | Laravel Form Requests |
| API Responses | Laravel API Resources |
| Background Jobs | Laravel Queue |
| Testing | PHPUnit |
| Code Formatting | Laravel Pint |

## 3. User Roles and Permissions

| Role | Permissions |
|---|---|
| Employee | Create requests, edit own Draft or Returned requests, submit requests, view own requests and add comments. |
| Manager | Review requests submitted by direct reports, approve, reject or return requests, and add comments. |
| Operations | Assign approved requests, start processing, complete requests and add comments to accessible requests. |

Access is restricted according to user roles and request ownership or assignment.

## 4. Request Workflow

The supported request lifecycle is:

`Draft → Submitted → Approved → Assigned → In Progress → Completed`

Additional decision paths:

- Submitted → Returned → Submitted
- Submitted → Rejected

Rejected and Completed requests are final states and cannot be reopened.

Every status transition records the previous status, new status, acting user, timestamp and optional comment in the request activity history.

## 5. Requirements

- PHP 8.2 or later
- Composer
- MySQL 8 or compatible MySQL version
- Git

## 6. Installation and Setup

### Step 1 – Clone the repository

```bash
git clone https://github.com/Dulangi26/quantix-service-api.git
cd quantix-service-api
```

### Step 2 – Install dependencies

```bash
composer install
```

### Step 3 – Configure environment variables

Copy the example environment file:

**Windows PowerShell:**

```powershell
Copy-Item .env.example .env
```

Update the database configuration in `.env`:

```env
APP_NAME=FlowDesk
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quantix_service_api
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password

QUEUE_CONNECTION=database
```

Create a MySQL database named `quantix_service_api` before running the migrations.

### Step 4 – Generate application key

```bash
php artisan key:generate
```

### Step 5 – Run migrations and seed demo data

```bash
php artisan migrate --seed
```

This creates the required database tables and sample users and service requests.

### Step 6 – Start the API server

```bash
php artisan serve
```

The API will be available at:

`http://127.0.0.1:8000`

### Step 7 – Start the queue worker

In a separate terminal:

```bash
php artisan queue:work database
```

The worker processes queued database notifications.

## 7. Demo Accounts

The database seeder creates the following accounts for testing:

| Role | Email | Password |
|---|---|---|
| Manager | manager@flowdesk.test | password |
| Employee | employee1@flowdesk.test | password |
| Employee | employee2@flowdesk.test | password |
| Employee | employee3@flowdesk.test | password |
| Operations | operations@flowdesk.test | password |

**Note:** These are development-only credentials. Do not use them in production.

## 8. API Endpoints

All endpoints use the `/api` prefix.

### Authentication

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| POST | `/api/login` | Authenticate and obtain an access token | No |
| GET | `/api/me` | Retrieve authenticated user details | Yes |
| POST | `/api/logout` | Revoke current access token | Yes |

### Service Requests

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/requests` | Create a service request |
| GET | `/api/requests` | List accessible service requests |
| GET | `/api/requests/{id}` | Retrieve a specific request |
| PUT | `/api/requests/{id}` | Update an eligible request |

### Workflow

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/requests/{id}/submit` | Submit a Draft or Returned request |
| POST | `/api/requests/{id}/decision` | Approve, reject or return a submitted request |
| POST | `/api/requests/{id}/assign` | Assign an approved request |
| POST | `/api/requests/{id}/status` | Update an assigned request's processing status |
| POST | `/api/requests/{id}/comments` | Add a comment to a request |

### Request Filtering and Sorting

The request listing endpoint supports:

- `status` – Filter by request status.
- `priority` – Filter by priority.
- `search` – Search request information.
- `sort_by` – Sort by supported fields such as `created_at`, `title` or `priority`.
- `sort_direction` – `asc` or `desc`.
- Pagination – Results are returned in paginated form.

Example:

```http
GET /api/requests?status=submitted&priority=high&sort_by=created_at&sort_direction=desc
```

## 9. Example API Requests

### Login

```http
POST /api/login
Content-Type: application/json
```

```json
{
    "email": "employee1@flowdesk.test",
    "password": "password"
}
```

### Create a Request

```http
POST /api/requests
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

```json
{
    "title": "Office Laptop Request",
    "description": "Requesting a laptop for development work.",
    "priority": "high"
}
```

### Submit a Request

```http
POST /api/requests/1/submit
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Manager Decision

```http
POST /api/requests/1/decision
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

```json
{
    "decision": "approved",
    "comment": "Approved for operational processing."
}
```

### Assign a Request

```http
POST /api/requests/1/assign
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

```json
{
    "assigned_to": 5,
    "comment": "Assigned to the Operations team."
}
```

### Update Request Status

```http
POST /api/requests/1/status
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

```json
{
    "status": "in_progress",
    "comment": "Work has started."
}
```

### Add a Comment

```http
POST /api/requests/1/comments
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

```json
{
    "comment": "Please provide an update on this request."
}
```

Replace the example request ID and access token with values from your database and login response.

## 10. Database Design

The main database entities are:

- **users** – User accounts, roles and manager relationships.
- **service_requests** – Request details, ownership, assignment and workflow status.
- **comments** – Comments associated with requests and users.
- **request_activities** – Audit history of status changes and request actions.
- **notifications** – Database-backed notifications.
- **personal_access_tokens** – Sanctum authentication tokens.

Foreign keys and indexes support data integrity and common request queries.

## 11. Architecture and Code Organization

The application follows Laravel's MVC-oriented structure with dedicated service, validation, authorization and response layers.

| Directory | Responsibility |
|---|---|
| `app/Enums` | User roles and request statuses |
| `app/Models` | Eloquent models and relationships |
| `app/Http/Controllers/Api` | API request handling |
| `app/Http/Requests` | Input validation |
| `app/Http/Resources` | Consistent JSON response formatting |
| `app/Policies` | Role and resource authorization |
| `app/Services` | Request workflow and business rules |
| `app/Notifications` | Queued request status notifications |
| `database/migrations` | Database schema |
| `database/factories` | Test data factories |
| `database/seeders` | Demo data |
| `tests` | Automated tests |

The `RequestWorkflowService` centralizes status transitions and audit recording. Database transactions and row locking help maintain workflow consistency.

## 12. Testing

Run the automated test suite:

```bash
php artisan test
```

Run the code formatting check:

```bash
vendor/bin/pint --test
```

The implemented feature tests cover:

- Employee request submission.
- Manager approval of direct-report requests.
- Prevention of unauthorized employee access.
- Prevention of reopening completed requests.

## 13. Error Handling

The API uses appropriate HTTP status codes, including:

| Status | Meaning |
|---|---|
| 200 | Successful request |
| 201 | Resource created |
| 401 | Unauthenticated or invalid credentials |
| 403 | Forbidden action |
| 404 | Resource not found |
| 422 | Validation error or invalid workflow transition |

Validation and workflow errors are returned as JSON responses.

## 14. Security Considerations

- Sanctum Bearer token authentication.
- Role-based authorization and resource policies.
- Password hashing.
- Request input validation.
- Protected API routes.
- Database constraints and transactional workflow updates.
- Environment variables for sensitive configuration.

Never commit the `.env` file or production credentials.

## 15. License

This project was developed as a software engineering assignment. Laravel is distributed under the MIT License.