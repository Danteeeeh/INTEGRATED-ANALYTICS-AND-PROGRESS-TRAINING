/**
 * The live headcount on the Faculty dashboard must reveal who is in the room.
 *
 * The chip shows a number; hovering (or focusing, or tapping, on a phone) opens
 * a roster of participant names with their role. Those names come from the LMS,
 * never from the raw join token, and the roster has to survive the dashboard's
 * own layout — the panels clip their overflow, which used to cut the roster
 * away entirely.
 *
 * The spec seeds its own live class + attendance rows, so it does not depend on
 * whatever demo data the seeded database happens to carry. LiveKit is left
 * unconfigured by the check's environment, which exercises the attendance
 * fallback; the names and roles asserted here come from that same path.
 *
 * Run with `npm run test:responsive` (or `npx playwright test --project=desktop
 * live-headcount-roster.spec.mjs`).
 */

import { spawnSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { authStateFile } from './support/coverage.mjs';
import { phpBinary, projectRoot, serverEnvironment } from './support/env.mjs';

/** Name with markup in it: it must render as text, not as an element. */
const MARKUP_NAME = '<b>Bold</b> Hacker';
const NAMES = ['Roster One', 'Roster Two', MARKUP_NAME];

const SEED = `
use App\\Models\\ClassModel;
use App\\Models\\Course;
use App\\Models\\User;
use App\\Models\\VirtualClass;
use App\\Models\\VirtualClassAttendee;
use Illuminate\\Support\\Str;

$instructor = User::where('email', 'instructor@lms.local')->firstOrFail();
$class = ClassModel::where('instructor_id', $instructor->id)->first()
    ?? ClassModel::create([
        'course_id' => Course::firstOrFail()->id,
        'instructor_id' => $instructor->id,
        'code' => 'ROSTER-' . Str::upper(Str::random(4)),
        'status' => 'active',
    ]);

$emails = ['roster1@lms.local', 'roster2@lms.local', 'roster3@lms.local'];
// Users and virtual classes are soft-deletable and email is unique, so a
// plain delete would leave the address taken and the next run's user create
// would fail with a unique violation.
User::withTrashed()->whereIn('email', $emails)->forceDelete();
VirtualClass::withTrashed()->where('class_id', $class->id)->where('title', 'Roster check live')->forceDelete();

$names = ${JSON.stringify(NAMES)};
foreach ($names as $i => $fullName) {
    $student = User::factory()->student()->create([
        'first_name' => $fullName,
        'last_name' => '',
        'email' => $emails[$i],
        'status' => 'active',
    ]);

    $students[] = $student;
}

$virtualClass = VirtualClass::create([
    'course_id' => $class->course_id,
    'class_id' => $class->id,
    'instructor_id' => $instructor->id,
    'title' => 'Roster check live',
    'meeting_date' => now()->toDateString(),
    'start_time' => now()->subMinutes(20)->format('H:i:s'),
    'end_time' => now()->addMinutes(40)->format('H:i:s'),
    'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
    'livekit_room_name' => 'lms-roster-check-' . Str::lower(Str::random(8)),
    'status' => VirtualClass::STATUS_ONGOING,
    'created_by' => $instructor->id,
]);

foreach ($students as $student) {
    VirtualClassAttendee::create([
        'virtual_class_id' => $virtualClass->id,
        'user_id' => $student->id,
        'joined_at' => now()->subMinutes(12),
        'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
    ]);
}

echo 'seeded ' . $virtualClass->id;
`;

test.use({ storageState: authStateFile('instructor') });

/**
 * Runs PHP inside the check's app (the responsive-check database), with LiveKit
 * blanked so nothing can reach a real project. Never let a test end or delete a
 * room against production: this spec only ever reads room state.
 */
function runTinker(php, confirmation) {
    const result = spawnSync(phpBinary(), ['artisan', 'tinker', '--execute=' + php], {
        cwd: projectRoot,
        env: serverEnvironment({
            LIVEKIT_URL: '',
            LIVEKIT_API_KEY: '',
            LIVEKIT_API_SECRET: '',
        }),
        encoding: 'utf8',
    });

    // A tinker error still exits 0 in some failure modes, so also insist on the
    // confirmation the script prints at the end.
    if (result.status !== 0 || !`${result.stdout}`.includes(confirmation)) {
        throw new Error(
            `Could not run the fixture (exit ${result.status}), expected "${confirmation}":\n` +
                `${result.stdout}${result.stderr}`,
        );
    }

    return `${result.stdout}`;
}

/** A student who joins after the page has already loaded. */
const SEED_LATE_JOIN = `
use App\\Models\\User;
use App\\Models\\VirtualClass;
use App\\Models\\VirtualClassAttendee;

$virtualClass = VirtualClass::where('title', 'Roster check live')->latest('id')->firstOrFail();

User::withTrashed()->where('email', 'roster4@lms.local')->forceDelete();

$student = User::factory()->student()->create([
    'first_name' => 'Late',
    'last_name' => 'Joiner',
    'email' => 'roster4@lms.local',
    'status' => 'active',
]);

VirtualClassAttendee::create([
    'virtual_class_id' => $virtualClass->id,
    'user_id' => $student->id,
    'joined_at' => now(),
    'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
]);

echo 'joined ' . $student->id;
`;

test.beforeAll(() => {
    runTinker(SEED, 'seeded ');
});

test('the live headcount reveals the participant roster', async ({ page }) => {
    const response = await page.goto('/instructor/dashboard', { waitUntil: 'domcontentloaded' });
    expect(response?.status(), 'GET /instructor/dashboard').toBe(200);

    const chip = page.locator('.dash-live-count').first();
    await expect(chip).toBeVisible();
    await expect(chip).toContainText(NAMES.length + ' in room');

    // The roster is attached to .dash-scope, not nested in the row: the
    // dashboard panels clip their own overflow.
    const roster = page.locator('.dash-live-roster').first();
    await expect(roster, 'the roster starts hidden').toBeHidden();
    expect(await roster.evaluate((element) => element.closest('.dash-list-item') === null)).toBe(true);

    await chip.hover();
    await expect(roster).toBeVisible();
    await expect(roster.locator('.dash-live-roster-row')).toHaveCount(NAMES.length);
    await expect(roster).toContainText('Roster One');
    await expect(roster).toContainText('Roster Two');

    // A name that looks like markup stays text.
    await expect(roster).toContainText(MARKUP_NAME);
    await expect(roster.locator('b'), 'the participant name must not render as markup').toHaveCount(0);

    // Roles are resolved on the server: every identity is "user-<id>", so an
    // identity-prefix guess labels the whole room as hosts.
    await expect(roster.locator('.dash-live-roster-role.student')).toHaveCount(NAMES.length);
    await expect(roster.locator('.dash-live-roster-role.instructor')).toHaveCount(0);

    // Minutes in the room, from the join time.
    await expect(roster.locator('.dash-live-roster-joined').first()).toHaveText(/^\d+m$/);

    // On screen, not clipped by an ancestor and not pushed off the viewport.
    const box = await roster.evaluate((element) => {
        const rect = element.getBoundingClientRect();
        const hit = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);

        return {
            reachesScreen: element.contains(hit),
            top: rect.top,
            left: rect.left,
            width: rect.width,
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
        };
    });

    expect(box.reachesScreen, `the roster is clipped by an ancestor: ${JSON.stringify(box)}`).toBe(true);
    expect(box.top, `roster above the viewport: ${JSON.stringify(box)}`).toBeGreaterThanOrEqual(0);
    expect(box.left, `roster left of the viewport: ${JSON.stringify(box)}`).toBeGreaterThanOrEqual(0);
    expect(box.left + box.width, `roster right of the viewport: ${JSON.stringify(box)}`).toBeLessThanOrEqual(
        box.viewportWidth,
    );
    expect(box.width).toBeGreaterThan(150);

    // Moving away hides it again.
    await page.mouse.move(4, 4);
    await expect(roster).toBeHidden();

    // A tap (no hover) toggles the roster instead of following the row link.
    await chip.evaluate((element) => element.click());
    await expect(roster).toBeVisible();
    expect(new URL(page.url()).pathname, 'the chip click must not navigate the row link').toBe(
        '/instructor/dashboard',
    );
});

test('the live room widget charts the headcount and lists who joined', async ({ page }) => {
    const response = await page.goto('/instructor/dashboard', { waitUntil: 'domcontentloaded' });
    expect(response?.status(), 'GET /instructor/dashboard').toBe(200);

    const room = page.locator('.dash-live-room').first();
    await expect(room).toBeVisible();
    await expect(room.locator('.dash-live-room-title')).toHaveText('Roster check live');
    // "~" because LiveKit is blanked here: the headcount is the attendance count.
    await expect(room.locator('[data-live-room-count]')).toHaveText(new RegExp(`~?${NAMES.length}`));

    // The sparkline is a real chart on the canvas, not an empty box.
    const canvas = room.locator('canvas[data-live-room-canvas]');
    await expect(canvas).toBeVisible();
    await expect
        .poll(async () =>
            canvas.evaluate((element) => {
                const pixels = element
                    .getContext('2d')
                    .getImageData(0, 0, element.width, element.height).data;

                let painted = 0;
                for (let index = 3; index < pixels.length; index += 4) {
                    if (pixels[index] !== 0) painted++;
                }

                return painted;
            }),
        )
        .toBeGreaterThan(200);

    await expect(room.locator('[data-live-room-trend]')).toHaveText(
        `Peak ${NAMES.length} · now ${NAMES.length}`,
    );

    // The feed: one join per seeded student, nobody has left, names stay text.
    await expect(room.locator('.dash-live-room-event.is-join')).toHaveCount(NAMES.length);
    await expect(room.locator('.dash-live-room-event.is-leave')).toHaveCount(0);
    await expect(room).toContainText('Roster One');
    await expect(room).toContainText(MARKUP_NAME);
    await expect(room.locator('.dash-live-room-event b')).toHaveCount(0);
});

test('the widget and the roster pick up a join without a reload', async ({ page }, testInfo) => {
    // This one waits out a real 20-second poll, which is width-independent.
    test.skip(testInfo.project.name !== 'desktop', 'the poll does not depend on the viewport');

    const response = await page.goto('/instructor/dashboard', { waitUntil: 'domcontentloaded' });
    expect(response?.status(), 'GET /instructor/dashboard').toBe(200);

    const room = page.locator('.dash-live-room').first();
    const chip = page.locator('.dash-live-count').first();

    await expect(room.locator('[data-live-room-count]')).toHaveText(new RegExp(`~?${NAMES.length}`));

    // Somebody joins once the page has already rendered: only a poll can know.
    runTinker(SEED_LATE_JOIN, 'joined ');

    await expect(room.locator('[data-live-room-count]')).toHaveText(/~?4/, { timeout: 45_000 });
    await expect(chip).toContainText('4 in room');
    await expect(room.locator('.dash-live-room-event.is-join')).toHaveCount(NAMES.length + 1);
    await expect(room.locator('.dash-live-room-event-name').first()).toHaveText('Late Joiner');
    await expect(room.locator('[data-live-room-trend]')).toHaveText('Peak 4 · now 4');
});
