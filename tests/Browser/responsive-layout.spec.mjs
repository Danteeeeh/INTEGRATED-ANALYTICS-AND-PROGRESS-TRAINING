/**
 * Responsive structure checks for the role dashboards and the admin list UI.
 *
 * The overflow spec already catches a page that is wider than the viewport.
 * This spec checks the layout boundaries themselves at phone and tablet
 * widths: content lines up with the viewport, the hero and filter controls
 * stay inside their containers, and wide admin tables scroll inside a panel
 * instead of being clipped or pushing the page sideways.
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards, tolerancePx } from './support/coverage.mjs';

async function waitForLayout(page) {
    await page.evaluate(() => document.fonts?.ready);
    await page.evaluate(
        () =>
            new Promise((resolve) => {
                requestAnimationFrame(() => requestAnimationFrame(resolve));
            }),
    );
    await page.evaluate(() => {
        document.querySelectorAll('.main').forEach((element) => {
            element.getAnimations().forEach((animation) => animation.finish());
        });
    });
}

function isPhoneOrTablet(testInfo) {
    return testInfo.project.name === 'mobile' || testInfo.project.name === 'tablet';
}

for (const dashboard of dashboards) {
    test.describe(`${dashboard.label} dashboard layout`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('keeps content, hero and filters inside the mobile/tablet viewport', async ({ page }, testInfo) => {
            test.skip(!isPhoneOrTablet(testInfo), '390px and tablet breakpoints only');

            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
            expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render its dashboard content`,
            ).toBeVisible();
            await waitForLayout(page);

            const result = await page.evaluate((tolerance) => {
                const rect = (element) => {
                    const box = element.getBoundingClientRect();

                    return {
                        left: box.left,
                        right: box.right,
                        top: box.top,
                        bottom: box.bottom,
                        width: box.width,
                        height: box.height,
                    };
                };

                const visible = (element) => {
                    const style = getComputedStyle(element);
                    const box = element.getBoundingClientRect();

                    return (
                        style.display !== 'none' &&
                        style.visibility !== 'hidden' &&
                        Number(style.opacity) > 0 &&
                        box.width > 0 &&
                        box.height > 0
                    );
                };

                const within = (inner, outer) =>
                    inner.left >= outer.left - tolerance &&
                    inner.right <= outer.right + tolerance &&
                    inner.top >= outer.top - tolerance &&
                    inner.bottom <= outer.bottom + tolerance;

                const viewportWidth = document.documentElement.clientWidth;
                const content = document.querySelector('.main > .content') ?? document.querySelector('.content');
                const hero = document.querySelector('.user-hero');
                const filters = Array.from(
                    document.querySelectorAll('.filter-form, .table-toolbar, .dash-search, .search-container'),
                ).filter(visible);

                const contentRect = content ? rect(content) : null;
                const heroRect = hero && visible(hero) ? rect(hero) : null;
                const outOfBounds = [];

                if (heroRect) {
                    const heroChildren = [
                        ...document.querySelectorAll('.user-hero-copy, .user-hero-actions, .user-hero-actions > *'),
                    ].filter(visible);

                    for (const child of heroChildren) {
                        const childRect = rect(child);

                        if (!within(childRect, heroRect)) {
                            outOfBounds.push({
                                selector: child.className || child.tagName.toLowerCase(),
                                owner: 'hero',
                                ...childRect,
                            });
                        }
                    }
                }

                const filterReports = filters.map((filter) => {
                    const filterRect = rect(filter);
                    const controls = [...filter.querySelectorAll('input, select, button, a.btn')].filter(visible);
                    const overflowingControls = controls
                        .filter((control) => !within(rect(control), filterRect))
                        .map((control) => ({
                            selector: control.className || control.tagName.toLowerCase(),
                            ...rect(control),
                        }));

                    return {
                        selector: filter.className || filter.tagName.toLowerCase(),
                        ...filterRect,
                        scrollWidth: filter.scrollWidth,
                        clientWidth: filter.clientWidth,
                        overflowingControls,
                    };
                });

                return {
                    viewportWidth,
                    documentScrollWidth: document.documentElement.scrollWidth,
                    bodyScrollWidth: document.body.scrollWidth,
                    content: contentRect,
                    hero: heroRect,
                    filters: filterReports,
                    outOfBounds,
                };
            }, tolerancePx);

            expect(
                result.documentScrollWidth,
                'the document should not be wider than the viewport',
            ).toBeLessThanOrEqual(result.viewportWidth + tolerancePx);
            expect(
                result.bodyScrollWidth,
                'the body should not be wider than the viewport',
            ).toBeLessThanOrEqual(result.viewportWidth + tolerancePx);

            expect(result.content, 'the dashboard should render a .content column').not.toBeNull();
            expect(result.content.left, 'content should start at the viewport edge').toBeGreaterThanOrEqual(-tolerancePx);
            expect(result.content.right, 'content should end at the viewport edge').toBeLessThanOrEqual(
                result.viewportWidth + tolerancePx,
            );
            expect(
                Math.abs(result.content.width - result.viewportWidth),
                `content width ${result.content.width} should match the ${result.viewportWidth}px viewport`,
            ).toBeLessThanOrEqual(tolerancePx);

            expect(result.hero, 'the dashboard should render a hero').not.toBeNull();
            expect(result.hero.left, 'hero should stay inside the content column').toBeGreaterThanOrEqual(
                result.content.left - tolerancePx,
            );
            expect(result.hero.right, 'hero should stay inside the content column').toBeLessThanOrEqual(
                result.content.right + tolerancePx,
            );
            expect(result.outOfBounds, 'hero content should not spill past the hero').toEqual([]);

            for (const filter of result.filters) {
                expect(filter.left, `${filter.selector} should stay inside the content column`).toBeGreaterThanOrEqual(
                    result.content.left - tolerancePx,
                );
                expect(filter.right, `${filter.selector} should stay inside the content column`).toBeLessThanOrEqual(
                    result.content.right + tolerancePx,
                );
                expect(
                    filter.scrollWidth,
                    `${filter.selector} should not overflow its own width`,
                ).toBeLessThanOrEqual(filter.clientWidth + tolerancePx);
                expect(
                    filter.overflowingControls,
                    `${filter.selector} controls should stay inside the filter`,
                ).toEqual([]);
            }
        });
    });
}

test.describe('Admin list filter and table', () => {
    test.use({ storageState: authStateFile('admin') });

    test('keeps the courses filter contained and scrolls the wide table', async ({ page }, testInfo) => {
        test.skip(!isPhoneOrTablet(testInfo), '390px and tablet breakpoints only');

        const response = await page.goto('/admin/courses', { waitUntil: 'domcontentloaded' });
        expect(response?.status(), 'GET /admin/courses').toBe(200);
        await expect(page.locator('.filter-form')).toBeVisible();
        await waitForLayout(page);

        const result = await page.evaluate((tolerance) => {
            const rect = (element) => {
                const box = element.getBoundingClientRect();

                return { left: box.left, right: box.right, width: box.width };
            };
            const visible = (element) => {
                const style = getComputedStyle(element);
                const box = element.getBoundingClientRect();

                return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
            };
            const within = (inner, outer) =>
                inner.left >= outer.left - tolerance && inner.right <= outer.right + tolerance;

            const content = document.querySelector('.main > .content') ?? document.querySelector('.content');
            const filter = document.querySelector('.filter-form');
            const tableWrap = document.querySelector('.table-responsive');
            const table = tableWrap?.querySelector('table');
            const contentRect = content ? rect(content) : null;
            const filterRect = filter && visible(filter) ? rect(filter) : null;
            const tableWrapRect = tableWrap ? rect(tableWrap) : null;
            const tableRect = table ? rect(table) : null;

            const controls = filter
                ? [...filter.querySelectorAll('input, select, button, a.btn')]
                      .filter(visible)
                      .filter((control) => !within(rect(control), filterRect))
                      .map((control) => control.className || control.tagName.toLowerCase())
                : [];

            let maxScroll = 0;
            let scrollLeftAfterScroll = 0;

            if (tableWrap) {
                maxScroll = tableWrap.scrollWidth - tableWrap.clientWidth;
                tableWrap.scrollLeft = maxScroll;
                scrollLeftAfterScroll = tableWrap.scrollLeft;
                tableWrap.scrollLeft = 0;
            }

            return {
                viewportWidth: document.documentElement.clientWidth,
                documentScrollWidth: document.documentElement.scrollWidth,
                content: contentRect,
                filter: filterRect,
                filterScrollWidth: filter?.scrollWidth ?? 0,
                filterClientWidth: filter?.clientWidth ?? 0,
                overflowingControls: controls,
                tableWrap: tableWrapRect,
                table: tableRect,
                tableOverflowX: tableWrap ? getComputedStyle(tableWrap).overflowX : null,
                maxScroll,
                scrollLeftAfterScroll,
            };
        }, tolerancePx);

        expect(result.documentScrollWidth, 'the filter page should not overflow the viewport').toBeLessThanOrEqual(
            result.viewportWidth + tolerancePx,
        );
        expect(result.content, 'the page should render a content column').not.toBeNull();
        expect(Math.abs(result.content.width - result.viewportWidth)).toBeLessThanOrEqual(tolerancePx);

        expect(result.filter, 'the courses page should render its filter form').not.toBeNull();
        expect(result.filter.left, 'filter should start inside the content column').toBeGreaterThanOrEqual(
            result.content.left - tolerancePx,
        );
        expect(result.filter.right, 'filter should end inside the content column').toBeLessThanOrEqual(
            result.content.right + tolerancePx,
        );
        expect(result.filterScrollWidth, 'the filter should not overflow its own width').toBeLessThanOrEqual(
            result.filterClientWidth + tolerancePx,
        );
        expect(result.overflowingControls, 'filter controls should stay inside the filter').toEqual([]);

        expect(result.tableWrap, 'the courses table should have a scroll wrapper').not.toBeNull();
        expect(['auto', 'scroll']).toContain(result.tableOverflowX);
        expect(result.table, 'the courses table should render').not.toBeNull();
        expect(result.maxScroll, 'the wide admin table should be wider than its scroll wrapper').toBeGreaterThan(
            tolerancePx,
        );
        expect(
            result.scrollLeftAfterScroll,
            'the admin table wrapper should actually scroll horizontally',
        ).toBeGreaterThan(0);
    });
});

for (const dashboard of dashboards.filter((entry) => ['instructor', 'student'].includes(entry.role))) {
    test.describe(`${dashboard.label} analytics header at sidebar tablet widths`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('keeps the analytics title and filters from wrapping or overlapping at 901, 960 and 1024', async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'desktop', 'explicit sidebar-tablet checks run once');

            for (const width of [901, 960, 1024]) {
                await page.setViewportSize({ width, height: 1000 });

                const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
                expect(response?.status(), `GET ${dashboard.path} at ${width}px`).toBe(200);
                await expect(
                    page.getByText(dashboard.marker, { exact: false }).first(),
                    `${dashboard.path} did not render at ${width}px`,
                ).toBeVisible();
                await expect(page.locator('.dash-analytics .dash-panel-head')).toBeVisible();
                await page.evaluate(
                    () =>
                        new Promise((resolve) => {
                            requestAnimationFrame(() => requestAnimationFrame(resolve));
                        }),
                );

                const result = await page.evaluate((tolerance) => {
                    const header = document.querySelector('.dash-analytics .dash-panel-head');
                    const heading = header.querySelector('h4');
                    const controls = header.querySelector('.analytics-controls');
                    const box = (element) => {
                        const rect = element.getBoundingClientRect();

                        return {
                            left: rect.left,
                            right: rect.right,
                            top: rect.top,
                            bottom: rect.bottom,
                            width: rect.width,
                            height: rect.height,
                        };
                    };
                    const headerBox = box(header);
                    const headingBox = box(heading);
                    const controlsBox = box(controls);
                    const within = (inner, outer) =>
                        inner.left >= outer.left - tolerance &&
                        inner.right <= outer.right + tolerance &&
                        inner.top >= outer.top - tolerance &&
                        inner.bottom <= outer.bottom + tolerance;

                    return {
                        headingLines: Math.round(
                            headingBox.height / Number.parseFloat(getComputedStyle(heading).lineHeight),
                        ),
                        controlsStartBelowHeading: controlsBox.top >= headingBox.bottom - tolerance,
                        overlap: !(
                            headingBox.right <= controlsBox.left ||
                            controlsBox.right <= headingBox.left ||
                            headingBox.bottom <= controlsBox.top ||
                            controlsBox.bottom <= headingBox.top
                        ),
                        controlsOutOfBounds: [...controls.children]
                            .map((control) => control.id || control.tagName.toLowerCase())
                            .filter((control, index) => !within(box(controls.children[index]), headerBox)),
                        headerScrollWidth: header.scrollWidth,
                        headerClientWidth: header.clientWidth,
                        header: headerBox,
                        heading: headingBox,
                        controls: controlsBox,
                    };
                }, tolerancePx);

                expect(result.headingLines, `${width}px: title should stay on one line`).toBe(1);
                expect(result.overlap, `${width}px: title should not overlap the filters`).toBe(false);
                expect(
                    result.controlsStartBelowHeading,
                    `${width}px: filters should occupy their own row`,
                ).toBe(true);
                expect(result.controlsOutOfBounds, `${width}px: filters should stay inside the header`).toEqual([]);
                expect(
                    result.headerScrollWidth,
                    `${width}px: header should not overflow horizontally`,
                ).toBeLessThanOrEqual(result.headerClientWidth + tolerancePx);
            }
        });
    });
}

test.describe('Admin dashboard tables', () => {
    test.use({ storageState: authStateFile('admin') });

    test('scrolls each wide table without moving the panel header', async ({ page }, testInfo) => {
        test.skip(!isPhoneOrTablet(testInfo), '390px and tablet breakpoints only');

        const response = await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
        expect(response?.status(), 'GET /admin/dashboard').toBe(200);
        await expect(page.getByText('System status', { exact: false }).first()).toBeVisible();
        await waitForLayout(page);

        const tables = await page.evaluate((tolerance) => {
            const visible = (element) => {
                const style = getComputedStyle(element);
                const box = element.getBoundingClientRect();

                return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
            };

            return [...document.querySelectorAll('.dash-panel table, .table-responsive table')]
                .filter((table) => visible(table))
                .map((table) => {
                    const panel = table.closest('.dash-panel');
                    const panelHead = panel?.querySelector('.dash-panel-head');
                    const headerLeftBefore = panelHead?.getBoundingClientRect().left;
                    let scroller = null;

                    for (let parent = table.parentElement; parent && parent !== document.body; parent = parent.parentElement) {
                        const overflowX = getComputedStyle(parent).overflowX;

                        if (overflowX === 'auto' || overflowX === 'scroll') {
                            scroller = parent;
                            break;
                        }
                    }

                    if (!scroller) {
                        return {
                            tableWidth: table.getBoundingClientRect().width,
                            hasScroller: false,
                        };
                    }

                    const maxScroll = scroller.scrollWidth - scroller.clientWidth;
                    scroller.scrollLeft = maxScroll;
                    const scrollLeftAfterScroll = scroller.scrollLeft;
                    scroller.scrollLeft = 0;

                    return {
                        tableWidth: table.getBoundingClientRect().width,
                        scrollerWidth: scroller.clientWidth,
                        overflowX: getComputedStyle(scroller).overflowX,
                        scrollerIsTableWrapper:
                            scroller.classList.contains('table-responsive') &&
                            scroller.parentElement === panel,
                        scrollerIsPanel: scroller === panel,
                        panelOverflowX: panel ? getComputedStyle(panel).overflowX : null,
                        panelScrolls: panel ? panel.scrollWidth > panel.clientWidth + tolerance : null,
                        panelScrollLeft: panel?.scrollLeft ?? null,
                        headerShift: panelHead
                            ? Math.abs(panelHead.getBoundingClientRect().left - headerLeftBefore)
                            : null,
                        maxScroll,
                        scrollLeftAfterScroll,
                        hasScroller: true,
                        scrolls: maxScroll > tolerance && scrollLeftAfterScroll > 0,
                    };
                });
        }, tolerancePx);

        expect(tables.length, 'the admin dashboard should render tables').toBeGreaterThan(0);
        expect(
            tables.filter((table) => !table.hasScroller),
            'every admin dashboard table should have a horizontal scroll container',
        ).toEqual([]);
        expect(
            tables.filter((table) => !table.scrollerIsTableWrapper),
            'each scroller should be a table-responsive wrapper directly inside its panel',
        ).toEqual([]);
        expect(
            tables.filter((table) => table.scrollerIsPanel),
            'the panel itself must not be the horizontal scroller',
        ).toEqual([]);
        expect(
            tables.filter((table) => table.panelOverflowX !== 'hidden'),
            'panels should keep horizontal overflow hidden',
        ).toEqual([]);
        expect(
            tables.filter((table) => table.panelScrolls),
            'the panel should not overflow horizontally',
        ).toEqual([]);
        expect(
            tables.filter((table) => table.panelScrollLeft !== 0),
            'scrolling a table must not scroll the panel',
        ).toEqual([]);
        expect(
            tables.filter((table) => table.headerShift > tolerancePx),
            'the panel header should stay fixed while its table scrolls',
        ).toEqual([]);
        expect(
            tables.some((table) => table.scrolls),
            'at least one admin dashboard table should actually scroll horizontally',
        ).toBe(true);
    });
});

// The stylesheet switches from the off-canvas mobile sidebar to the fixed
// desktop sidebar at 900px. Check the first pixel above that breakpoint: the
// content should start exactly where the sidebar ends, with no gap and no
// sideways scroll caused by the restored 248px offset.
for (const dashboard of dashboards.filter((entry) => ['admin', 'instructor', 'student'].includes(entry.role))) {
    test.describe(`${dashboard.label} sidebar breakpoint`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('restores the desktop sidebar offset cleanly at 901px', async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'desktop', 'the 901px breakpoint is checked once');

            await page.setViewportSize({ width: 901, height: 1000 });

            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
            expect(response?.status(), `GET ${dashboard.path} at 901px`).toBe(200);
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render at 901px`,
            ).toBeVisible();
            await page.waitForFunction(() => document.documentElement.clientWidth === 901);
            await waitForLayout(page);
            await page.waitForTimeout(100);

            const result = await page.evaluate((tolerance) => {
                const box = (element) => {
                    const rect = element.getBoundingClientRect();

                    return {
                        left: rect.left,
                        right: rect.right,
                        top: rect.top,
                        bottom: rect.bottom,
                        width: rect.width,
                        height: rect.height,
                    };
                };
                const visible = (element) => {
                    const style = getComputedStyle(element);
                    const rect = element.getBoundingClientRect();

                    return (
                        style.display !== 'none' &&
                        style.visibility !== 'hidden' &&
                        Number(style.opacity) > 0 &&
                        rect.width > 0 &&
                        rect.height > 0
                    );
                };
                const sidebar = [...document.querySelectorAll('#sidebar')].find(visible);
                const main = document.querySelector('.main');
                const content = document.querySelector('.main > .content') ?? document.querySelector('.content');

                if (!sidebar || !main || !content) {
                    return { missing: { sidebar: !sidebar, main: !main, content: !content } };
                }

                const sidebarBox = box(sidebar);
                const mainBox = box(main);
                const contentBox = box(content);
                const sidebarStyle = getComputedStyle(sidebar);
                const mainStyle = getComputedStyle(main);

                return {
                    missing: null,
                    viewportWidth: document.documentElement.clientWidth,
                    documentScrollWidth: document.documentElement.scrollWidth,
                    bodyScrollWidth: document.body.scrollWidth,
                    sidebar: {
                        ...sidebarBox,
                        position: sidebarStyle.position,
                        transform: sidebarStyle.transform,
                    },
                    main: {
                        ...mainBox,
                        marginLeft: Number.parseFloat(mainStyle.marginLeft) || 0,
                    },
                    content: contentBox,
                    tolerance,
                };
            }, tolerancePx);

            expect(result.missing, 'sidebar/main/content should render').toBeNull();
            expect(result.viewportWidth, 'the check should run at 901px').toBe(901);
            expect(result.documentScrollWidth, 'document should not overflow horizontally').toBeLessThanOrEqual(
                result.viewportWidth + tolerancePx,
            );
            expect(result.bodyScrollWidth, 'body should not overflow horizontally').toBeLessThanOrEqual(
                result.viewportWidth + tolerancePx,
            );
            expect(result.sidebar.position, 'sidebar should switch to the fixed desktop column').toBe('fixed');
            expect(result.sidebar.width, 'desktop sidebar should remain a visible column').toBeGreaterThan(150);
            expect(result.sidebar.left, 'desktop sidebar should start at the viewport edge').toBeGreaterThanOrEqual(
                -tolerancePx,
            );
            expect(result.sidebar.right, 'desktop sidebar should be on screen').toBeGreaterThan(0);
            expect(result.main.marginLeft, 'main should receive the desktop sidebar offset').toBeGreaterThan(0);
            expect(
                Math.abs(result.main.marginLeft - result.sidebar.width),
                'main offset should match the sidebar width',
            ).toBeLessThanOrEqual(tolerancePx);
            expect(
                Math.abs(result.main.left - result.sidebar.right),
                'content column should start exactly where the sidebar ends',
            ).toBeLessThanOrEqual(tolerancePx);
            expect(result.main.right, 'main should stay inside the viewport').toBeLessThanOrEqual(
                result.viewportWidth + tolerancePx,
            );
            expect(result.content.left, 'content should not sit under the sidebar').toBeGreaterThanOrEqual(
                result.sidebar.right - tolerancePx,
            );
            expect(result.content.right, 'content should stay inside the viewport').toBeLessThanOrEqual(
                result.viewportWidth + tolerancePx,
            );
        });
    });
}

// At the mobile sidebar breakpoint the drawer is an overlay: opening it must
// not push or reflow the main column. Check the exact breakpoint (900px) and a
// normal tablet width (768px), before, during and after the drawer cycle.
for (const dashboard of dashboards.filter((entry) => ['admin', 'instructor', 'student'].includes(entry.role))) {
    test.describe(`${dashboard.label} sidebar drawer`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('opens and closes without shifting the main column at 768px and 900px', async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'desktop', 'drawer checks set their own tablet widths');

            const readState = () =>
                page.evaluate(() => {
                    const rect = (element) => {
                        const box = element.getBoundingClientRect();

                        return {
                            left: box.left,
                            right: box.right,
                            top: box.top,
                            width: box.width,
                            height: box.height,
                        };
                    };
                    const sidebar = document.querySelector('#sidebar');
                    const overlay = document.querySelector('#sidebarOverlay');
                    const main = document.querySelector('.main');
                    const content = document.querySelector('.main > .content') ?? document.querySelector('.content');

                    return {
                        viewportWidth: document.documentElement.clientWidth,
                        documentScrollWidth: document.documentElement.scrollWidth,
                        bodyScrollWidth: document.body.scrollWidth,
                        scrollX: window.scrollX,
                        sidebar: {
                            collapsed: sidebar.classList.contains('collapsed'),
                            ...rect(sidebar),
                        },
                        overlayActive: overlay.classList.contains('active'),
                        main: rect(main),
                        content: rect(content),
                    };
                });

            const assertStable = (before, after, label) => {
                expect(after.main.left, `${label}: main left`).toBeCloseTo(before.main.left, 0);
                expect(after.main.right, `${label}: main right`).toBeCloseTo(before.main.right, 0);
                expect(after.main.width, `${label}: main width`).toBeCloseTo(before.main.width, 0);
                expect(after.content.left, `${label}: content left`).toBeCloseTo(before.content.left, 0);
                expect(after.content.right, `${label}: content right`).toBeCloseTo(before.content.right, 0);
                expect(after.scrollX, `${label}: horizontal scroll`).toBe(0);
            };

            const assertContained = (state, label) => {
                expect(state.viewportWidth, `${label}: viewport width`).toBeGreaterThanOrEqual(768);
                expect(state.documentScrollWidth, `${label}: document overflow`).toBeLessThanOrEqual(
                    state.viewportWidth + tolerancePx,
                );
                expect(state.bodyScrollWidth, `${label}: body overflow`).toBeLessThanOrEqual(
                    state.viewportWidth + tolerancePx,
                );
                expect(state.main.left, `${label}: main should start at the viewport edge`).toBeGreaterThanOrEqual(
                    -tolerancePx,
                );
                expect(state.main.right, `${label}: main should stay in view`).toBeLessThanOrEqual(
                    state.viewportWidth + tolerancePx,
                );
            };

            for (const width of [768, 900]) {
                await page.setViewportSize({ width, height: 1000 });

                const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
                expect(response?.status(), `GET ${dashboard.path} at ${width}px`).toBe(200);
                await expect(
                    page.getByText(dashboard.marker, { exact: false }).first(),
                    `${dashboard.path} did not render at ${width}px`,
                ).toBeVisible();
                await page.waitForFunction((expected) => document.documentElement.clientWidth === expected, width);
                await waitForLayout(page);
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebar').classList.contains('collapsed'))).toBe(true);

                const closed = await readState();
                assertContained(closed, `${width}px closed`);
                expect(closed.overlayActive, `${width}px closed: overlay should be hidden`).toBe(false);

                await page.locator('#hamburgerBtn').click();
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebar').classList.contains('collapsed'))).toBe(false);
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebarOverlay').classList.contains('active'))).toBe(true);
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebar').getBoundingClientRect().left)).toBeGreaterThanOrEqual(-tolerancePx);

                const opened = await readState();
                assertContained(opened, `${width}px opened`);
                expect(opened.overlayActive, `${width}px opened: overlay should be active`).toBe(true);
                expect(opened.sidebar.left, `${width}px opened: sidebar should start on screen`).toBeGreaterThanOrEqual(
                    -tolerancePx,
                );
                expect(opened.sidebar.width, `${width}px opened: sidebar should be visible`).toBeGreaterThan(150);
                assertStable(closed, opened, `${width}px opened`);

                // The overlay covers the topbar while the drawer is open, so the
                // user closes it by tapping the exposed page area to the right.
                await page.locator('#sidebarOverlay').click({ position: { x: width - 20, y: 400 } });
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebar').classList.contains('collapsed'))).toBe(true);
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebarOverlay').classList.contains('active'))).toBe(false);
                await expect.poll(() => page.evaluate(() => document.querySelector('#sidebar').getBoundingClientRect().right)).toBeLessThanOrEqual(tolerancePx);

                const reclosed = await readState();
                assertContained(reclosed, `${width}px reclosed`);
                expect(reclosed.overlayActive, `${width}px reclosed: overlay should be hidden`).toBe(false);
                assertStable(closed, reclosed, `${width}px reclosed`);
            }
        });
    });
}
