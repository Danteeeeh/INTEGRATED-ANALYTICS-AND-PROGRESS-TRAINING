<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h2 style="color: #2c3e50; margin-top: 0;">{{ config('app.name') }}</h2>
        </div>

        <div style="background: #ffffff; padding: 30px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h1 style="color: #2c3e50; margin-top: 0;">{{ $title }}</h1>

            <p style="font-size: 16px; color: #555;">{{ $message }}</p>

            @if($link)
                <div style="margin: 20px 0;">
                    <a href="{{ $link }}" style="display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;">
                        View Details
                    </a>
                </div>
            @endif

            <p style="font-size: 14px; color: #777; margin-top: 30px;">
                If you have any questions, please contact your instructor or administrator.
            </p>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 20px; text-align: center; font-size: 12px; color: #777;">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>