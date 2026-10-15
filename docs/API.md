# LMS REST API — Reference

Versioned JSON API for the LMS, served by Laravel Sanctum token authentication.
This document describes the endpoints that actually exist in
[routes/api.php](../routes/api.php) — it is generated from the source, not aspirational.

- **Base URL:** `{APP_URL}/api/v1`
- **Version:** v1
- **Content type:** `application/json` (file uploads use `multipart/form-data`)
- **Auth scheme:** Bearer token (Sanctum personal access token)

> **System of record.** AA (Academic Affairs) is authoritative for official student
> records, official course enrollment, official faculty assignments, and final grades.
> The API therefore exposes enrollment **read-only**; the only writer of enrollment
> records is the Super Admin's **Sync from AA** (see [AGENTS.md](../AGENTS.md)).

## Authentication

### `POST /api/v1/auth/login`

| Field | Type | Required | Notes |
|---|---|---|---|
| `email` | string | yes | Case-insensitive; trimmed |
| `password` | string | yes | |
| `device_name` | string | no | Token label, defaults to `api-token` |

Rate limited to **10 requests/minute** per IP (`throttle:10,1`). Per-account
lockout also applies after `LMS_LOGIN_MAX_ATTEMPTS` failures
(`LoginThrottleService`).

Success — `200`:

```json
{
  "data": {
    "user": {
      "id": 3,
      "name": "Juan Dela Cruz",
      "email": "student@lms.local",
      "role": "student"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

Failure — `422` with `errors.email` (`The provided credentials are incorrect.`)
or (`This account is not active.`).

### `POST /api/v1/auth/logout`

Requires auth. Revokes the token used for the call. Returns `{"message":"Logged out."}`.

### `GET /api/v1/auth/me`

Requires auth. Returns `data` with `id`, `name`, `email`, `identifier`, `role`, `status`.

### Sending the token

```http
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

Authenticated endpoints are rate limited to **60 requests/minute**.

## Authorization model

Two independent server-side layers guard every endpoint:

1. **Authentication** — `auth:sanctum` on the whole authenticated group.
2. **Permission** — a `permission:<name>` route middleware per resource group.

Roles are exactly four: `admin` (Super Admin), `instructor` (Faculty),
`student`, and `aa_staff` (Academic Officer, read-only in the LMS).
Permissions are resolved from `role_permissions`; see
[PERMISSIONS_DOCUMENTATION.md](../PERMISSIONS_DOCUMENTATION.md).

## Conventions

**Pagination.** Collection endpoints return a Laravel length-aware paginator.
Pass `per_page` (default `15`).

```json
{
  "data": [ { "id": 1 } ],
  "links": { "first": "…", "last": "…", "prev": null, "next": "…" },
  "meta": { "current_page": 1, "per_page": 15, "total": 42, "last_page": 3 }
}
```

Single resources are wrapped in `data`.

## Endpoints

### Academic periods

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/academic-periods` | `academic_periods.view` | Filters: `search`, `status` |
| GET | `/academic-periods/{id}` | `academic_periods.view` | |
| POST | `/academic-periods` | `academic_periods.create` | |
| PUT | `/academic-periods/{id}` | `academic_periods.update` | |
| DELETE | `/academic-periods/{id}` | `academic_periods.delete` | |

### Courses

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/courses` | `courses.view` | Filters: `search`, `status`, `academic_period_id` |
| GET | `/courses/{id}` | `courses.view` | |
| POST | `/courses` | `courses.create` | |
| PUT | `/courses/{id}` | `courses.update` | |
| DELETE | `/courses/{id}` | `courses.delete` | |

### Classes (course offerings)

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/classes` | `classes.view` | Filters: `search`, `status`, `course_id`, `instructor_id` |
| GET | `/classes/{id}` | `classes.view` | Loads `course`, `instructor`, `academicPeriod`, `enrollments` |
| POST | `/classes` | `classes.create` | |
| PUT | `/classes/{id}` | `classes.update` | |
| DELETE | `/classes/{id}` | `classes.delete` | |

### Enrollments — read-only by design

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/enrollments` | `enrollments.view` | Filters: `student_id`, `class_id`, `status` |
| GET | `/enrollments/{id}` | `enrollments.view` | Loads `student`, `class.course`, `class.instructor` |

There is **no** `POST`, `PUT`, or `DELETE` for enrollments. Requests to those
methods return `405 Method Not Allowed`. Enrollment records are created and
updated only by `AaSyncService` during **Sync from AA**.

The enrollment payload includes the LMS-computed `final_grade`, which is an
internal reference value — **not** the official final grade, which stays in AA.

### Users

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/users` | `users.view` | Filters: `search`, `status`, `role_id` |
| GET | `/users/{id}` | `users.view` | |
| POST | `/users` | `users.create` | |
| PUT | `/users/{id}` | `users.update` | |
| DELETE | `/users/{id}` | `users.delete` | |

### Files

| Method | Path | Permission | Notes |
|---|---|---|---|
| POST | `/files/upload` | `media.upload` | `file` (max 10 MB), mimes: `pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mp3,zip,txt` |
| POST | `/files/upload-multiple` | `media.upload` | `files[]` (max 10 items), optional `folder` |
| DELETE | `/files` | `media.delete` | Body `file_id`. Only the original uploader may delete; otherwise `403` |
| GET | `/files/info` | — (auth only) | Query `file_id` |

## Errors

| Status | Meaning |
|---|---|
| `401` | Missing or invalid token |
| `403` | Token valid but the role/permission (or record ownership) denies the action |
| `404` | Model not found |
| `405` | Method not allowed — e.g. any write to `/enrollments` |
| `422` | Validation failure; body contains `errors` (or `success: false` for file routes) |
| `429` | Rate limit exceeded |
| `500` | Server error (file routes return `{"success":false,"message":…}`) |

## Quick start

```bash
BASE=https://lms.example.edu/api/v1

TOKEN=$(curl -s -X POST "$BASE/auth/login" \
  -H 'Accept: application/json' \
  -d 'email=admin@lms.local&password=Password123!&device_name=curl' \
  | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')

curl -s "$BASE/auth/me" -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
curl -s "$BASE/courses?per_page=5" -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

## Scope

The REST API currently covers identity and the academic structure
(users, courses, classes, enrollment, academic periods) plus file storage.
Instructional features — modules, lessons, materials, quizzes, assignments,
virtual classes and reports — are served through the authenticated web
interface (`routes/web.php`), not this API. Do not assume an endpoint exists
unless it is listed above.
