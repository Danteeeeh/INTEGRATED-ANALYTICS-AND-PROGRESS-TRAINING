<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Structural guard: Blade must actually compile a view.
 *
 * Blade finds `@php ... @endphp` by matching the first `@php` anywhere in the
 * file to the next `@endphp`. An inline `@php($x = ...)` earlier in the same
 * view therefore swallows everything up to that block's terminator, and the
 * page ships with raw `@php`, `{{ }}` and `<x-...>` straight into the response
 * body. It renders as a 500 on an undefined variable, and nothing in the view
 * source looks wrong — which is exactly how the admin virtual class page broke
 * and stayed broken.
 *
 * Compiling is purely syntactic, so this can cover every view in the app
 * without needing a database row behind each one.
 */
class BladeCompilationTest extends TestCase
{
    public function test_no_view_leaves_blade_syntax_behind(): void
    {
        foreach (self::viewPaths() as $path) {
            $this->assertViewCompiles($path);
        }
    }

    /**
     * @return array<int, string>
     */
    private static function viewPaths(): array
    {
        // Resolved from this file rather than resource_path() so the list can
        // be built without depending on the application's boot state.
        $root = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';

        if (! is_dir($root)) {
            return [];
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        $paths = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $paths[] = $file->getPathname();
        }

        sort($paths);

        return $paths;
    }

    private function assertViewCompiles(string $path): void
    {
        $source = file_get_contents($path);

        // A view may deliberately show Blade to the reader. Those regions are
        // the one legitimate place the syntax below is allowed to survive.
        $verbatimRegions = $this->verbatimRegions($source);
        $bareSource = $this->stripVerbatim($source, $verbatimRegions);

        $compiled = Blade::compileString($bareSource);

        // Only the HTML half is checked. Once Blade has done its job the
        // directives it handled live inside the PHP blocks it generated — and
        // so do the words "@foreach" or "@php" when a comment mentions them.
        // Those are prose, not uncompiled syntax.
        $html = $this->stripGeneratedPhp($compiled);

        $leftovers = [];

        foreach (['@php', '@endphp', '@section', '@endsection', '@if(', '@endif', '@foreach'] as $directive) {
            if (str_contains($html, $directive)) {
                $leftovers[] = $directive;
            }
        }

        // Uncompiled echoes: Blade turns every {{ }} outside @verbatim into an
        // echo call, so any survivor in the HTML means that region was never
        // processed and is being sent to the browser verbatim.
        if (preg_match('/\{\{.*?\}\}/s', $html)) {
            $leftovers[] = '{{ }}';
        }

        $this->assertSame(
            [],
            $leftovers,
            sprintf(
                "Blade left %s uncompiled in %s, so the page renders raw source.\n".
                "An inline @php(...) ahead of a @php/@endphp block makes the compiler\n".
                "swallow everything in between.",
                implode(', ', $leftovers),
                basename($path)
            )
        );
    }

    /** @return array<int, array{0: int, 1: int}> */
    private function verbatimRegions(string $source): array
    {
        if (! preg_match_all('/@verbatim(.*?)@endverbatim/s', $source, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $regions = [];

        foreach ($matches[0] as $index => $whole) {
            $regions[] = [$matches[1][$index][1], strlen($whole)];
        }

        return $regions;
    }

    /** @param array<int, array{0: int, 1: int}> $regions */
    private function stripVerbatim(string $source, array $regions): string
    {
        // Replace each region with padding of the same length so offsets and
        // line numbers stay put.
        foreach ($regions as [$offset, $length]) {
            $source = substr_replace($source, str_repeat(' ', $length), $offset, $length);
        }

        return $source;
    }

    /**
     * Drop the PHP Blade generated, leaving only what becomes page output.
     *
     * Handles the long form, the short-echo form and the `<?php(...)` shorthand
     * Blade emits for inline statements.
     */
    private function stripGeneratedPhp(string $compiled): string
    {
        $patterns = [
            '/<\?php\s.*?\?>/s',
            '/<\?php\(.*?\)\s*;?\s*\?>/s',
            '/<\?php\(.*?\)(?!\s*;)/s',
            '/<\?=.*?\?>/s',
        ];

        foreach ($patterns as $pattern) {
            $compiled = (string) preg_replace($pattern, ' ', $compiled);
        }

        return $compiled;
    }
}