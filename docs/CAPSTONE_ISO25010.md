# CAPSTONE ISO/IEC 25010 Mapping

**Project:** Integrated Analytics and Progress-Training LMS (AI-Enhanced)
**Chapter:** 4 — Software Quality Evaluation

This table maps the LMS features (existing + new AI features) to the ISO/IEC 25010:2011 quality characteristics and sub-characteristics. Coverage: ✓ = supported; (✓) = partially supported / via configuration.

## 1. Functional Suitability (Functional completeness, correctness, appropriateness)

| Feature | Completeness | Correctness | Appropriateness |
|---|---|---|---|
| Role-based access (Admin / Instructor / Student / Registrar) | ✓ | ✓ | ✓ |
| Course, module, lesson management | ✓ | ✓ | ✓ |
| Enrollment lifecycle (pending → active → completed/dropped) | ✓ | ✓ | ✓ |
| Assignment & quiz grading with rubrics | ✓ | ✓ | ✓ |
| Gradebook (per-item grades, bulk entry, release, export CSV) | ✓ | ✓ | ✓ |
| Attendance & virtual classes | ✓ | ✓ | ✓ |
| Announcements & discussions | ✓ | ✓ | ✓ |
| **AI Personalized Learning Plans (student self-generate, instructor suggest)** | ✓ | ✓ | ✓ |
| **AI Study Assistant (deadlines / progress / priorities / recovery)** | ✓ | ✓ | ✓ |
| **AI-assisted feedback suggestions (gradebook)** | ✓ | ✓ | ✓ |
| **Login OTP / 2-step verification for non-local accounts** | ✓ | ✓ | ✓ |

## 2. Performance Efficiency (Time behaviour, resource utilisation, capacity)

| Aspect | Evidence |
|---|---|
| Dashboard caching (15-min cache per instructor) | ✓ — cached queries reduce DB load |
| Optimized at-risk / analytics queries | ✓ — eager loading, scoped queries |
| Throttling on login & assistant endpoints | ✓ — `throttle:5,1`, `throttle:20,1` |
| Pagination on gradebook & roster lists | ✓ |

## 3. Compatibility (Co-existence, interoperability)

| Aspect | Evidence |
|---|---|
| Standard LAMP/XAMPP stack | ✓ — PHP 8.2, MySQL, Apache |
| Laravel 12 conventions (routes, policies, services) | ✓ |
| REST-ish form endpoints with CSRF protection | ✓ |
| CSV export interoperable with spreadsheets | ✓ |

## 4. Usability (Appropriateness recognizability, learnability, operability, user error protection, accessibility, user interface aesthetics)

| Aspect | Evidence |
|---|---|
| Role-specific sidebars & dashboards | ✓ |
| Clear page titles, empty states, success messages | ✓ |
| Inline validation + throttling errors | ✓ |
| Learning-plan progress bars & checklists | ✓ |
| Study Assistant natural-language prompts (Taglish-friendly) | ✓ |
| Instructor "Suggest plan" / "Suggest feedback (AI)" one-click actions | ✓ |
| Responsive admin/student layouts | ✓ |

## 5. Reliability (Maturity, availability, fault tolerance, recoverability)

| Aspect | Evidence |
|---|---|
| Soft-deletes on key models | ✓ |
| Audit log service | ✓ |
| Backup service (admin) | ✓ |
| Idempotent learning-plan generation (no duplicate active plans) | ✓ |
| OTP expiry + attempt limits | ✓ |
| Migration-based schema evolution | ✓ |

## 6. Security (Confidentiality, integrity, non-repudiation, accountability, authenticity)

| Aspect | Evidence |
|---|---|
| Bcrypt/hashed passwords, Sanctum auth | ✓ |
| Role middleware (`role:...`) + policy authorization | ✓ |
| CSRF protection on all forms | ✓ |
| Input validation on requests | ✓ |
| **OTP second factor for gmail/external accounts** | ✓ |
| Login throttling & verification attempt caps | ✓ |
| Activity / audit logging | ✓ |

## 7. Maintainability (Modularity, reusability, analysability, modifiability, testability)

| Aspect | Evidence |
|---|---|
| Service layer (19 services) separated from controllers | ✓ |
| Policies per domain (Class, Course, LearningPlan) | ✓ |
| Blade components & shared layouts | ✓ |
| Config (`config/lms.php`) for feature toggles | ✓ |
| **122 passing feature tests (377 assertions)** | ✓ |
| Naming-conventioned migrations & seeders | ✓ |

## 8. Portability (Adaptability, installability, replaceability)

| Aspect | Evidence |
|---|---|
| Runs under XAMPP (Windows) & standard Linux stacks | ✓ |
| Environment-based config (`.env`) | ✓ |
| Seeder-driven demo data (`full_setup.php`) | ✓ |
| Vite asset pipeline | ✓ |

---

## New AI Features — Detailed Quality Notes

### AI Personalized Learning Plans
- **Functional correctness:** generated from `StudentPerformanceAssessmentService` signals (quiz avg, overdue work, progress, streak), 1:1 mapped to plan items, idempotent per student.
- **Security:** owner-only via `LearningPlanPolicy`; instructor actions scoped to their own classes.
- **Usability:** student dashboard "Create my learning plan" + plan progress bar; instructor per-student "Suggest plan".

### AI Study Assistant
- **Functional correctness:** intent matching for deadlines / progress / priorities / recovery answered from real DB data (assignments, assessments, course progress).
- **Performance:** throttled `throttle:20,1`.
- **Usability:** natural language, Taglish-friendly prompts, chat UI with loading state.

### AI-Assisted Feedback Suggestions
- **Functional correctness:** drafts composed from score + rubric criteria/levels; preview only (never auto-saved).
- **Security:** instructor-grade authorization; route under instructor role group.
- **Reliability:** graceful failure message on error.

### Login OTP / 2-Step Verification
- **Security:** hashed one-time code, expiry, attempt cap, resend throttle; local default accounts bypass.
- **Testability:** covered by `LoginOtpTest` (8 scenarios).
