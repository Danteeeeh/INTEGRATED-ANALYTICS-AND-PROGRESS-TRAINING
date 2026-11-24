/**
 * Shared chart layer for the LMS dashboards.
 *
 * Chart.js is bundled here instead of pulled from a CDN, so charts keep
 * working behind a strict CSP, offline, or on a network that blocks
 * jsdelivr. Global defaults give every dashboard the same typography,
 * tooltip, legend and grid treatment, and the theme observer keeps the
 * canvases readable when the light/dark toggle flips.
 *
 * Existing views still call `new Chart(...)`; this module exposes the
 * (tracked) constructor as `window.Chart`, so they inherit the defaults
 * without a rewrite. New code should prefer `LMSCharts.mount(...)`.
 */
import Chart from 'chart.js/auto';

const FONT_STACK = "Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif";

const PALETTE = {
    brand: '#5b8cff',
    cyan: '#62c9f5',
    violet: '#a78bfa',
    green: '#34d399',
    amber: '#fbbf24',
    orange: '#fb923c',
    rose: '#fb7185',
    pink: '#f472b6',
    purple: '#8b5cf6',
    magenta: '#db2777',
    slate: '#94a3b8',
};

// The dark-mode colors are intentionally luminous. On white chart cards the
// same hues need deeper variants to keep 3:1 non-text contrast for the bars,
// lines, arcs and legend swatches.
const LIGHT_PALETTE = {
    brand: '#2563eb',
    cyan: '#0e7490',
    violet: '#6d28d9',
    green: '#047857',
    amber: '#b45309',
    orange: '#c2410c',
    rose: '#be123c',
    pink: '#be185d',
    purple: '#6d28d9',
    magenta: '#be185d',
    slate: '#64748b',
};

const SERIES = [
    PALETTE.brand,
    PALETTE.cyan,
    PALETTE.green,
    PALETTE.amber,
    PALETTE.violet,
    PALETTE.rose,
];

const LIGHT_SERIES = [
    LIGHT_PALETTE.brand,
    LIGHT_PALETTE.cyan,
    LIGHT_PALETTE.green,
    LIGHT_PALETTE.amber,
    LIGHT_PALETTE.violet,
    LIGHT_PALETTE.rose,
];

function tokenScope() {
    return document.querySelector('.dash-scope')
        || document.querySelector('.main .content')
        || document.body;
}

function token(name, fallback) {
    const value = getComputedStyle(tokenScope()).getPropertyValue(name).trim();
    return value || fallback;
}

function theme() {
    const light = document.body.classList.contains('light-mode');

    return {
        light,
        text: token('--id-text', token('--dash-text', light ? '#16233c' : '#e7eefc')),
        muted: token('--id-muted', token('--dash-muted', light ? '#5f708c' : '#98a7c4')),
        grid: token('--id-line', token('--dash-line', light ? 'rgba(31,52,88,.12)' : 'rgba(153,174,214,.12)')),
        surface: token('--id-surface-2', token('--dash-surface', light ? '#ffffff' : '#151c2c')),
        brand: token('--id-brand', token('--bcp-cyan-400', light ? LIGHT_PALETTE.brand : PALETTE.brand)),
        tooltip: light ? '#ffffff' : '#0d1424',
        tooltipText: light ? '#16233c' : '#f1f5ff',
        tooltipBorder: light ? 'rgba(31,52,88,.14)' : 'rgba(153,174,214,.24)',
    };
}

const charts = new Set();

function applyTheme() {
    const t = theme();

    Chart.defaults.font.family = FONT_STACK;
    Chart.defaults.font.size = 11.5;
    Chart.defaults.font.weight = '600';
    Chart.defaults.color = t.muted;
    Chart.defaults.borderColor = t.grid;

    Chart.defaults.plugins.legend.labels.color = t.text;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.boxHeight = 8;
    Chart.defaults.plugins.legend.labels.padding = 14;

    Chart.defaults.plugins.tooltip.backgroundColor = t.tooltip;
    Chart.defaults.plugins.tooltip.titleColor = t.tooltipText;
    Chart.defaults.plugins.tooltip.bodyColor = t.tooltipText;
    Chart.defaults.plugins.tooltip.footerColor = t.muted;
    Chart.defaults.plugins.tooltip.borderColor = t.tooltipBorder;
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 11;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 4;
    Chart.defaults.plugins.tooltip.titleFont = { weight: '700', size: 12 };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 11.5 };
}

applyTheme();

let paintedLight = theme().light;

function recolourValue(value, fromPalette, toPalette) {
    if (typeof value !== 'string') return value;

    const normalised = value.trim().toLowerCase();

    for (const [key, colour] of Object.entries(fromPalette)) {
        if (normalised === colour.toLowerCase()) {
            return toPalette[key];
        }
    }

    const match = normalised.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([\d.]+))?\s*\)$/);
    if (!match) return value;

    const hex = '#' + match.slice(1, 4)
        .map((channel) => Number(channel).toString(16).padStart(2, '0'))
        .join('');

    for (const [key, colour] of Object.entries(fromPalette)) {
        if (hex === colour.toLowerCase()) {
            if (match[4] === undefined) return toPalette[key];

            const target = toPalette[key].replace('#', '');
            const channels = [0, 2, 4].map((offset) => parseInt(target.slice(offset, offset + 2), 16));

            return `rgba(${channels[0]}, ${channels[1]}, ${channels[2]}, ${match[4]})`;
        }
    }

    return value;
}

function recolourSeries(datasets, fromPalette, toPalette) {
    const properties = [
        'backgroundColor', 'borderColor', 'pointBackgroundColor',
        'pointBorderColor', 'hoverBackgroundColor', 'hoverBorderColor',
    ];

    (datasets || []).forEach((dataset) => {
        properties.forEach((property) => {
            if (Array.isArray(dataset[property])) {
                dataset[property] = dataset[property].map((value) => recolourValue(value, fromPalette, toPalette));
            } else if (dataset[property] !== undefined) {
                dataset[property] = recolourValue(dataset[property], fromPalette, toPalette);
            }
        });
    });
}

function repaint() {
    const t = theme();
    const switched = t.light !== paintedLight;
    const fromPalette = paintedLight ? LIGHT_PALETTE : PALETTE;
    const toPalette = t.light ? LIGHT_PALETTE : PALETTE;

    charts.forEach((chart) => {
        if (!chart || !chart.options) return;

        if (switched) {
            recolourSeries(chart.data && chart.data.datasets, fromPalette, toPalette);
        }

        const legend = chart.options.plugins && chart.options.plugins.legend;
        if (legend && legend.labels) legend.labels.color = t.text;

        if (chart.options.scales) {
            Object.values(chart.options.scales).forEach((scale) => {
                if (!scale) return;
                if (scale.ticks) scale.ticks.color = t.muted;
                if (scale.grid && scale.grid.display !== false) scale.grid.color = t.grid;
            });
        }

        if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
            chart.data.datasets.forEach((dataset) => {
                dataset.borderColor = t.surface;
            });
        }

        chart.update('none');
    });

    paintedLight = t.light;
}

const NativeChart = Chart;

// Views that build charts after an async fetch still pass palette constants
// captured when the page loaded. Apply the current theme to the config before
// Chart.js performs its first draw, so a late chart is correct immediately and
// does not need a second update pass (which can fight the responsive layout).
function themeConfig(config) {
    if (!config) return;

    const t = theme();
    const options = config.options;
    const legend = options && options.plugins && options.plugins.legend;

    if (legend && legend.labels) legend.labels.color = t.text;

    if (options && options.scales) {
        Object.values(options.scales).forEach((scale) => {
            if (!scale) return;
            if (scale.ticks) scale.ticks.color = t.muted;
            if (scale.grid && scale.grid.display !== false) scale.grid.color = t.grid;
        });
    }

    if (config.type === 'doughnut' || config.type === 'pie') {
        (config.data && config.data.datasets || []).forEach((dataset) => {
            dataset.borderColor = t.surface;
        });
    }
}

const TrackedChart = new Proxy(NativeChart, {
    construct(target, args) {
        themeConfig(args[1]);

        const chart = new target(...args);
        charts.add(chart);

        // Views destroy charts before redrawing. Forget them here too, so the
        // theme repaint never touches a destroyed instance.
        const destroy = chart.destroy.bind(chart);
        chart.destroy = () => {
            charts.delete(chart);
            destroy();
        };

        return chart;
    },
});

function merge(base, extra) {
    const out = { ...base };

    Object.entries(extra || {}).forEach(([key, value]) => {
        if (value && typeof value === 'object' && !Array.isArray(value) && base[key] && typeof base[key] === 'object' && !Array.isArray(base[key])) {
            out[key] = merge(base[key], value);
        } else {
            out[key] = value;
        }
    });

    return out;
}

function baseOptions(extra = {}) {
    return merge({
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'nearest', intersect: false },
        layout: { padding: { top: 4, right: 10, bottom: 2, left: 2 } },
        animation: { duration: 450, easing: 'easeOutQuart' },
        plugins: {
            // Cards that do not want a legend pass display:false themselves;
            // doughnuts keep a readable legend by default.
            legend: { display: true, position: 'bottom' },
            tooltip: { enabled: true },
        },
    }, extra);
}

function destroy(target) {
    if (!target) return;

    charts.forEach((chart) => {
        if (chart.canvas && chart.canvas.parentElement === target) {
            charts.delete(chart);
            chart.destroy();
        }
    });
}

function mount(target, config) {
    if (!target) return null;

    destroy(target);
    target.innerHTML = '';

    const canvas = document.createElement('canvas');
    const label = target.dataset.chartLabel || config.ariaLabel || 'Chart';
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', label);
    target.appendChild(canvas);

    return new TrackedChart(canvas, config);
}

function empty(target, { message = 'No data yet', hint = '', icon = 'fa-chart-simple' } = {}) {
    if (!target) return;

    destroy(target);
    target.innerHTML = `
        <div class="chart-empty">
            <span class="chart-empty-icon"><i class="fa-solid ${icon}" aria-hidden="true"></i></span>
            <strong>${message}</strong>
            ${hint ? `<span>${hint}</span>` : ''}
        </div>`;
}

function fmt(value, digits = 0) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return '0';
    return numeric.toLocaleString(undefined, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });
}

function pct(value, digits = 0) {
    return `${fmt(value, digits)}%`;
}

function palette() {
    return theme().light ? LIGHT_PALETTE : PALETTE;
}

function alpha(hex, opacity) {
    const value = String(hex).replace('#', '');
    const expanded = value.length === 3
        ? value.split('').map((char) => char + char).join('')
        : value;

    if (!/^[0-9a-f]{6}$/i.test(expanded)) {
        return hex;
    }

    const channels = [0, 2, 4].map((offset) => parseInt(expanded.slice(offset, offset + 2), 16));

    return `rgba(${channels[0]}, ${channels[1]}, ${channels[2]}, ${opacity})`;
}

function series() {
    return theme().light ? LIGHT_SERIES : SERIES;
}

// A doughnut with a number in the middle answers "how many?" without a
// legend lookup, and stops the ring from reading as an empty decoration.
const centerTotal = {
    id: 'lmsCenterTotal',
    afterDraw(chart) {
        const type = chart.config.type;
        if (type !== 'doughnut' && type !== 'pie') return;

        const values = chart.data.datasets.flatMap((dataset) => dataset.data || []);
        const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
        if (!total) return;

        const area = chart.chartArea;
        if (!area) return;

        const t = theme();
        const x = (area.left + area.right) / 2;
        const y = (area.top + area.bottom) / 2;
        const ctx = chart.ctx;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = t.text;
        ctx.font = `800 24px ${FONT_STACK}`;
        ctx.fillText(fmt(total), x, y - 4);
        ctx.fillStyle = t.muted;
        ctx.font = `700 9px ${FONT_STACK}`;
        ctx.fillText('TOTAL', x, y + 17);
        ctx.restore();
    },
};

NativeChart.register(centerTotal);

window.Chart = TrackedChart;
window.LMSCharts = {
    Chart: TrackedChart,
    PALETTE,
    LIGHT_PALETTE,
    SERIES,
    LIGHT_SERIES,
    theme,
    baseOptions,
    mount,
    destroy,
    empty,
    fmt,
    pct,
    palette,
    alpha,
    series,
    repaint,
    applyTheme,
};

if (document.body) {
    new MutationObserver(() => {
        applyTheme();
        repaint();
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
}
