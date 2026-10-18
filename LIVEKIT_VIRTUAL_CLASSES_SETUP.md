# LiveKit Virtual Classes integration

This patch adds a LiveKit-powered in-LMS classroom to the existing Laravel 12 project. Existing Zoom/Google Meet/Teams links remain supported.

## 1. LiveKit credentials

Create a LiveKit Cloud project at https://cloud.livekit.io/ and add these values to the **server-side** `.env` file (never expose the API secret in Blade or JavaScript):

```dotenv
LIVEKIT_URL=wss://your-project.livekit.cloud
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_api_secret
```

## 2. Apply the patch

Merge the files in this patch into the project root, keeping existing files. Review the diff before replacing files. Back up the production database and code first.

## 3. Run the migration

On local/staging first, with the correct MySQL database configured:

```bash
php artisan migrate
php artisan config:clear
php artisan config:cache
```

The migration adds a nullable `livekit_room_name` and changes `meeting_provider` from a MySQL ENUM to VARCHAR so `livekit` can be stored. This project migration is written for MySQL/MariaDB. Do not run it against production until a database backup and staging test are complete.

## 4. Test workflow

1. Sign in as an instructor assigned to a class.
2. Open that class > Virtual Classes > Create Virtual Class.
3. Choose **Built-in Virtual Classroom (LiveKit)** and save it as Scheduled.
4. Open the new class and click **Start Class**. The instructor is redirected into the room.
5. Sign in in a separate browser as a student with an **active** enrollment in that class. Open the virtual class and click **Join Meeting**.
6. Test camera, microphone, screen share, chat, leave/rejoin, and attendance. Test on the real hosting domain using HTTPS.

## Hosting notes

The Laravel app can stay on shared cPanel hosting. LiveKit's real-time media server runs separately (LiveKit Cloud is recommended). Browser access needs HTTPS and network access to LiveKit's WebSocket/WebRTC endpoints.

The LiveKit browser SDK is **bundled locally** — the room loads `resources/js/virtual-classroom.js`, which imports the pinned `livekit-client` package, and `resources/css/virtual-classroom.css`; both are compiled by Vite (`npm install && npm run build`). No jsDelivr request is made, so a strict Content-Security-Policy or a blocked CDN cannot break the classroom. **Deployment must run the Vite build** — without `public/build/manifest.json` entries for those two files the room page fails before it renders.

The room page keeps its configuration in `data-*` attributes on `#virtualClassroom` (token/leave/end/mute-all URLs, display name, instructor flag), so the view contains no inline `<script>` and no inline `<style>` block.

## Scope and known limitations of this first integration

- The Laravel backend issues short-lived room-scoped participant tokens; the API secret is never sent to the browser.
- Student token requests require an active enrollment; instructor requests require the instructor to own the class and scheduled session.
- Student attendance is recorded when a room token is issued and duration is updated on a clean leave. Abrupt browser/network closure may not reliably send the leave request, so attendance duration should be treated as best-effort until webhook reconciliation is added.
- Ending a class (dashboard **End**, in-room **End Meeting**, or the `virtual-classes:auto-close` cron) calls LiveKit `RoomService.DeleteRoom` with a room-scoped `roomAdmin` token, so everyone still inside is disconnected immediately. The same call closes any attendance row that is still open, since a forced disconnect never fires the browser's leave beacon. If LiveKit is unreachable the class still closes (status alone blocks new tokens); the room is then only dropped on the next successful attempt.
- The instructor dashboard's live row shows "N in room" where N is LiveKit's exact participant count (a `~` prefix marks the attendance fallback). Hovering, focusing or tapping the chip opens a roster of participant names with role (Instructor / Student / Observer) and time in the room. One `ListParticipants` call per poll answers both the count and the roster; names and roles are resolved from the LMS (`App\Services\VirtualClassPresence`), never from the join token, and the roster is attached outside the dashboard panels because those clip their own overflow.
- `RoomService` is polled, not pushed: the dashboard refreshes the count and the roster every 20 seconds while the tab is visible. A LiveKit webhook (`participant_joined` / `participant_left`) would be the upgrade path for instant updates and for reconciling attendance without relying on the browser's leave beacon.
- Each live session also gets a **live room status** widget on the instructor dashboard: a headcount sparkline and the newest joins and leaves. It leads the sidebar whenever a session is running. The trend and the feed are derived from the session's attendance rows (`App\Services\VirtualClassRoomTimeline`) — 24 points over the last hour at most, newest eight events — so they need no extra storage and cover the whole session the moment the page loads. The headline number stays LiveKit's live headcount, which is why the widget carries the line "Headcount is live from the room; the trend and this feed come from LMS attendance records": the two can legitimately differ (the instructor is connected but is not an attendee row).
- The sparkline is drawn with the same Chart.js build the dashboard's analytics panels use, loaded from jsDelivr. If that CDN is blocked the widget still renders its headcount, trend text ("Peak 4 · now 2") and feed; only the canvas stays empty, and the browser check asserts the canvas actually has pixels drawn.
- Still not implemented: recording, breakout rooms, and persisted chat history.
- The room markup still uses inline `style="..."` attributes for layout, so a policy that blocks `style-src-attr` would need those moved into the bundlable stylesheet as well.
- The `virtual_classes.join` permission and existing role permissions must be reviewed against the production role/permission seed data. The new room endpoints additionally enforce role, class ownership, and active enrollment directly.
