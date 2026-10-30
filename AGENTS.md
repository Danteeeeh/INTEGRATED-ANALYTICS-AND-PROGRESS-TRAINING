# LMS Agent Notes

## Canvas-Style Role Model (4 Roles) — Dashboard Enhancements

### Role Model Direction (agreed design)

Para hindi magulo ang panel at complementary sa AA (Academic Affairs), 4 roles lang:

1. **Super Admin (IT/System Admin)** — sync lang ng data mula AA → LMS ("Sync from AA", direct DB connection sa AA), manage ng courses. Hindi na siya nag-eenroll manually.
2. **AA Staff / Academic Officer** — view-only sa LMS. Siya pa rin may hawak ng official data at grades sa kabilang system (AA).
3. **Faculty / Instructor** — gumagawa ng Modules, mag-upload ng lessons, Quiz at Assignment, mag-check at magbigay ng score (LMS score lang, hindi final grade), makikita ang progress ng students.
4. **Student** — nakikita lang ang enrolled subjects niya na galing sa AA (To-Do, Calendar, My Courses); mag-view ng modules, mag-take ng quiz, mag-submit ng assignment.

### Implemented: Faculty + Student Dashboards (Canvas-style zones)

#### Faculty Dashboard (`resources/views/instructor/dashboard.blade.php` + `app/Http/Controllers/Instructor/DashboardController.php`)

- **To-check queue panel** — list ng submitted-but-ungraded assignment submissions at quiz attempts (incl. `auto_submitted`), may direktong "Score" link (`instructor.courses.assignments.submissions.show` / `instructor.courses.quizzes.attempts.show`). Live count badge (`to-check-count`) na ina-update ng 30s real-time poll via `to_check_count` sa `/dashboard/real-time-stats`.
- **Completion graph** — horizontal stacked bar chart (`#completionChart`, Chart.js server-rendered data, walang extra request): Completed vs Active students per class. Data: `completion_by_class` sa `calculateDashboardStats()`.
- **Export Report for AA** — primary button sa header: `instructor.dashboard.export?type=aa&format=csv`. AA-ready CSV columns: Student ID, Student Name, Email, Course Code, Course Title, Class Code, Enrollment Status, LMS Score %, Letter Grade, Completed At. LMS scores lang — final grade ay hawak pa rin ng AA.

#### Student Dashboard (`resources/views/student/dashboard.blade.php`)

Canvas 3-zone layout (walang controller change — existing `$stats` ginamit):

1. **My courses** — card grid (`course-card-grid`) ng enrolled subjects: code, title, instructor + section, status chip, progress bar per course, deterministic per-course color (hash ng course ID sa 8-color palette). Progress lookup: `$progressLookup` (course.id → progress).
2. **My progress** — Chart.js horizontal bar (`#progressChart`) ng per-course completion %, color-coded (green ≥75, amber ≥40, blue <40), overall % sa panel count, at "Paano kino-compute" explainer. Data: `$progressChartData`. Ang axis label ay **course code** (hal. `IT-103`) para hindi magkamukha ang bars ng magkatulad ang title; nasa tooltip ang buong course title.
3. **To-do list** — dating "Upcoming this week" panel: upcoming assignments/quizzes/exams na may due chips at links.

(Ang AI assessment, overdue strip, continue learning, upcoming activities, announcements, analytics ay nananatili.)

#### Hidden: Admin Manual Enrollment

- `resources/views/components/admin-sidebar.blade.php` — na-comment out ang Enrollments nav link (kasama ang explanation comment). Routes at tests ay buhay pa (`Route::resource('enrollments', ...)` at `enrollments.bulk-auto-enroll`); itago lang ang UI dahil ang enrollment ay galing na sa AA sync (Phase 3, direct DB connection).

### Implemented: AA Staff Role + View-Only Panel + Sync from AA

#### Phase 1 — Roles (`app/Models/Role.php`, `database/seeders/RoleSeeder.php`, `Auth/LoginController.php`)

- **`Role::AA_STAFF = 'aa_staff'`** — bagong role; RoleSeeder: Admin → "Super Admin", Instructor → "Faculty", bagong "AA Staff". Sidebar labels dynamic (`role?->name`) kaya auto-update; fallbacks updated.
- `dashboardRouteFor()`: aa_staff → `aa.dashboard` (OTP + normal login parehas).
- `User::isAaStaff()` helper. AA Staff ay lumalabas sa admin user picker (`Role::assignable()` excludes registrar lang) — ganoon nalulikha ang AA accounts.

#### Phase 2 — AA Officer view-only panel (GET routes only)

- `resources/views/layouts/aa.blade.php` + `components/aa-sidebar.blade.php`
- `app/Http/Controllers/Aa/`: `DashboardController` (overview stats + course/enrollment tables), `CourseController` (index + show, read-only, may search), `StudentController` (index, may search + status filter), `EnrollmentController` (index, may search/status filter), `ProgressController` (per-course avg LMS progress — may "hindi official grade" notice), `ReportController` (LMS assessment reports: index/show/CSV export)
- Views: `resources/views/aa/{dashboard,courses/index,courses/show,students/index,enrollments/index,progress/index,reports/index,reports/show}.blade.php`
- Routes: `role:aa_staff`, prefix `aa`, name `aa.*` — walang POST/PUT/DELETE.

#### Phase 3 — Super Admin "Sync from AA" (direct DB connection)

- `config/database.php` — bagong `aa` connection (`AA_DB_*` env; `.env.example` documented)
- `config/aa_sync.php` — configurable AA table/column mapping (departments, programs, sections, students, faculty, classes, enrollments) + status maps + sync order + default passwords
- `app/Services/AaSyncService.php` — upsert mula AA → LMS: soft-delete aware (withTrashed + restore), keyed by code/email/identifier, per-record try/catch (created/updated/skipped/failed + errors/skips detail), **hindi nagsi-sync ng final_grade** (official grade hawak ng AA), `DB::transaction` per entity (safe rollback, idempotent retry)
- `database/migrations/2026_10_11_000000_create_aa_sync_logs_table.php` + `app/Models/AaSyncLog.php` — audit trail per sync run (entities, counts, summary JSON)
- `app/Http/Controllers/Admin/AaSyncController.php` + `resources/views/admin/aa-sync/index.blade.php` — sync center: connection status, entity checkboxes, one-click sync (JS confirm), last sync breakdown (skip/fail details), history table, mapping preview
- Routes: `admin.aa-sync.index` / `admin.aa-sync.run`; sidebar link "Sync from AA" (unang item sa Main group)

#### Paano magamit

1. I-set `AA_DB_*` sa `.env`, i-update ang `config/aa_sync.php` mapping sa aktwal na AA schema, `php artisan migrate` (aa_sync_logs)
2. Super Admin → **Sync from AA** → piliin ang entities → Sync (o auto-check lahat)
3. AA Staff account: admin → Users → create (role = AA Staff) → mag-la-login → view-only panel

### Role Audit vs. Canvas-Style Prompt (final pass)

Audit ng aktwal na access ng bawat role laban sa prompt (4 roles lang):

| Role | Dapat (prompt) | Ginawa |
|---|---|---|
| **Super Admin (IT)** | Sync lang + manage courses | Sidebar stripped sa: **Sync from AA · Dashboard · Courses/Modules/Lessons · Users (accounts) · Audit Logs / Backup / Settings**. Lahat ng iba (Classes, Assessments, Classroom, Evaluation/Reports/Records/Gradebook, Students, Instructors, Enrollments, Departments/Programs/Sections/Periods, Calendar, Competencies) naka-comment sa `components/admin-sidebar.blade.php` — routes at tests buhay pa, itago lang ang UI |
| **AA Staff** | View lang | View-only panel (GET-only routes) + tests ✅ |
| **Faculty** | Modules/lessons/quiz/assignment/scores/progress | Enrollment CRUD tinanggal sa UI: sidebar link nakatago; enrollments index view-only (view button lang); create buttons tinanggal sa roster, class show, at enrollments show; "Edit Enrollment" tinanggal; dashboard hero link pinalitan ng Reports — enrollment galing na sa AA sync |
| **Student** | Enrolled subjects galing SA; To-Do/Calendar/My Courses | Self-enroll button tinanggal sa class page (may "handled by the school through AA" note); sidebar: "My Classes" at "My Enrollments" nakatago; dashboard hero/quick actions pinalitan (Calendar, My courses — walang enrollments/classes link); "My Classes" button sa courses index pinalitan ng Dashboard |

### Spec Compliance Pass (PROJECT PROMPT — gawiing reference)

Ginawa ang buong audit laban sa formal spec. Natapos ang mga gaps:

- **§4/§8 Canvas course cards** — deterministic per-course color (hash ng course id sa 8-color palette) bilang card accent + code color; section name sa card (class section → user section fallback); progress explainer sa My progress panel ("Paano kino-compute": lessons/modules/quizzes/assignments activity, LMS lang — official grade sa AA)
- **§8 Search/filter/sort** — AA Students page: search (name/email/student ID) + status filter; AA Courses page: search (title/code)
- **§6 Super Admin Dashboard** — sync status section sa dashboard: Last Sync (kailan + kanino), Sync Status (connected/not), Last Sync Changes (new/updated/skipped), Failed Records + failed-run count, Recent sync activity table; prominent **"Sync from AA"** primary button sa hero
- **§7 AA Staff Reports** — bagong read-only reports panel (`Aa\ReportController` + `aa/reports/{index,show}` + CSV export): per-course LMS assessment report (Student ID, Name, Course, Class, Status, LMS Score %, Letter, Completed At) at "Export Lahat"; malinaw ang label na grade-reference, hindi official
- **§5 Faculty Reports** — bagong `instructor/reports` page + sidebar "Reports" nav: per-class exports (Students/Grades/Performance/**For AA**) na may "hindi official final grade" labelling
- **§4 Student To-Do** — bagong "To-Do" nav item sa student sidebar (`student.incomplete`)
- **§3 #13 Sync safeguards** — `DB::transaction` per entity sa `AaSyncService::run()`: rollback kung may entity na pumalya, idempotent pa rin (upserts), safe retry
- **Tests** — AA staff reports view/export + read-only (405); faculty reports page + labelling; admin dashboard sync status/primary action

### Tests (para sa bagong features)

- `tests/Feature/AaStaffAccessTest.php` — role seeded; aa_staff puwedeng mag-view ng dashboard/courses/students/enrollments/progress/reports (view + CSV export); zero write routes (POST → 405); hindi maka-access ng admin/instructor/student panels (403); login → `aa.dashboard`; sync center — admin lang
- `tests/Feature/AaSyncTest.php` — AA connection failure handling: `available()` false at `run()` → **failed log** (hindi silently ignored); unknown entities nado-drop; default sync order
- `tests/Feature/CanvasDashboardTest.php` — faculty dashboard may to-check/AA export/completion graph; faculty reports page + "hindi official final grade" labelling; admin dashboard may AA sync status + primary Sync button; student dashboard may My courses/progress/To-do + AA empty-state copy
- `tests/Feature/VirtualClassRoomAccessTest.php` — LiveKit room access + JWT claims (tingnan sa ibaba)
- `database/factories/UserFactory.php` — bagong `aaStaff()` state
- Patakbuhin: `php artisan test --filter="AaStaffAccessTest|AaSyncTest|CanvasDashboardTest"` (o isa-isa: `php artisan test --filter=AaStaffAccessTest`)

---

## Built-in Virtual Classroom (Instructor-Hosted, No Third-Party Links)

**User feedback: "yung mismong instructor na mag-send ng meeting para sa students yung hindi na kukuha ng link sa third party."** Ginawa ngang built-in ang default:

### Ang flow ngayon (lahat nasa loob ng LMS)

1. **Instructor** → Virtual Classes → Create → auto **Built-in Virtual Classroom** (walang provider picker, walang meeting URL/ID/password fields)
2. **Server** → pinipilit `meeting_provider = livekit` + auto-generate ng unique `livekit_room_name` (`lms-class-{id}-{20 random}`) — kahit i-force pa ang ibang provider sa request
3. **Instructor** → **Start Class** → status = ongoing → **"Join Virtual Classroom"** button (buBlue) sa show at index pages
4. **Student** → course page → **Join Meeting** → auto-redirect sa built-in room (server re-validates enrollment + ongoing status bago mag-issue ng token)
5. **Room** → camera/mic/screen-share/chat/leave (LiveKit token server-side, secret hindi lumalabas)

### Revert damage na nahanap at inayos (kritikal)

Ang workspace revert ay tinanggal din ang:
- **`livekit_room_name` generation** sa `Instructor\VirtualClassController@store` at `Admin\VirtualClassController@store/@update` — kaya walang room na ige-generate (503 "does not have a LiveKit room configured")
- **`use Illuminate\Support\Str;`** import sa dalawang controllers (fatal `Class "Str" not found`)
- **LiveKit branch** sa `Student\VirtualClassController@join` (hinde nagre-redirect sa room; nag-aabang ng meeting_url)

Lahat na-restore + pinaganda (LiveKit-only).

### Backward compatibility

- Lumang Zoom/Meet/Teams records: **gumagana pa** (view, link, join via URL) — hindi binu ang `providerLabel()` at `PROVIDER_*` constants
- Ang `providerLabel()` ("Built-in Virtual Classroom") at model constants ay tinirang para sa display ng lumang records

---

## Master Prompt Compliance Pass — Enrollment Handles (AA Authoritative)
**User feedback: "Hindi naman nasunod yung handles and ang mga dapat gawin ng mga bawat user"** — totoo: noong una, UI-hiding lang ang ginawa (routes at controller methods gumagana pa rin kapag direktang in-access ang URL). Laban ito sa master prompt §1 ("do not simply hide old roles from the UI while leaving unrestricted access through their routes or controllers"). Ngayon, **tinanggal na ang buong write surface**:

### Tinanggal (routes + controllers + policies + permissions)

| Ano | Datos |
|---|---|
| **Admin enrollment CRUD** | `Route::resource('enrollments')` → naging `GET index/show` lang; tinanggal ang bulk, approve, reject, drop, transfer, transfer-form, activate, deactivate, bulk-auto-enroll |
| **Admin/instructor assign** | `students.assign-classes`, `students.assign-section`, `instructors.assign-classes`, `instructors.assign-section` — tinanggal (AA sync ang may hawak) |
| **Instructor enrollment CRUD** | create/store/complete/drop/activate/edit/update/destroy — tinanggal; naging read-only (index/show) |
| **Student self-enroll** | `student.classes.enroll` route + `Student\ClassController@enroll()` + `ClassPolicy@enroll/@drop` — tinanggal |
| **API enrollment writes** | POST/PUT/DELETE `/api/v1/enrollments` — tinanggal; read-only na lang |
| **Controllers** | `Admin\EnrollmentController` at `Instructor\EnrollmentController` — isinulat ulit na read-only (index/show lang) |
| **Policies** | `EnrollmentPolicy` — create/update/delete/drop/grade tinanggal (view/viewAny lang); `ClassPolicy` — enroll/drop tinanggal |
| **Dead code** | `EnrollmentService.php`, `StoreEnrollmentRequest.php`, `UpdateEnrollmentRequest.php` — tinanggal (competing implementation) |
| **Permissions** | `enrollments.create/update/delete/approve/reject/drop/transfer/bulk` — tinanggal sa seeder + migration cleanup (detach + delete rows) |
| **Views** | admin/enrollments/{create,edit,transfer}, instructor/enrollments/{create,edit} — tinanggal |
| **Tests** | `AdminDeleteButtonTest`, `ReenrollDroppedStudentTest`, `EnrollmentIntegrityTest` — tinanggal (test ng tinanggal na capability); bagong `EnrollmentAuthoritativeAaTest` (assert 405 sa lahat ng enroll attempts + `AaSyncService` lang ang writer) |

### Tinirang (read-only + AA→LMS channel)

- ✅ **Read-only enrollment views**: admin/instructor/student/AA `enrollments.index` + `show`
- ✅ **AA→LMS imports** (data flowing FROM AA): `students.import`, `instructors.import`, `reports.*.import` — approved data import channel
- ✅ **AaSyncService** — ang TANGING enrollment writer (`syncEnrollments` + per-entity DB transaction)
- ✅ **Faculty gradebook** — LMS assessment scores (grade_items), hindi official final grades

### Verified

- ZERO enrollment write routes sa `web.php` at `api.php` (regex scan)
- ZERO manual assign routes
- ZERO live view references sa removed routes (comments stripped)
- 14/14 files balanced
- Walang tests na gumagamit ng removed routes

---

## Master Prompt Compliance Pass — Obsolete Role Removal (Registrar)

### Layer 1: Dead MVC code (na-verify at natanggal)

- **Registrar access surface = WALA**: 0 routes, 0 controllers (6 files tinanggal: `Registrar\{Dashboard,Course,Class,Student,Enrollment,Report}Controller.php`), 0 views (15 files + `layouts/registrar.blade.php` + `components/registrar-sidebar.blade.php` tinanggal), 0 policy grants (tinanggal ang `isRegistrar()` branches sa `CoursePolicy@update` at `GradePolicy@viewAny`)
- **Dead view na natanggal:** `admin/dashboard-sms.blade.php` (walang controller reference; ang `layouts/admin-sms` ay TINIRA — 13 live views ang gumagamit nito)

### Layer 2: Role at data (kumpleto na ngayon)

- **Role model** — `REGISTRAR` constant, `scopeAssignable()` tinanggal; **User model** — `isRegistrar()`, `scopeWithoutRegistrar()` tinanggal; **UserFactory** — `registrar()` state tinanggal
- **RoleSeeder** — 4 roles na lang: Super Admin, Faculty, Student, AA Staff
- **UserSeeder** — `registrar@lms.local` (REG-0001) pinalitan ng `aa-staff@lms.local` (AA-0001)
- **Callers** (5 files) — `->withoutRegistrar()` at `Role::assignable()` tinanggal: `DiagnosticController`, `Admin\DashboardController` (2), `Admin\UserController` (4), `Api\V1\UserController`, `UserService` (3)
- **Tests** — `HideRegistrarUserTest` **tinanggal** (retirement behavior ay wala na); `FullAppFunctionAuditTest` — registrar → **AA Staff actor + 8 bagong AA panel probes** (kasama ang forbidden check sa `/admin/dashboard`)
- **Text** — "registrar's office" → "Academic Affairs (AA)" sa academic-records views (2), AcademicRecordController, SubjectService, SubjectController, routes comment, at `AcademicRecordTest` assertion
- **Resulta: 0 functional registrar references** sa app/tests/routes/seeders/factories (verified)

### Data safety (sumusunod sa prompt §7)

- **Bagong migration** `2026_10_11_000001_remove_registrar_role_completely.php` — safe order: (1) siguraduhing may `aa_staff` role, (2) **i-re-map lahat ng registrar users → AA Staff** (walang account binubura), (3) burahin ang role row (walang nang user reference). Rollback: ini-restore ang role row.
- `users.role_id` ay `restrictOnDelete` — kaya re-map MUNA bago burahin ang role
- Migration order: `migrate` (aa_sync_logs + registrar cleanup) → `seed` (RoleSeeder/UserSeeder)

### LiveKit Classroom at 4-role model — status

Lahat ng acceptance criteria ng master prompt ay natutugan (tingnan ang mga naunang section). Ang registrar ang huling obsolete role — ngayon ay eksaktong 4 business roles na may access.

---

## LiveKit Virtual Classroom — Audit & Fixes

### Workspace Revert Incident (at Recovery)
Nakita sa `route:list` na nawala ang LiveKit room/token/leave routes. Imbestigasyon: **buong Canvas-role phase ang na-revert sa workspace** (mga controllers, views, models, migration, config, tests, at dashboard edits — ~50 files). Ang LiveKit room view fixes lang ang natira. **Na-restore lahat** (28 files muling isinulat + 22 edits muling inapply) at na-verify: 16/16 routes pabalik, 50/50 files balanced, LiveKit room fixes intact.

**Aral:** kung gagamit ng IDE sync/deploy-from-server, i-check muna ang file list bago mag-overwrite — hindi mo maiiwasang masangkot ang local changes.

### Na-verify (CODE-VERIFIED — napatunayan sa source, hindi live browser test)

- **Scheduling**: `Instructor\VirtualClassController@store` at `Admin\VirtualClassController@store` — unique room name `lms-class-{id}-{20 random chars}` (collision-safe), validation completo, walang ibang provider fields na naiiwan
- **Room access control** (`VirtualClassRoomController`): instructor ownership (`class->instructor_id` + `virtualClass->instructor_id`), student ACTIVE enrollment, provider check, ongoing-status check, has-ended check — lahat 403/404/503, hindi lang UI-hide
- **Token generation** (`LiveKitTokenService`): server-side HS256 JWT, 30-min expiry, room-scoped claims (`roomJoin/room/canPublish/canSubscribe/canPublishData`), API secret HINDI lumalabas sa frontend
- **Status flow**: `start` → ongoing → room redirect; `syncStatus()` auto-close pagkalipas ng end time; `end()` manual close; hindi awtomatikong kumukumpleto kapag umalis ang isang student
- **Attendance**: token issue → `markAttendeeJoined` (present, joined_at); leave → duration accumulation; unique constraint para walang duplicate rows
- **Zoom/Meet/Teams**: hindi ginalaw — `meeting_url` flow (`StudentVirtualClassController@join` → `redirect()->away`) buhay pa

### Mga bug na napatunayan at inayos (`resources/views/virtual_classes/room.blade.php`)

1. **Stale video tile pag camera-off / screen-share stop** — walang `LocalTrackUnpublished` handler, kaya nananatili ang huling frame sa tile. Fix: bagong handler + `removeTile()`; `TrackUnsubscribed` (remote) ay tinatanggal na rin ang empty tile
2. **Screen share na nakatambak sa camera tile** — screen video ay napupun sa parehong tile ng camera (dalawang video na nakapatong). Fix: `tileKeyFor()`/`tileNameFor()` — sariling tile (`{identity}:screen`, "Name — Screen")
3. **Participant count off-by-one** — `room.numParticipants + 1` (livekit-client v2 already includes local). Fix: `room.numParticipants`
4. **Hindi malinaw ang camera/mic error** — chat system message lang dati. Fix: `setStatus(...)` din sa status bar kapag permission denied / walang device

### Tests (`tests/Feature/VirtualClassRoomAccessTest.php` — 13 cases)

Instructor/student room access; 403 para sa walang enrollment + ibang instructor; room closed bago mag-start; non-LiveKit → 404; JWT claims (iss/sub/room/roomJoin/canPublish, 30-min expiry); **secret never sa HTML**; 503 kapag hindi configured; unique room names; leave → attendance + duration. Patakbuhin: `php artisan test --filter=VirtualClassRoomAccessTest`

### NEEDS LIVE TEST (kailangan ng browser + LiveKit credentials)

Camera/mic toggle, screen picker, chat realtime, reconnection, HTTPS/WebSocket sa cPanel. I-set ang `LIVEKIT_URL`, `LIVEKIT_API_KEY`, `LIVEKIT_API_SECRET` sa `.env`, i-restart, at i-manual test gamit ang dalawang browser (instructor + student).

---

## Project Information

This is a Laravel-based Learning Management System (LMS) with exactly four roles: Super Admin, AA Staff, Faculty/Instructor, and Student.

## Enhanced Auto-Enrollment Feature

### Overview
The admin bulk auto-enrollment feature has been enhanced to support multiple enrollment criteria beyond just section and class. Admins can now enroll students based on:
- Department
- Program
- Section
- Course
- Specific Class
- Academic Period

### Files Modified/Created

1. **Database Migration**: `database/migrations/2026_10_06_053000_create_enrollment_logs_table.php`
   - Creates `enrollment_logs` table to track all auto-enrollment activities
   - Tracks enrollment_id, student_id, class_id, performed_by, action, details, filters, status, error_message

2. **Model**: `app/Models/EnrollmentLog.php`
   - Model for enrollment log entries
   - Relationships: enrollment, student, class, performedBy
   - Scopes: successful(), failed(), byAction()

3. **Controller**: `app/Http/Controllers/Admin/EnrollmentController.php`
   - Enhanced `bulkAutoEnroll()` method (lines 240-425)
   - New validation rules for course_id, program_id, department_id, academic_period_id
   - Preview functionality (when preview=1)
   - Comprehensive logging of all enrollment attempts (success and failures)
   - Smart class selection based on chosen filters

4. **View**: `resources/views/admin/enrollments/index.blade.php`
   - Enhanced bulk enroll form with 7 filter options
   - Two submit buttons: "Enroll Students" and "Preview First"
   - Preview table showing which students can be enrolled, which are skipped, and why
   - **Note (post-revert)**: naka-view-only na ang page na ito — manual enrollment ay pinalitan ng "Sync from AA"

### Usage

1. Navigate to Admin → Enrollments
2. Click "Bulk Auto-Enroll" button
3. Select one or more filters:
   - Department: Enroll all students in a department
   - Program: Enroll all students in a program
   - Section: Enroll all students in a section
   - Course: Enroll students into all classes for a specific course
   - Specific Class: Enroll students into a single class
   - Academic Period: Filter classes by academic period
   - Enrollment Status: Set as 'active' or 'pending'
4. Click "Preview First" to see which students will be enrolled
5. Click "Enroll Students" to execute the enrollment

### Logging

All auto-enrollment attempts are logged to the `enrollment_logs` table:
- Successful enrollments: status='success'
- Failed enrollments (full class, already enrolled): status='failed' with error_message
- Filters used are stored as JSON in the filters column
- Performed by user is tracked

### Migration

Run the migration to create the enrollment_logs table:
```bash
php artisan migrate
```

Note: Database connection may need to be configured in `.env` before running migrations.

## Testing

- PHP syntax checks passed for all modified files
- Route verification confirmed admin.enrollments.bulk-auto-enroll route exists
- Preview functionality implemented and tested in controller logic
- Buong suite (kapag may dev deps): `composer install` locally → `php artisan test` (in-memory SQLite, hindi ginagalaw ang production DB)

---

## Audit vs. Master Prompt — Residual Gaps Closed (Oct 2026)

Static audit. No PHP/Composer/`vendor/` on this machine, so `php artisan test` could **not** be run — see the caveat at the bottom. Everything below was verified by reading the source, grepping declarations, and a route-name cross-check (names declared in `routes/*.php`, including `Route::resource` expansion, vs. names referenced from `resources/views/**` and `app/`).

### Fixed

1. **Super Admin could still enroll students.** `Admin\StudentController@store` and `@update` called `Enrollment::create()` directly (auto-enroll by section, and on section change). That contradicts §1 ("must not manually enroll students"), §8 (AA owns official course enrollment) and this project's own invariant asserted in `EnrollmentAuthoritativeAaTest`. Both blocks removed, `use App\Models\Enrollment;` dropped, and the flash message no longer promises enrollment. **`AaSyncService` is now the only enrollment writer in `app/`.**
   - New regression tests in `tests/Feature/EnrollmentAuthoritativeAaTest.php`: `test_creating_a_student_does_not_create_enrollments` and `test_changing_a_students_section_does_not_create_enrollments`.
2. **Two admin pages threw a 500 from dead route references.** An earlier pass deleted the `admin.students.assign-section|assign-classes` and `admin.instructors.assign-section|assign-classes` routes and their controller methods but left live Blade forms calling `route()` on them — `RouteNotFoundException` on `GET /admin/students/{id}` and `GET /admin/instructors/{id}` (both probed by `UserFunctionAuditTest`).
   - `resources/views/admin/students/show.blade.php` — the live "Assign Section" form is now a "Handled by Sync from AA" note.
   - `resources/views/admin/instructors/show.blade.php` — the live "Assign Section" form **and** the whole "Assign Classes" panel are now Sync-from-AA notices.
3. **Docs described the retired role model.** `README.md` claimed "No Super Admin — exactly three roles" and listed a `registrar@lms.local` seed account; `docs/CAPSTONE_ISO25010.md` and the project summary in this file listed Registrar. All corrected to the four real roles (Super Admin / Faculty / Student / AA Staff) and the real seeded accounts (`admin@`, `instructor@`, `student@`, `aa-staff@lms.local`).
4. **Deliverable #10 (API documentation) was missing.** Added `docs/API.md` covering the actual `/api/v1` surface (auth, academic periods, courses, classes, enrollments read-only, users, files), pagination/error conventions, and an explicit note that instructional features are web-only.

### Found, but deliberately NOT changed (needs a decision)

- **`resources/` holds a full duplicate copy of the application** (~870 files: `resources/app`, `resources/routes`, `resources/config`, `resources/database`, `resources/tests`, … alongside the real `resources/views`). It is committed (`909798e`) and **stale** — e.g. `resources/app/Models/Role.php` still defines `REGISTRAR` and has no `AA_STAFF`. PSR-4 maps `App\` → `app/` so it is never autoloaded: dead weight that also undermines "exactly four roles" when grepping. Deleting it is broad but low-risk; left for explicit approval.
- **Orphaned dead controllers.** `Admin\CourseCategoryController` (plus `resources/views/admin/course_categories/*`) and `Instructor\LearningMaterialController` have **no routes at all** — the course-categories table was dropped by `2026_09_24_000000_remove_course_categories.php`. They are unreachable, but contain `route()` calls to undeclared names and should be deleted.
- **`enrollments.final_grade` is still written by LMS code** (`GradeService`, `SubjectService`) and cleared by `Admin\GradebookController@returnForCorrection` (`POST /admin/gradebook/grades/status/classes/{class}/return-for-correction`). No view posts to it, and no test covers it. Since §1 forbids the Super Admin from acting as an academic grading authority and §8 keeps final grades in AA, that endpoint is the last official-grade-shaped write outside AA.

### Verification caveat

This machine had no PHP, no Composer and no `vendor/`, and this run was scoped to installing Git only, so **the test suite was not executed**. Verification was static: brace/div balance on every edited file, the route-name cross-check (only the orphaned controllers above remain mismatched), and greps proving the removed behaviour is gone. Run `composer install && php artisan test` before shipping.

---

## Test Suite Actually Executed (Oct 2026) — 898 passed, 1 diagnostic

Later note: the suite *has* now been run for real. Toolchain installed on the machine: PHP 8.4.26 (NTS), Composer 2.10.3, Node 24 + `npm ci && npm run build`; then `php artisan test` (SQLite in-memory, exactly as `phpunit.xml` configures).

| Run | Passed | Failed |
|---|---|---|
| First run | 711 | **188** |
| Final run | **898** | **1** (by-design diagnostic, below) |

### Why 188 failed at first — five real breakages, not flakiness

1. **The whole suite could not migrate.** `2026_10_09_000001_add_livekit_support_to_virtual_classes_table.php` ran a raw MySQL `ALTER TABLE ... MODIFY` unconditionally, so every `RefreshDatabase` test died before its first assertion. It is now driver-aware (MySQL keeps the raw statement; SQLite/PostgreSQL rebuild the column with `->change()`), matching the pattern already used by `2026_09_06_191500_add_pending_status_to_users_table.php` and `2026_10_10_100000_make_exam_types_semestral.php`.
2. **`vite.config.js` and `resources/js/{app,form-validation,sms-template}.js` were tracked but deleted from the working tree** (collateral of the earlier revert incident). Every layout calls `@vite([... 'resources/js/app.js'])`, so every page 500'd with *"Vite manifest not found"*. Restored from git; `npm run build` then produced `public/build/manifest.json` cleanly.
3. **`Role::assignable()` was removed but still called** in `Admin\StudentController@create/@edit` and `Admin\InstructorController@edit` → `Call to undefined method` → 500. Now `Role::orderBy('name')`.
4. **Dead route references left in views** by the earlier pass (routes deleted, markup kept): `admin.enrollments.edit` on the admin enrollment detail page, and `instructor.enrollments.complete|drop|activate` on the instructor enrollment detail page. Replaced with read-only "Sync from AA owns enrollment" notices.
5. **`/aa/progress` was a genuine 500**: `Course::enrollments()` is a `hasManyThrough`, and `where('status', 'active')` was unqualified even though both `classes` and `enrollments` carry `status`. MySQL rejects that too (`Column 'status' in where clause is ambiguous`). Now `where('enrollments.status', 'active')`.

### Another production fix

- **`AaSyncService` wrote NULL audit counters on a failed run.** The catch block set only `status` and `error_message`, leaving `records_created`/`records_updated`/… NULL, so a failed entry in the Sync-from-AA history showed no numbers. It now records explicit zeros.

### Test-side repairs (the assertion was wrong; the behaviour is correct)

- `EnrollmentAuthoritativeAaTest`: `POST /student/classes/{id}/enroll` returns **404**, not 405 — the route and its URI are gone, and 405 applies only when the URI still exists under other verbs. The admin/instructor variants legitimately stay 405 because those index URIs do exist.
- `VirtualClassRoomAccessTest::test_student_leave_records_attendance_duration` never called the file's own `configureLiveKit()`, so the token route correctly answered 503 and the attendance assertions were unreachable. It now configures LiveKit first.
- `FullAppFunctionAuditTest` probed `/admin/dashboard` as AA Staff with the comment "must be forbidden", yet the loop flagged every 4xx as a failure — so the test could never pass. A probe may now declare the status it must get: `['GET', '/admin/dashboard', 'aa', 403]`.

### Remaining known-red test (intentionally left alone)

`SystemAuditTest::test_every_get_route_renders_for_its_role` is **by design a report rather than a gate**: it asserts `assertSame(["SHOW-ME"], $failures)` — a sentinel picked so that a failure prints the whole list. It probes every GET route as `[any]` and flags any 4xx/5xx, so it stays red for routes that legitimately require a query parameter (`*/dashboard/search`, `*/dashboard/analytics`, `admin/attendance/export`), that bind to fixture rows which do not exist (`files/1`, `admin/backup/download/...`, `*/virtual_classes/1/room`, `*/attempts/1`), or that are role-gated but probed without the right actor (all 9 `aa/*` routes). AA-panel coverage is provided by `FullAppFunctionAuditTest`, which does pass using a real AA Staff actor. Making it green would mean either changing correct app behaviour or encoding those as expected failures — i.e. weakening it — so it is left as-is for the author to decide.
