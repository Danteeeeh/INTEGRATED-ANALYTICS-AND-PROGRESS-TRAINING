/**
 * Horizontal-overflow measurement, run inside the page.
 *
 * The gate is the standard one: how much wider the document's content is than
 * the viewport it is shown in (`scrollWidth - clientWidth`). That number is
 * what decides the test outcome. Everything else here exists to explain a
 * failure: which elements stick out, and by how much.
 */

/**
 * Passed to `page.evaluate`, so it must stay self-contained — no imports and no
 * references to module scope.
 *
 * @param {number} tolerancePx slack for sub-pixel rounding
 */
export function measureHorizontalOverflow(tolerancePx) {
    const doc = document.documentElement;
    const scroller = document.scrollingElement || doc;
    const viewportWidth = scroller.clientWidth;

    const scrollWidth = Math.max(scroller.scrollWidth || 0, doc.scrollWidth || 0);
    const overflowPx = scrollWidth - viewportWidth;

    // Cross-check independent of scrollWidth: if the page really is wider than
    // the viewport it can be scrolled sideways, whether or not something paints
    // over the overflow.
    const restoreScroll = scroller.scrollLeft;
    scroller.scrollLeft = 1e6;
    const scrollablePx = Math.max(0, scroller.scrollLeft);
    scroller.scrollLeft = restoreScroll;

    const describe = (el) => {
        const classes = (el.getAttribute('class') || '')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 3)
            .map((c) => `.${c}`)
            .join('');

        return `${el.tagName.toLowerCase()}${el.id ? `#${el.id}` : ''}${classes}`;
    };

    // An element inside its own horizontal scroller (a wide table in a
    // `.table-responsive`, say) is deliberately scrollable, not a page overflow.
    const sitsInScroller = (el) => {
        for (let parent = el.parentElement; parent && parent !== doc; parent = parent.parentElement) {
            const overflowX = getComputedStyle(parent).overflowX;
            if (overflowX === 'auto' || overflowX === 'scroll' || overflowX === 'hidden' || overflowX === 'clip') {
                return true;
            }
        }

        return false;
    };

    const offenders = [];

    for (const el of Array.from(document.body ? document.body.querySelectorAll('*') : [])) {
        const style = getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') continue;

        const rect = el.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) continue;

        // Only elements that straddle the right edge. Something parked entirely
        // off-screen to the right (a closed drawer waiting to slide in) is
        // deliberate, not overflow.
        if (rect.left >= viewportWidth - tolerancePx) continue;

        const overhang = rect.right - viewportWidth;
        if (overhang <= tolerancePx) continue;
        if (sitsInScroller(el)) continue;

        offenders.push({
            selector: describe(el),
            overhangPx: Math.round(overhang),
            width: Math.round(rect.width),
        });
    }

    offenders.sort((a, b) => b.overhangPx - a.overhangPx);

    return {
        viewportWidth,
        scrollWidth,
        overflowPx,
        scrollablePx,
        innerWidth: window.innerWidth,
        bodyScrollWidth: document.body ? document.body.scrollWidth : 0,
        offenders: offenders.slice(0, 12),
        offenderCount: offenders.length,
    };
}

/**
 * Human-readable failure report: the number that failed the gate plus the
 * elements that most likely caused it.
 */
export function describeOverflow(dashboard, result, viewport) {
    const lines = [
        `${dashboard.label} dashboard (${dashboard.path}) overflows horizontally at ${viewport.width}x${viewport.height}.`,
        `Document content is ${result.overflowPx}px wider than the ${result.viewportWidth}px viewport ` +
            `(scrollWidth ${result.scrollWidth}, page scrollable sideways by ${result.scrollablePx}px).`,
    ];

    if (result.offenders.length) {
        lines.push('Widest elements sticking out past the right edge:');
        for (const offender of result.offenders) {
            lines.push(`  - ${offender.selector} (+${offender.overhangPx}px, width ${offender.width}px)`);
        }
        if (result.offenderCount > result.offenders.length) {
            lines.push(`  … and ${result.offenderCount - result.offenders.length} more.`);
        }
    } else {
        lines.push('No single element was identified as the cause — check for wide tables, fixed widths or unbroken text.');
    }

    return lines.join('\n');
}
