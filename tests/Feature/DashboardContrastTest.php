<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Structural accessibility guard for the shared dashboard palette.
 *
 * The dashboard and chart colours live in CSS custom properties, so a palette
 * tweak can silently make muted text or chart labels unreadable. These checks
 * read the actual token declarations, calculate WCAG relative luminance, and
 * enforce the 4.5:1 AA threshold for normal-sized text in both themes.
 */
class DashboardContrastTest extends TestCase
{
    private const MIN_CONTRAST = 4.5;

    private const MIN_NON_TEXT_CONTRAST = 3.0;

    public function test_shared_dashboard_text_tokens_meet_aa_contrast_in_both_themes(): void
    {
        $themes = $this->dashboardThemes();
        $surfaces = ['bg', 'surface', 'surface-raised', 'surface-2', 'surface-3'];

        foreach (['dark', 'light'] as $theme) {
            foreach (['text', 'muted'] as $text) {
                foreach ($surfaces as $surface) {
                    $this->assertTokenContrast(
                        $theme,
                        "--dash-$text",
                        $themes[$theme][$text],
                        "--dash-$surface",
                        $themes[$theme][$surface]
                    );
                }
            }
        }
    }

    public function test_capped_list_hint_color_meets_aa_contrast_in_both_themes(): void
    {
        $themes = $this->dashboardThemes();

        $hintRule = $this->cssBlock(
            $this->read('resources/css/dashboard-layout.css'),
            '/\.panel-cap-note\s*\{/'
        );

        $this->assertStringContainsString(
            'color:var(--dash-muted',
            $hintRule,
            'The capped-list hint should keep using the shared muted dashboard token.'
        );

        foreach (['dark', 'light'] as $theme) {
            $this->assertTokenContrast(
                $theme,
                'panel-cap-note hint',
                $themes[$theme]['muted'],
                '--dash-surface',
                $themes[$theme]['surface']
            );
        }
    }

    public function test_shared_accent_cyan_warning_and_status_text_colors_meet_aa_contrast_in_both_themes(): void
    {
        $themes = $this->dashboardThemes();
        $roles = [
            'accent',
            'cyan',
            'warning',
            'status-active',
            'status-pending',
            'status-inactive',
            'status-danger',
            'status-info',
        ];
        $surfaces = ['bg', 'surface', 'surface-raised', 'surface-2', 'surface-3'];

        foreach (['dark', 'light'] as $theme) {
            foreach ($roles as $role) {
                foreach ($surfaces as $surface) {
                    $this->assertTokenContrast(
                        $theme,
                        "--dash-$role",
                        $themes[$theme][$role],
                        "--dash-$surface",
                        $themes[$theme][$surface]
                    );
                }
            }
        }

        $userCss = $this->read('resources/css/user-ui-system.css');

        foreach (['active', 'pending', 'inactive', 'danger', 'info'] as $status) {
            $this->assertStringContainsString(
                "var(--dash-status-$status",
                $userCss,
                "The shared user status text should use --dash-status-$status."
            );
        }

        $this->assertStringContainsString(
            '--user-warning: var(--dash-warning',
            $userCss,
            'Shared warning text should use --dash-warning in both themes.'
        );
    }

    public function test_admin_badges_and_dashboard_status_text_use_shared_tokens(): void
    {
        $checks = [
            'resources/css/admin-ui.css' => [
                '.badge-active', '.badge-inactive', '.badge-pending', '.badge-draft',
                '.badge-warning', '.badge-suspended', '.badge-admin',
                '.badge-instructor', '.badge-student', '.badge-program',
                '.badge-success', '.badge-secondary', '.m-green', '.m-gray',
            ],
            'resources/css/app.css' => [
                '.trend-chip.', '.dash-list-icon.i-', '.dash-meta-chip.m-',
            ],
            'resources/css/dashboard-layout.css' => ['.dash-outcome.d-'],
            'resources/views/instructor/dashboard.blade.php' => [
                '.type-badge.type-', '.status-badge.', '.dash-meta-chip.m-',
                '.dash-list-icon.i-', '.panel-count', '.perf-score', '.announce-date{',
            ],
            'resources/views/student/dashboard.blade.php' => [
                '.qa-card:nth-child', '.type-badge.type-', '.overdue-head h4',
                '.od-meta', '.earned-badges-title i',
            ],
        ];

        foreach ($checks as $path => $selectors) {
            $this->assertStatusTextRulesUseDashboardTokens($path, $selectors);
        }
    }

    public function test_shared_chart_tick_and_text_colors_meet_aa_contrast_in_both_themes(): void
    {
        $chart = $this->chartViewThemes();
        $themes = $this->dashboardThemes();

        foreach (['dark', 'light'] as $theme) {
            foreach (['tick', 'text'] as $role) {
                foreach (['surface', 'surface-2'] as $surface) {
                    $this->assertTokenContrast(
                        $theme,
                        "chart $role",
                        $chart[$theme][$role],
                        "--dash-$surface",
                        $themes[$theme][$surface]
                    );
                }
            }
        }
    }

    public function test_chart_tooltip_tokens_meet_aa_contrast_in_both_themes(): void
    {
        $tooltip = $this->chartTooltipThemes();
        $themes = $this->dashboardThemes();
        $scoped = $this->instructorChartThemes();

        foreach (['dark', 'light'] as $theme) {
            $this->assertTokenContrast(
                $theme,
                'tooltip text',
                $tooltip[$theme]['text'],
                'tooltip background',
                $tooltip[$theme]['background']
            );

            $this->assertTokenContrast(
                $theme,
                'tooltip footer (--dash-muted)',
                $themes[$theme]['muted'],
                'tooltip background',
                $tooltip[$theme]['background']
            );

            $this->assertTokenContrast(
                $theme,
                'tooltip footer (--id-muted)',
                $scoped[$theme]['muted'],
                'tooltip background',
                $tooltip[$theme]['background']
            );
        }
    }

    public function test_scoped_chart_tokens_meet_aa_contrast_in_both_themes(): void
    {
        $tokens = $this->instructorChartThemes();

        foreach (['dark', 'light'] as $theme) {
            foreach (['text', 'muted'] as $text) {
                foreach (['surface', 'surface-2'] as $surface) {
                    $this->assertTokenContrast(
                        $theme,
                        "--id-$text",
                        $tokens[$theme][$text],
                        "--id-$surface",
                        $tokens[$theme][$surface]
                    );
                }
            }
        }
    }

    public function test_aa_staff_overview_and_report_pages_meet_aa_contrast_in_both_themes(): void
    {
        $themes = $this->dashboardThemes();
        $pages = [
            'AA Staff overview' => 'resources/views/aa/dashboard.blade.php',
            'AA assessment reports' => 'resources/views/aa/reports/index.blade.php',
            'AA assessment report detail' => 'resources/views/aa/reports/show.blade.php',
        ];

        foreach ($pages as $label => $path) {
            $source = $this->read($path);

            $this->assertStringContainsString(
                "@extends('layouts.aa')",
                $source,
                "$label should render through the AA Staff layout."
            );

            // The shared text/accent tokens are checked once above; this loop
            // only covers page-specific inline colours and link wiring.
            $this->assertInlineTextColorsMeetContrast($label, $path, $themes);
        }

        $this->assertLinkColorUsesThemeAwareToken(
            'resources/css/app.css',
            '/dash-section .view-all/',
            'The AA dashboard section links must use the theme-aware cyan token.'
        );
        $this->assertLinkColorUsesThemeAwareToken(
            'resources/css/dashboard-palette.css',
            '/dash-polish .dash-panel-head .view-all/',
            'The AA dashboard panel-head links must use the theme-aware cyan token.'
        );
    }

    public function test_chart_series_and_focus_ring_colors_meet_non_text_contrast_in_both_themes(): void
    {
        $series = $this->chartSeriesThemes();
        $focus = $this->focusThemes();
        $themes = $this->dashboardThemes();
        $surfaces = ['bg', 'surface', 'surface-raised', 'surface-2', 'surface-3'];

        foreach (['dark', 'light'] as $theme) {
            foreach ($surfaces as $surface) {
                foreach ($series[$theme] as $name => $color) {
                    $this->assertNonTextContrast(
                        $theme,
                        "chart series $name",
                        $color,
                        "--dash-$surface",
                        $themes[$theme][$surface]
                    );
                }

                $this->assertNonTextContrast(
                    $theme,
                    'focus ring (--bcp-focus)',
                    $focus[$theme],
                    "--dash-$surface",
                    $themes[$theme][$surface]
                );
            }
        }

        $this->assertFocusRingRulesUseSharedToken();
    }

    public function test_dashboard_chart_configs_use_shared_palette_colors(): void
    {
        $series = $this->chartSeriesThemes();
        $views = [
            'instructor' => 'resources/views/instructor/dashboard.blade.php',
            'student' => 'resources/views/student/dashboard.blade.php',
            'admin' => 'resources/views/admin/dashboard.blade.php',
            'aa' => 'resources/views/aa/dashboard.blade.php',
        ];

        foreach ($views as $label => $path) {
            $source = $this->read($path);

            $this->assertStringContainsString(
                'LMSCharts.palette()',
                $source,
                "The $label dashboard chart series should resolve the shared theme palette."
            );

            preg_match_all('/\bpalette\.([a-z][a-z0-9]*)/', $source, $matches);
            $keys = array_values(array_unique($matches[1]));

            $this->assertNotEmpty($keys, "No shared palette colours were found in the $label dashboard.");

            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $series['dark'], "The $label dashboard uses unknown dark palette key [$key].");
                $this->assertArrayHasKey($key, $series['light'], "The $label dashboard uses unknown light palette key [$key].");
            }

            preg_match_all(
                '/\b(?:backgroundColor|borderColor|pointBackgroundColor|hoverBackgroundColor)\s*:\s*[^;\n]*(#[0-9a-f]{3,6}|rgba?\([^)\n]*\))/i',
                $source,
                $literals
            );

            $this->assertSame(
                [],
                $literals[0],
                "The $label dashboard still configures chart series with literal colours instead of the shared palette."
            );
        }
    }

    public function test_shared_accent_and_chart_brand_tokens_meet_non_text_contrast_in_both_themes(): void
    {
        $themes = $this->dashboardThemes();
        $faculty = $this->instructorChartThemes();
        // --dash-accent and --dash-cyan already pass the stricter 4.5:1 text
        // check above, so repeating them at 3:1 adds nothing.
        $tokens = [
            'bcp-cyan-400' => $this->cssTokenThemes(
                'resources/css/app.css',
                'bcp-cyan-400',
                '/:root\s*\{/',
                '/body\.light-mode\s*\{/'
            ),
            'faculty-chart-brand' => [
                'dark' => $faculty['dark']['brand'],
                'light' => $faculty['light']['brand'],
            ],
            'faculty-chart-cyan' => [
                'dark' => $faculty['dark']['cyan'],
                'light' => $faculty['light']['cyan'],
            ],
            'lms-accent' => $this->cssTokenThemes(
                'resources/css/lms-polish.css',
                'lms-accent',
                '/:root\s*\{/',
                '/body\.light-mode\s*\{/'
            ),
            'user-accent-admin' => $this->cssTokenThemes(
                'resources/css/user-ui-system.css',
                'user-accent',
                '/body\.role-admin\s*\{/',
                '/body\.light-mode\.role-admin\s*\{/'
            ),
            'user-accent-instructor' => $this->cssTokenThemes(
                'resources/css/user-ui-system.css',
                'user-accent',
                '/body\.role-instructor\s*\{/',
                '/body\.light-mode\.role-instructor\s*\{/'
            ),
            'user-accent-student' => $this->cssTokenThemes(
                'resources/css/user-ui-system.css',
                'user-accent',
                '/body\.role-student\s*\{/',
                '/body\.light-mode\.role-student\s*\{/'
            ),
        ];
        $surfaces = ['bg', 'surface', 'surface-raised', 'surface-2', 'surface-3'];

        foreach (['dark', 'light'] as $theme) {
            foreach ($tokens as $name => $pair) {
                foreach ($surfaces as $surface) {
                    $this->assertNonTextContrast(
                        $theme,
                        $name,
                        $pair[$theme],
                        "--dash-$surface",
                        $themes[$theme][$surface]
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function dashboardThemes(): array
    {
        $css = $this->read('resources/css/dashboard-palette.css');

        $dark = $this->cssVariables($this->cssBlock($css, '/:root\s*\{/'), 'dash-');
        $lightOverrides = $this->cssVariables($this->cssBlock($css, '/body\.light-mode\s*\{/'), 'dash-');

        return [
            'dark' => $dark,
            'light' => array_replace($dark, $lightOverrides),
        ];
    }

    /**
     * All three dashboards declare the same CHART_TICK / CHART_TEXT constants.
     * Assert that shared contract before checking either colour.
     *
     * @return array{dark: array{tick: string, text: string}, light: array{tick: string, text: string}}
     */
    private function chartViewThemes(): array
    {
        $views = [
            'instructor' => 'resources/views/instructor/dashboard.blade.php',
            'student' => 'resources/views/student/dashboard.blade.php',
            'admin' => 'resources/views/admin/dashboard.blade.php',
        ];

        $palettes = [];

        foreach ($views as $name => $path) {
            $source = $this->read($path);

            $tick = $this->jsThemePair($source, 'CHART_TICK', $path);
            $text = $this->jsThemePair($source, 'CHART_TEXT', $path);

            $palettes[$name] = [
                'dark' => ['tick' => $tick['dark'], 'text' => $text['dark']],
                'light' => ['tick' => $tick['light'], 'text' => $text['light']],
            ];
        }

        $reference = $palettes['instructor'];

        foreach ($palettes as $name => $palette) {
            $this->assertSame(
                $reference,
                $palette,
                "The $name dashboard's chart text constants drifted from the shared chart palette."
            );
        }

        return $reference;
    }

    /**
     * @return array<string, array{background: string, text: string}>
     */
    private function chartTooltipThemes(): array
    {
        $source = $this->read('resources/js/charts.js');
        $background = $this->jsThemePair($source, 'tooltip', 'resources/js/charts.js');
        $text = $this->jsThemePair($source, 'tooltipText', 'resources/js/charts.js');

        return [
            'dark' => ['background' => $background['dark'], 'text' => $text['dark']],
            'light' => ['background' => $background['light'], 'text' => $text['light']],
        ];
    }

    /**
     * The chart layer has separate dark and light series palettes because the
     * luminous dark-mode colours are not readable on white chart cards.
     *
     * @return array<string, array<string, string>>
     */
    private function chartSeriesThemes(): array
    {
        $source = $this->read('resources/js/charts.js');

        return [
            'dark' => $this->jsObjectColors(
                $this->jsObjectBlock($source, 'PALETTE', 'resources/js/charts.js'),
                'resources/js/charts.js'
            ),
            'light' => $this->jsObjectColors(
                $this->jsObjectBlock($source, 'LIGHT_PALETTE', 'resources/js/charts.js'),
                'resources/js/charts.js'
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function focusThemes(): array
    {
        $source = $this->read('resources/css/app.css');
        $root = $this->cssBlock($source, '/:root\s*\{/');

        if (! preg_match('/--bcp-focus\s*:\s*([^;}]+)/', $root, $darkMatch)) {
            $this->fail('Could not find the dark-mode --bcp-focus token in resources/css/app.css.');
        }

        if (! preg_match('/body\.light-mode\s*\{[^}]*--bcp-focus\s*:\s*([^;}]+)/s', $source, $match)) {
            $this->fail('Could not find the light-mode --bcp-focus override in resources/css/app.css.');
        }

        return ['dark' => trim($darkMatch[1]), 'light' => trim($match[1])];
    }

    /**
     * @return array{dark: string, light: string}
     */
    private function cssTokenThemes(
        string $path,
        string $token,
        string $darkSelectorPattern,
        string $lightSelectorPattern
    ): array {
        $source = $this->read($path);

        return [
            'dark' => $this->cssVariableValue($this->cssBlock($source, $darkSelectorPattern), $token, $path),
            'light' => $this->cssVariableValue($this->cssBlock($source, $lightSelectorPattern), $token, $path),
        ];
    }

    private function cssVariableValue(string $block, string $token, string $path): string
    {
        if (! preg_match('/--'.preg_quote($token, '/').'\s*:\s*([^;}]+)/', $block, $match)) {
            $this->fail("Could not find --$token in $path.");
        }

        return trim($match[1]);
    }

    private function assertFocusRingRulesUseSharedToken(): void
    {
        $paths = [
            'resources/css/app.css',
            'resources/css/lms-polish.css',
            'resources/css/user-ui-system.css',
            'resources/css/topbar-global-ui.css',
            'resources/css/dashboard-palette.css',
            'resources/css/dashboard-layout.css',
        ];

        foreach ($paths as $path) {
            preg_match_all('/([^{}]+)\{([^{}]*)\}/', $this->read($path), $rules, PREG_SET_ORDER);
            $checked = 0;

            foreach ($rules as $rule) {
                $selector = trim(preg_replace('/\s+/', ' ', $rule[1]));

                if (! str_contains($selector, 'focus')
                    || ! preg_match('/(?:^|;)\s*outline\s*:\s*([^;}]+)/i', $rule[2], $outline)) {
                    continue;
                }

                if (preg_match('/^(?:0|none)\b/i', trim($outline[1]))) {
                    continue;
                }

                $checked++;
                $this->assertStringContainsString(
                    'var(--bcp-focus',
                    $outline[1],
                    "$path focus rule [$selector] should use the shared --bcp-focus token."
                );
            }

            $this->assertGreaterThan(0, $checked, "No focus-ring rules were checked in $path.");
        }
    }

    /**
     * The instructor dashboard scopes the chart layer's --id-* tokens.
     *
     * @return array<string, array<string, string>>
     */
    private function instructorChartThemes(): array
    {
        $source = $this->read('resources/views/instructor/dashboard.blade.php');

        $dark = $this->cssVariables(
            $this->cssBlock($source, '/\.main:has\(\.dash-scope\) \.content\s*\{/'),
            'id-'
        );
        $lightOverrides = $this->cssVariables(
            $this->cssBlock($source, '/body\.light-mode \.main:has\(\.dash-scope\) \.content\s*\{/'),
            'id-'
        );

        return [
            'dark' => $dark,
            'light' => array_replace($dark, $lightOverrides),
        ];
    }

    private function assertTokenContrast(
        string $theme,
        string $foregroundName,
        string $foreground,
        string $backgroundName,
        string $background
    ): void {
        $ratio = $this->contrastRatio($foreground, $background);

        $this->assertGreaterThanOrEqual(
            self::MIN_CONTRAST,
            $ratio,
            sprintf(
                '%s theme: %s (%s) on %s (%s) has %.2f:1 contrast; WCAG AA for normal text is %.1f:1.',
                $theme,
                $foregroundName,
                $foreground,
                $backgroundName,
                $background,
                $ratio,
                self::MIN_CONTRAST
            )
        );
    }

    private function assertNonTextContrast(
        string $theme,
        string $foregroundName,
        string $foreground,
        string $backgroundName,
        string $background
    ): void {
        $ratio = $this->contrastRatio($foreground, $background);

        $this->assertGreaterThanOrEqual(
            self::MIN_NON_TEXT_CONTRAST,
            $ratio,
            sprintf(
                '%s theme: %s (%s) on %s (%s) has %.2f:1 contrast; WCAG AA for non-text UI is %.1f:1.',
                $theme,
                $foregroundName,
                $foreground,
                $backgroundName,
                $background,
                $ratio,
                self::MIN_NON_TEXT_CONTRAST
            )
        );
    }

    /**
     * AA views still carry a few inline text colours. Resolve the token colours
     * they are allowed to use and enforce the same threshold against both page
     * surfaces. Icon-only <i> colours are intentionally not part of this text
     * check.
     *
     * @param array<string, array<string, string>> $themes
     */
    private function assertInlineTextColorsMeetContrast(string $label, string $path, array $themes): void
    {
        preg_match_all(
            '/<(span|td|div|strong|p|a|button|h[1-6])\b[^>]*\bstyle="([^"]*)"[^>]*>/i',
            $this->read($path),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            if (! preg_match('/(?:^|;)\s*color\s*:\s*([^;"]+)/i', $match[2], $colorMatch)) {
                continue;
            }

            $value = trim($colorMatch[1]);

            foreach (['dark', 'light'] as $theme) {
                $foreground = $this->resolveThemeColor($value, $themes[$theme], $path);

                foreach (['surface', 'bg'] as $surface) {
                    $this->assertTokenContrast(
                        $theme,
                        "$label inline text [$value]",
                        $foreground,
                        "--dash-$surface",
                        $themes[$theme][$surface]
                    );
                }
            }
        }
    }

    /**
     * @param array<string, string> $theme
     */
    private function resolveThemeColor(string $color, array $theme, string $path): string
    {
        if (preg_match('/^var\(--dash-([a-z0-9-]+)/i', $color, $match)) {
            $token = strtolower($match[1]);

            if (! array_key_exists($token, $theme)) {
                $this->fail("$path uses unknown dashboard token colour [$color].");
            }

            return $theme[$token];
        }

        if (preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $color)) {
            return $color;
        }

        $this->fail("$path uses unsupported inline text colour [$color].");
    }

    private function assertLinkColorUsesThemeAwareToken(string $path, string $selectorPattern, string $message): void
    {
        $rule = $this->cssBlock($this->read($path), $selectorPattern);

        $this->assertStringContainsString('color:var(--dash-cyan', $rule, $message);
    }

    /**
     * Status text must resolve through a shared token, so both themes pick up
     * the contrast-checked value instead of maintaining a second literal.
     *
     * @param string[] $selectorFragments
     */
    private function assertStatusTextRulesUseDashboardTokens(string $path, array $selectorFragments): void
    {
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $this->read($path), $rules, PREG_SET_ORDER);

        $checked = 0;

        foreach ($rules as $rule) {
            $selector = trim(preg_replace('/\s+/', ' ', $rule[1]));
            $matchesSelector = false;

            foreach ($selectorFragments as $fragment) {
                if (str_contains($selector, $fragment)) {
                    $matchesSelector = true;
                    break;
                }
            }

            if (! $matchesSelector || ! preg_match('/(?:^|;)\s*color\s*:\s*([^;}]+)/i', $rule[2], $color)) {
                continue;
            }

            $checked++;
            $this->assertMatchesRegularExpression(
                '/^var\(--dash-(?:status-[a-z-]+|warning|accent|cyan)(?:\s*,[^)]*)?\)(?:\s*!important)?$/i',
                trim($color[1]),
                "$path selector [$selector] should use a shared dashboard status token instead of a literal text colour."
            );
        }

        $this->assertGreaterThan(0, $checked, "No status text rules were checked in $path.");
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $foregroundLuminance = $this->relativeLuminance($foreground);
        $backgroundLuminance = $this->relativeLuminance($background);
        $lighter = max($foregroundLuminance, $backgroundLuminance);
        $darker = min($foregroundLuminance, $backgroundLuminance);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        [$red, $green, $blue] = $this->rgb($hex);

        $channels = array_map(
            fn (float $channel): float => $channel <= 0.03928
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4,
            [$red, $green, $blue]
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-f]{3}$/i', $hex)) {
            $hex = implode('', array_map(fn (string $char): string => $char.$char, str_split($hex)));
        }

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $this->fail("Expected a solid hex colour token, got [$hex]. Alpha colours are not supported by this guard.");
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function cssVariables(string $block, string $prefix): array
    {
        preg_match_all(
            '/--'.preg_quote($prefix, '/').'([a-z0-9-]+)\s*:\s*([^;]+);/i',
            $block,
            $matches,
            PREG_SET_ORDER
        );

        $variables = [];

        foreach ($matches as $match) {
            $variables[strtolower($match[1])] = trim($match[2]);
        }

        return $variables;
    }

    private function cssBlock(string $css, string $selectorPattern): string
    {
        if (! preg_match($selectorPattern, $css, $match, PREG_OFFSET_CAPTURE)) {
            $this->fail("Could not find selector matching $selectorPattern.");
        }

        $open = strpos($css, '{', $match[0][1]);

        if ($open === false) {
            $this->fail("Selector matching $selectorPattern has no declaration block.");
        }

        $depth = 0;

        for ($index = $open; $index < strlen($css); $index++) {
            if ($css[$index] === '{') {
                $depth++;
            } elseif ($css[$index] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($css, $open + 1, $index - $open - 1);
                }
            }
        }

        $this->fail("Unclosed declaration block for selector matching $selectorPattern.");
    }

    /**
     * @return array<string, string>
     */
    private function jsThemePair(string $source, string $property, string $path): array
    {
        $pattern = "/".preg_quote($property, '/')."(?:\s*=\s*isLightTheme|\s*:\s*light)\s*\?\s*'([^']+)'\s*:\s*'([^']+)'/";

        if (! preg_match($pattern, $source, $match)) {
            $this->fail("Could not find the light/dark $property pair in $path.");
        }

        return ['light' => $match[1], 'dark' => $match[2]];
    }

    private function jsObjectBlock(string $source, string $name, string $path): string
    {
        $pattern = '/const\s+'.preg_quote($name, '/').'\s*=\s*\{(.*?)\};/s';

        if (! preg_match($pattern, $source, $match)) {
            $this->fail("Could not find the $name object in $path.");
        }

        return $match[1];
    }

    /**
     * @return array<string, string>
     */
    private function jsObjectColors(string $block, string $path): array
    {
        preg_match_all('/\b([a-z][a-z0-9]*)\s*:\s*\'([^\']+)\'/i', $block, $matches, PREG_SET_ORDER);

        $colors = [];

        foreach ($matches as $match) {
            $colors[$match[1]] = $match[2];
        }

        if ($colors === []) {
            $this->fail("No colour entries found in $path.");
        }

        return $colors;
    }

    private function read(string $path): string
    {
        $fullPath = base_path($path);

        $this->assertFileExists($fullPath);
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail("Could not read $path.");
        }

        return $contents;
    }
}
