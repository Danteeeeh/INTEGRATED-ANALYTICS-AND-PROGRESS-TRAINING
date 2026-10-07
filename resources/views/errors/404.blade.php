{{-- Surface a message the application deliberately authored with
     abort(404, '...') so an operator can act on it. Deliberately limited to
     HttpException: a ModelNotFoundException message ("No query results for
     model [App\Models\X] 42") would leak internals, so that stays generic. --}}
@php
    $explained = $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException
        && filled($exception->getMessage())
        && $exception->getMessage() !== 'Not Found';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Not Found</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: grid; place-items: center; margin: 0; }
        .box { text-align: center; padding: 2rem; }
        h1 { font-size: 3rem; margin: 0 0 .5rem; color: #93c5fd; }
        p { max-width: 34rem; margin: 0 auto .75rem; line-height: 1.5; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <div class="box">
        <h1>404</h1>
        @if ($explained)
            <p>{{ $exception->getMessage() }}</p>
        @else
            <p>The page you requested could not be found.</p>
        @endif
        <p><a href="{{ url('/') }}">Return home</a></p>
    </div>
</body>
</html>
