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

The Laravel app can stay on shared cPanel hosting. LiveKit's real-time media server runs separately (LiveKit Cloud is recommended). Browser access needs HTTPS and network access to LiveKit's WebSocket/WebRTC endpoints. The CDN-loaded LiveKit JavaScript SDK requires the browser to reach jsDelivr. For stricter deployments, bundle a pinned `livekit-client` package locally via Vite instead.

## Scope and known limitations of this first integration

- The Laravel backend issues short-lived room-scoped participant tokens; the API secret is never sent to the browser.
- Student token requests require an active enrollment; instructor requests require the instructor to own the class and scheduled session.
- Student attendance is recorded when a room token is issued and duration is updated on a clean leave. Abrupt browser/network closure may not reliably send the leave request, so attendance duration should be treated as best-effort until webhook reconciliation is added.
- This patch does not yet implement server-side removal of every participant when the instructor ends a class, recording, breakout rooms, or persisted chat history.
- The `virtual_classes.join` permission and existing role permissions must be reviewed against the production role/permission seed data. The new room endpoints additionally enforce role, class ownership, and active enrollment directly.
