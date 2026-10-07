<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login verification code</title>
</head>
<body style="margin:0;background:#edf3fa;font-family:Arial,sans-serif;color:#13213b;line-height:1.6;">
    <div style="max-width:560px;margin:0 auto;padding:32px 18px;">
        <div style="background:#173aa8;color:#fff;padding:24px;border-radius:12px 12px 0 0;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#bfe9ff;">{{ config('app.name') }}</div>
            <h1 style="margin:8px 0 0;font-size:24px;">Login verification</h1>
        </div>
        <div style="background:#fff;padding:28px 24px;border:1px solid #dce4f0;border-top:0;border-radius:0 0 12px 12px;">
            <p>Hello {{ $user->full_name }},</p>
            <p>Use this one-time code to finish signing in:</p>
            <div style="margin:24px 0;text-align:center;font-size:34px;font-weight:800;letter-spacing:.35em;color:#173aa8;">{{ $code }}</div>
            <p>This code expires in {{ $expiresInMinutes }} minutes and can only be used once.</p>
            <p style="font-size:13px;color:#71809b;">If you did not try to sign in, change your password and contact your school administrator.</p>
        </div>
        <p style="margin:16px 0 0;text-align:center;font-size:12px;color:#71809b;">This is an automated security message. Please do not reply.</p>
    </div>
</body>
</html>
