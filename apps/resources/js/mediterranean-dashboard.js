import * as echarts from "echarts/core";
import { BarChart, CustomChart, HeatmapChart, LineChart, PieChart, TreemapChart } from "echarts/charts";
import {
    GridComponent,
    LegendComponent,
    TimelineComponent,
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";
// Registers the wordCloud series on the same ECharts core (echarts-wordcloud 2.1.0, run on ECharts 6 via an npm override).
import "echarts-wordcloud";
import jsVectorMap from "jsvectormap";
import "jsvectormap/dist/jsvectormap.css";
import { register as registerMediterranean, renderSubregionMap, SEA, SUBREGIONS } from "./subregion-map.js";

/**
 * Charts for the MAMIAS chart Layup widget (App\Layup\Widgets\MamiasChartWidget).
 * Each widget renders a [data-mamias-chart] element next to a JSON payload;
 * this entry draws it the first time it scrolls into view, so the entry
 * animation is actually seen. Colours follow DESIGN-SYSTEM.md, as on the
 * panel dashboard.
 */
echarts.use([
    BarChart,
    CustomChart,
    HeatmapChart,
    LineChart,
    PieChart,
    TreemapChart,
    GridComponent,
    LegendComponent,
    TimelineComponent,
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
    CanvasRenderer,
]);

const TEAL_500 = "#078da0";
const TEAL_600 = "#056273";
/** The running-total line: orange, so it never reads as part of the teal bars. */
const CUMULATIVE = "#e8590c";
const GRAY_100 = "#edf3f5";
const GRAY_200 = "#d8e3e8";
const GRAY_500 = "#5f7783";
const GRAY_600 = "#47606b";
const INK = "#0e2630";

/** Status stack => colour; statuses sharing a colour share a stack. */
const STATUS_COLOURS = {
    Invasive: "#b42318",
    Established: "#b45309",
    "Casual / vagrant": "#00558c",
    "Unknown / other": GRAY_500,
};

/**
 * One colour per sub-region, shared by the spread charts. None repeats a
 * status colour: the decade chart sits beside the establishment chart.
 */
const SUBREGION_COLOURS = { WMED: "#1971c2", CMED: TEAL_500, ADRIA: "#7c3aed", EMED: "#c2255c" };

/** Kingdom => bar colour in the taxonomic composition chart. */
const KINGDOM_COLOURS = {
    Animalia: TEAL_600,
    Plantae: "#2f9e44",
    Chromista: "#b45309",
    Bacteria: "#7c3aed",
    Fungi: "#9c36b5",
    Protozoa: "#00558c",
};

/** CBD pathway categories 1–6, in category order. */
const PATHWAY_COLOURS = ["#2f9e44", "#e8590c", "#1971c2", "#7c3aed", TEAL_600, "#c2255c"];

/** Phyla shown by name; the rest are folded into one "Other phyla" bar. */
const TOP_PHYLA = 12;

/**
 * Keep the first `top` rows of a phylum × column grid (MediterraneanDashboard
 * lists phyla largest first) and sum the rest into one "Other phyla" row.
 */
function foldPhyla({ rows, values }, top) {
    if (rows.length <= top) {
        return { rows, values };
    }

    const other = values.slice(top).reduce(
        (sum, row) => sum.map((cell, i) => cell + row[i]),
        values[0].map(() => 0),
    );

    return {
        rows: [...rows.slice(0, top), `Other phyla (${rows.length - top})`],
        values: [...values.slice(0, top), other],
    };
}

/** A phylum × column heatmap, largest phylum at the top, teal ramp from 0. */
function phylumHeatmap(grid, xLabelRotate = 0) {
    const { rows, values } = foldPhyla(grid, TOP_PHYLA);
    const y = [...rows].reverse();
    const data = values.flatMap((row, r) => row.map((value, x) => [x, rows.length - 1 - r, value]));

    return {
        tooltip: {
            trigger: "item",
            backgroundColor: "#ffffff",
            borderColor: GRAY_200,
            textStyle: { color: INK, fontSize: 13 },
            formatter: ({ value: [x, yi, count] }) => `${y[yi]} · ${grid.columns[x]}<br><b>${count}</b> reported NIS`,
        },
        grid: { left: 8, right: 8, top: 8, bottom: 56, containLabel: true },
        xAxis: categoryAxis(grid.columns, {
            splitArea: { show: false },
            axisLabel: {
                color: GRAY_600,
                fontSize: 11,
                interval: 0,
                rotate: xLabelRotate,
                width: 110,
                overflow: "break",
            },
        }),
        yAxis: categoryAxis(y),
        visualMap: {
            min: 0,
            max: Math.max(1, ...data.map((cell) => cell[2])),
            calculable: false,
            orient: "horizontal",
            left: "center",
            bottom: 0,
            itemHeight: 160,
            itemWidth: 10,
            text: ["more", "fewer"],
            textStyle: { color: GRAY_500, fontSize: 11 },
            inRange: { color: ["#f1f8f9", "#8fd6e0", TEAL_600] },
        },
        series: [
            {
                type: "heatmap",
                data,
                // White halo keeps the count readable on the darkest cells too.
                label: {
                    show: true,
                    fontSize: 10,
                    formatter: ({ value }) => (value[2] > 0 ? value[2] : ""),
                    color: INK,
                    textBorderColor: "#ffffff",
                    textBorderWidth: 2,
                },
                itemStyle: { borderColor: "#ffffff", borderWidth: 1 },
                emphasis: { itemStyle: { borderColor: INK, borderWidth: 1 } },
            },
        ],
    };
}

/** Figures after Zenetos et al. 2023 (Diversity 15:962): the whole basin, then each sub-region. */
const SCOPE_COLOURS = { Mediterranean: INK, ...SUBREGION_COLOURS };
// Anything else is the country a by-country chart is scoped to.
const scopeColour = (name) => SCOPE_COLOURS[name] ?? CUMULATIVE;

/** The paper's pathway codes, coloured as the CBD categories elsewhere on the page. */
const PATHWAY_CODE_COLOURS = {
    REL: "#2f9e44",
    EC: "#e8590c",
    TS: "#1971c2",
    TC: "#7c3aed",
    COR: TEAL_600,
    UNA: "#c2255c",
    UNK: GRAY_500,
};

/** Where each sub-region's circle sits on the spread map, [lat, lng]. */
const CENTROIDS = { WMED: [39.5, 4.5], CMED: [35.2, 17.5], ADRIA: [43.2, 15.2], EMED: [34.2, 29.5] };

const tooltip = (trigger = "axis") => ({
    trigger,
    axisPointer: { type: "shadow", shadowStyle: { color: "rgba(7, 141, 160, 0.08)" } },
    backgroundColor: "#ffffff",
    borderColor: GRAY_200,
    textStyle: { color: INK, fontSize: 13 },
});

const valueAxis = (extra = {}) => ({
    type: "value",
    minInterval: 1,
    axisLabel: { color: GRAY_500, fontSize: 11 },
    splitLine: { lineStyle: { color: GRAY_100 } },
    ...extra,
});

const categoryAxis = (data, extra = {}) => ({
    type: "category",
    data,
    axisLine: { lineStyle: { color: GRAY_200 } },
    axisTick: { show: false },
    axisLabel: { color: GRAY_600, fontSize: 11 },
    ...extra,
});

const legend = (extra = {}) => ({
    icon: "rect",
    itemWidth: 12,
    itemHeight: 12,
    textStyle: { color: GRAY_600 },
    ...extra,
});

/** ECharts option builders, keyed like MamiasChartWidget::CHARTS. */
const OPTIONS = {
    trend: ({ labels, counts, cumulative }) => ({
        tooltip: tooltip(),
        legend: legend({
            top: 0,
            data: [{ name: "New reported NIS" }, { name: "Cumulative", icon: "path://M0,5 L24,5 L24,8 L0,8 Z" }],
        }),
        grid: { left: 8, right: 8, top: 56, bottom: 8, containLabel: true },
        xAxis: categoryAxis(labels, { axisLabel: { color: GRAY_600, fontSize: 11, rotate: 45 } }),
        yAxis: [
            valueAxis({ name: "New reported NIS", nameTextStyle: { color: TEAL_600, align: "left" } }),
            valueAxis({
                name: "Cumulative",
                nameTextStyle: { color: CUMULATIVE, align: "right" },
                axisLabel: { color: CUMULATIVE, fontSize: 11 },
                splitLine: { show: false },
            }),
        ],
        series: [
            {
                name: "New reported NIS",
                type: "bar",
                data: counts,
                barWidth: "60%",
                itemStyle: { color: TEAL_600 },
                animationDelay: (index) => index * 50,
            },
            {
                name: "Cumulative",
                type: "line",
                yAxisIndex: 1,
                data: cumulative,
                symbol: "none",
                lineStyle: { color: CUMULATIVE, width: 2.5 },
                itemStyle: { color: CUMULATIVE },
                animationDuration: 1200 + counts.length * 50,
                animationEasing: "linear",
            },
        ],
    }),

    "subregion-status": ({ subregions, stacks }) => ({
        tooltip: tooltip(),
        legend: legend({ bottom: 0 }),
        grid: { left: 8, right: 8, top: 16, bottom: 40, containLabel: true },
        xAxis: categoryAxis(subregions),
        yAxis: valueAxis(),
        series: Object.entries(stacks).map(([name, data]) => ({
            name,
            type: "bar",
            stack: "status",
            data,
            barWidth: "55%",
            itemStyle: { color: STATUS_COLOURS[name], borderColor: "#ffffff", borderWidth: 1 },
        })),
    }),

    /** First Mediterranean records per country, largest at the top, the selected country highlighted. */
    "country-ranking": ({ rows, selected }) => {
        const ascending = rows.filter((row) => row.value > 0 || row.country === selected).reverse();

        return {
            tooltip: tooltip(),
            grid: { left: 8, right: 40, top: 8, bottom: 8, containLabel: true },
            xAxis: valueAxis(),
            yAxis: categoryAxis(
                ascending.map((row) => row.country),
                { axisLabel: { color: GRAY_600, interval: 0 } },
            ),
            series: [
                {
                    name: "First Mediterranean records",
                    type: "bar",
                    data: ascending.map((row) => ({
                        value: row.value,
                        itemStyle: { color: row.country === selected ? CUMULATIVE : TEAL_600 },
                    })),
                    barWidth: "60%",
                    label: { show: true, position: "right", color: GRAY_600, fontSize: 11 },
                },
            ],
        };
    },

    pathways: ({ rows }) => {
        // Largest at the top of a horizontal chart, so the category axis runs ascending.
        const ascending = [...rows].reverse();

        return {
            tooltip: tooltip(),
            grid: { left: 8, right: 40, top: 8, bottom: 8, containLabel: true },
            xAxis: valueAxis(),
            yAxis: categoryAxis(ascending.map((row) => row.label)),
            series: [
                {
                    name: "Reported NIS",
                    type: "bar",
                    data: ascending.map((row) => row.value),
                    barWidth: "60%",
                    itemStyle: { color: TEAL_600 },
                    label: { show: true, position: "right", color: TEAL_600, fontSize: 11 },
                },
            ],
        };
    },

    /**
     * Phyla ranked by reported NIS, each bar coloured by its kingdom, with the
     * count and share of all NIS on the bar. One series per kingdom, stacked
     * on a shared axis, so the legend doubles as the kingdom key.
     */
    taxonomy: ({ tree }) => {
        const total = tree.reduce((sum, kingdom) => sum + kingdom.value, 0) || 1;
        const phyla = tree
            .flatMap((kingdom) => kingdom.children.map((phylum) => ({ ...phylum, kingdom: kingdom.name })))
            .sort((a, b) => b.value - a.value);
        const rest = phyla.slice(TOP_PHYLA);
        const rows = [
            ...phyla.slice(0, TOP_PHYLA),
            ...(rest.length
                ? [
                      {
                          name: `Other phyla (${rest.length})`,
                          value: rest.reduce((sum, phylum) => sum + phylum.value, 0),
                          kingdom: "Other",
                      },
                  ]
                : []),
        ].reverse();
        const share = (value) => `${value} · ${((value / total) * 100).toFixed(value / total < 0.01 ? 1 : 0)}%`;

        return {
            tooltip: {
                ...tooltip(),
                formatter: (params) =>
                    params
                        .filter((p) => p.value != null)
                        .map((p) => `${p.name} (${p.seriesName})<br><b>${share(p.value)}</b> of reported NIS`)
                        .join(""),
            },
            legend: legend({ top: 0 }),
            grid: { left: 8, right: 72, top: 36, bottom: 8, containLabel: true },
            xAxis: valueAxis(),
            yAxis: categoryAxis(rows.map((row) => row.name)),
            // Only kingdoms with a bar of their own, so the legend has no empty keys.
            series: [...new Set([...rows].reverse().map((row) => row.kingdom))].map((kingdom) => ({
                name: kingdom,
                type: "bar",
                stack: "phylum",
                barWidth: "65%",
                data: rows.map((row) => (row.kingdom === kingdom ? row.value : null)),
                itemStyle: { color: KINGDOM_COLOURS[kingdom] ?? GRAY_500 },
                label: {
                    show: true,
                    position: "right",
                    color: GRAY_600,
                    fontSize: 11,
                    formatter: ({ value }) => share(value),
                },
                animationDelay: (index) => (rows.length - index) * 40,
            })),
        };
    },

    /**
     * Establishment status within each phylum: one horizontal bar per phylum
     * (largest at the top, the tail folded into "Other phyla"), stacked by
     * status in the same colours as the sub-region status chart. A small
     * Nightingale (rose) chart of reported NIS per kingdom sits in the empty
     * lower right, where the small phyla's bars stay short. roseType 'area'
     * gives every kingdom the same angle and encodes the count in the radius,
     * so Chromista and Bacteria stay visible beside Animalia's hundreds.
     */
    "taxon-status": ({ phyla, stacks, kingdoms }) => {
        const tail = phyla.length > TOP_PHYLA;
        // Counts past TOP_PHYLA sum into one "Other phyla" bar; reversed so the largest sits at the top.
        const fold = (values) =>
            [
                ...values.slice(0, TOP_PHYLA),
                ...(tail ? [values.slice(TOP_PHYLA).reduce((sum, value) => sum + value, 0)] : []),
            ].reverse();
        const names = [
            ...phyla.slice(0, TOP_PHYLA),
            ...(tail ? [`Other phyla (${phyla.length - TOP_PHYLA})`] : []),
        ].reverse();

        return {
            // Item trigger so the rose's petals get a tooltip too.
            tooltip: {
                ...tooltip("item"),
                formatter: ({ seriesName, seriesType, name, value, data }) =>
                    seriesType === "pie"
                        ? `<b>${name}</b><br>${data.count} reported NIS (${((data.count / kingdoms.reduce((sum, kingdom) => sum + kingdom.value, 0)) * 100).toFixed(1)}%)`
                        : `${name} · ${seriesName}<br><b>${value}</b> reported NIS`,
            },
            // Statuses only: the kingdoms are labelled on the rose itself.
            legend: legend({ bottom: 0, data: Object.keys(stacks) }),
            title: {
                text: "Reported NIS by kingdom",
                left: "80%",
                top: "36%",
                textAlign: "center",
                textStyle: { color: GRAY_600, fontSize: 12, fontWeight: 600 },
            },
            grid: { left: 8, right: 16, top: 8, bottom: 40, containLabel: true },
            xAxis: valueAxis(),
            yAxis: categoryAxis(names),
            series: [
                ...Object.entries(stacks).map(([name, values]) => ({
                    name,
                    type: "bar",
                    stack: "status",
                    barWidth: "65%",
                    data: fold(values),
                    itemStyle: { color: STATUS_COLOURS[name], borderColor: "#ffffff", borderWidth: 1 },
                })),
                {
                    name: "Reported NIS by kingdom",
                    type: "pie",
                    roseType: "area",
                    center: ["80%", "66%"],
                    radius: ["5%", "26%"],
                    // Petal radius from √count, so petal *area* follows the count and
                    // Plantae/Chromista stay readable next to Animalia; labels show the real count.
                    data: kingdoms.map((kingdom) => ({
                        name: kingdom.name,
                        value: Math.sqrt(kingdom.value),
                        count: kingdom.value,
                        itemStyle: { color: KINGDOM_COLOURS[kingdom.name] ?? GRAY_500 },
                    })),
                    itemStyle: { borderColor: "#ffffff", borderWidth: 1, borderRadius: 3 },
                    label: { color: GRAY_600, fontSize: 11, formatter: ({ name, data }) => `${name}\n${data.count}` },
                    labelLine: { length: 4, length2: 6 },
                    animationType: "scale",
                    animationEasing: "elasticOut",
                },
            ],
        };
    },

    /** Which groups arrive by which vector: phylum rows × CBD pathway columns. */
    "phylum-pathways": (grid) => phylumHeatmap(grid),

    /** Where each group is reported: phylum rows × EcAp sub-region columns. */
    "phylum-subregions": (grid) => phylumHeatmap(grid),

    /**
     * Kingdom › phylum › class › family as a treemap you drill into: two
     * levels show at once, a click zooms in, the breadcrumb climbs back out.
     * Each kingdom keeps its colour, lighter for the levels below it.
     */
    "taxonomy-treemap": ({ tree }) => ({
        tooltip: {
            trigger: "item",
            backgroundColor: "#ffffff",
            borderColor: GRAY_200,
            textStyle: { color: INK, fontSize: 13 },
            formatter: ({ treePathInfo, value }) =>
                `${treePathInfo
                    .slice(1)
                    .map((node) => node.name)
                    .join(" › ")}<br><b>${value}</b> reported NIS`,
        },
        series: [
            {
                type: "treemap",
                name: "All reported NIS",
                data: tree.map((kingdom) => ({
                    ...kingdom,
                    itemStyle: { color: KINGDOM_COLOURS[kingdom.name] ?? GRAY_500 },
                })),
                top: 36,
                bottom: 8,
                left: 0,
                right: 0,
                leafDepth: 2,
                roam: false,
                nodeClick: "zoomToNode",
                breadcrumb: {
                    top: 0,
                    left: 0,
                    itemStyle: { color: GRAY_100, borderColor: GRAY_200, textStyle: { color: INK } },
                },
                label: { fontSize: 11, formatter: ({ name, value }) => `${name}\n${value}` },
                upperLabel: { show: true, height: 20, color: "#ffffff", fontWeight: 600 },
                // colorAlpha, not colorSaturation: the kingdom hue stays recognisable
                // (saturation shifts turned Animalia's teal into cyan); lower levels only lighten.
                levels: [
                    // levels[0] is the root, which the breadcrumb already names.
                    { itemStyle: { borderColor: "#ffffff", borderWidth: 3, gapWidth: 3 }, upperLabel: { show: false } },
                    // Kingdom header strips take the (white) border colour, so their label is dark.
                    {
                        itemStyle: { borderColor: "#ffffff", borderWidth: 2, gapWidth: 1 },
                        colorAlpha: [0.6, 1],
                        upperLabel: { show: true, color: INK, fontWeight: 600 },
                    },
                    { itemStyle: { borderColor: "#ffffff", borderWidth: 1, gapWidth: 1 }, colorAlpha: [0.55, 0.95] },
                    { itemStyle: { borderColor: "#ffffff", borderWidth: 1, gapWidth: 1 }, colorAlpha: [0.5, 0.9] },
                ],
            },
        ],
    }),

    /** Fig. 2: pathway shares (%) for the basin and each sub-region, one 100% bar each. */
    "pathway-shares": ({ codes, labels, rows }) => {
        const top = [...rows].reverse();

        return {
            tooltip: {
                ...tooltip(),
                formatter: (params) =>
                    `<b>${top[params[0].dataIndex].name}</b> (${top[params[0].dataIndex].total} reported NIS)<br>${params
                        .filter((p) => p.value > 0)
                        .map((p) => `${p.marker}${labels[p.seriesName]}: <b>${p.value}%</b>`)
                        .join("<br>")}`,
            },
            legend: legend({ bottom: 0, formatter: (code) => `${code} · ${labels[code]}` }),
            grid: { left: 8, right: 16, top: 8, bottom: 64, containLabel: true },
            xAxis: valueAxis({ max: 100, axisLabel: { color: GRAY_500, fontSize: 11, formatter: "{value}%" } }),
            yAxis: categoryAxis(top.map((row) => `${row.name} (${row.total})`)),
            series: codes.map((code) => ({
                name: code,
                type: "bar",
                stack: "share",
                barWidth: "60%",
                data: top.map((row) => row.shares[code]),
                itemStyle: { color: PATHWAY_CODE_COLOURS[code], borderColor: "#ffffff", borderWidth: 1 },
                label: {
                    show: true,
                    color: "#ffffff",
                    fontSize: 10,
                    formatter: ({ value }) => (value >= 5 ? `${Math.round(value)}%` : ""),
                },
            })),
        };
    },

    /**
     * Fig. 3a: mean new reported NIS per year in each 10-year cycle, with
     * standard-error bars drawn by a custom series that shares its line's name,
     * so one legend click hides both.
     */
    "introduction-rate": ({ cycles, series }) => ({
        tooltip: {
            ...tooltip(),
            formatter: (params) =>
                `<b>${cycles[params[0].dataIndex]}</b><br>${params
                    .filter((p) => p.seriesType === "line")
                    .map(
                        (p) =>
                            `${p.marker}${p.seriesName}: <b>${p.value}</b> ± ${series[p.seriesName].se[p.dataIndex]} per year`,
                    )
                    .join("<br>")}`,
        },
        legend: legend({ bottom: 0 }),
        grid: { left: 8, right: 16, top: 36, bottom: 40, containLabel: true },
        xAxis: categoryAxis(cycles),
        yAxis: valueAxis({
            name: "New reported NIS per year",
            nameTextStyle: { color: GRAY_500, align: "left" },
            minInterval: 0,
        }),
        series: Object.entries(series).flatMap(([name, { mean, se }]) => [
            {
                name,
                type: "line",
                data: mean,
                symbolSize: 7,
                lineStyle: { color: scopeColour(name), width: name === "Mediterranean" ? 3 : 2 },
                itemStyle: { color: scopeColour(name) },
            },
            {
                name,
                type: "custom",
                data: mean.map((value, i) => [i, value - se[i], value + se[i]]),
                z: 1,
                renderItem: (params, api) => {
                    const [x, low] = api.coord([api.value(0), api.value(1)]);
                    const [, high] = api.coord([api.value(0), api.value(2)]);
                    const style = { stroke: scopeColour(name), lineWidth: 1.5, opacity: 0.8 };

                    return {
                        type: "group",
                        children: [
                            { type: "line", shape: { x1: x, y1: low, x2: x, y2: high }, style },
                            { type: "line", shape: { x1: x - 4, y1: low, x2: x + 4, y2: low }, style },
                            { type: "line", shape: { x1: x - 4, y1: high, x2: x + 4, y2: high }, style },
                        ],
                    };
                },
            },
        ]),
    }),

    /** Fig. 3b: new reported NIS per year, basin-wide and per sub-region. */
    "yearly-rate": ({ years, series }) => ({
        tooltip: tooltip(),
        legend: legend({ bottom: 0 }),
        grid: { left: 8, right: 16, top: 36, bottom: 40, containLabel: true },
        xAxis: categoryAxis(years, { boundaryGap: false }),
        yAxis: valueAxis({ name: "New reported NIS", nameTextStyle: { color: GRAY_500, align: "left" } }),
        series: Object.entries(series).map(([name, data]) => ({
            name,
            type: "line",
            data,
            symbol: "circle",
            symbolSize: 4,
            lineStyle: { color: scopeColour(name), width: name === "Mediterranean" ? 3 : 1.5 },
            itemStyle: { color: scopeColour(name) },
            emphasis: { focus: "series" },
        })),
    }),

    /**
     * Fig. 4 as an UpSet-style bar chart (ECharts has no Venn diagram): reported
     * NIS per exact combination of sub-regions, cumulative by decade. The
     * timeline plays the decades; one sub-region is its own colour, a shared
     * combination darkens with the number of sub-regions in it.
     */
    "shared-subregions": ({ steps, combinations, counts }) => {
        const shades = ["", null, "#8fd6e0", TEAL_500, TEAL_600];
        const colour = (combination) => {
            const parts = combination.split("+");

            return parts.length === 1 ? SUBREGION_COLOURS[parts[0]] : shades[parts.length];
        };

        return {
            baseOption: {
                timeline: {
                    axisType: "category",
                    data: steps.map((step) => `by ${step}`),
                    autoPlay: true,
                    loop: false,
                    playInterval: 1400,
                    currentIndex: 0,
                    bottom: 0,
                    left: 48,
                    right: 48,
                    label: { color: GRAY_600, fontSize: 11 },
                    lineStyle: { color: GRAY_200 },
                    itemStyle: { color: GRAY_200 },
                    checkpointStyle: { color: TEAL_600, borderColor: "#ffffff" },
                    controlStyle: { color: TEAL_600, borderColor: TEAL_600 },
                    progress: { lineStyle: { color: TEAL_500 }, itemStyle: { color: TEAL_500 } },
                },
                tooltip: {
                    ...tooltip(),
                    formatter: ([p]) =>
                        `<b>${combinations[p.dataIndex].replaceAll("+", " + ")}</b>${combinations[p.dataIndex].includes("+") ? "" : " only"}<br>${p.value} reported NIS`,
                },
                grid: { left: 8, right: 8, top: 36, bottom: 80, containLabel: true },
                xAxis: categoryAxis(
                    combinations.map((c) => (c.includes("+") ? c.replaceAll("+", "\n") : `${c}\nonly`)),
                    { axisLabel: { color: GRAY_600, fontSize: 9, interval: 0, lineHeight: 11 } },
                ),
                yAxis: valueAxis({ name: "Reported NIS", nameTextStyle: { color: GRAY_500, align: "left" } }),
                series: [{ type: "bar", barWidth: "60%" }],
            },
            options: steps.map((step) => {
                const values = counts[step];
                const total = values.reduce((sum, value) => sum + value, 0) || 1;

                return {
                    series: [
                        {
                            data: values.map((value, i) => ({ value, itemStyle: { color: colour(combinations[i]) } })),
                            label: {
                                show: true,
                                position: "top",
                                color: GRAY_600,
                                fontSize: 10,
                                formatter: ({ value }) =>
                                    value > 0 ? `${value}\n${Math.round((value / total) * 100)}%` : "",
                            },
                        },
                    ],
                };
            }),
        };
    },

    /** Table 2: reported NIS per broad taxa group, basin-wide and per sub-region; colour = share of the column. */
    "groups-subregions": ({ groups, columns, counts, totals }) => {
        const codes = Object.keys(groups);
        const y = [...codes].reverse().map((code) => groups[code]);
        const data = codes.flatMap((code, r) =>
            counts[code].map((count, x) => [
                x,
                codes.length - 1 - r,
                totals[x] ? Math.round((count / totals[x]) * 1000) / 10 : 0,
                count,
            ]),
        );

        return {
            tooltip: {
                trigger: "item",
                backgroundColor: "#ffffff",
                borderColor: GRAY_200,
                textStyle: { color: INK, fontSize: 13 },
                formatter: ({ value: [x, yi, share, count] }) =>
                    `${y[yi]} · ${columns[x]}<br><b>${count}</b> reported NIS (${share}% of ${columns[x]})`,
            },
            grid: { left: 8, right: 8, top: 8, bottom: 56, containLabel: true },
            xAxis: categoryAxis(
                columns.map((column, i) => `${column}\n(${totals[i]})`),
                { axisLabel: { color: GRAY_600, fontSize: 11, interval: 0 } },
            ),
            yAxis: categoryAxis(y),
            visualMap: {
                dimension: 2,
                min: 0,
                max: Math.max(1, ...data.map((cell) => cell[2])),
                calculable: false,
                orient: "horizontal",
                left: "center",
                bottom: 0,
                itemHeight: 160,
                itemWidth: 10,
                text: ["higher share", "lower"],
                textStyle: { color: GRAY_500, fontSize: 11 },
                inRange: { color: ["#f1f8f9", "#8fd6e0", TEAL_600] },
            },
            series: [
                {
                    type: "heatmap",
                    data,
                    label: {
                        show: true,
                        fontSize: 10,
                        color: INK,
                        textBorderColor: "#ffffff",
                        textBorderWidth: 2,
                        formatter: ({ value }) => `${value[3]} · ${value[2]}%`,
                    },
                    itemStyle: { borderColor: "#ffffff", borderWidth: 1 },
                },
            ],
        };
    },

    /** Reported NIS per sub-region, stacked by CBD pathway category. */
    "subregion-pathways": ({ subregions, series }) => ({
        tooltip: tooltip(),
        legend: legend({ bottom: 0 }),
        grid: { left: 8, right: 8, top: 36, bottom: 64, containLabel: true },
        xAxis: categoryAxis(subregions),
        yAxis: valueAxis({ name: "Reported NIS", nameTextStyle: { color: GRAY_500, align: "left" } }),
        series: Object.entries(series).map(([name, data], index) => ({
            name,
            type: "bar",
            stack: "pathway",
            data,
            barWidth: "55%",
            itemStyle: { color: PATHWAY_COLOURS[index] ?? GRAY_500, borderColor: "#ffffff", borderWidth: 1 },
        })),
    }),

    "spread-bars": ({ labels, series, names }) => ({
        tooltip: tooltip(),
        legend: legend({ bottom: 0 }),
        grid: { left: 8, right: 8, top: 32, bottom: 40, containLabel: true },
        xAxis: categoryAxis(labels, { axisLabel: { color: GRAY_600, fontSize: 11, rotate: 45 } }),
        yAxis: valueAxis({ name: "New reported NIS", nameTextStyle: { color: GRAY_500, align: "left" } }),
        series: Object.entries(series).map(([code, data]) => ({
            name: names[code],
            type: "bar",
            stack: "spread",
            data,
            itemStyle: { color: SUBREGION_COLOURS[code] },
            animationDelay: (index) => index * 40,
        })),
    }),
};

/** Chart element => its ECharts instance, for the PNG download. The maps are SVG and need none. */
const CHARTS = new WeakMap();
/** Every drawn ECharts instance and jsVectorMap map, to fit them to paper when printing. */
const DRAWN = [];

function renderEchart(el, kind, payload) {
    const chart = echarts.init(el, null, { renderer: "canvas" });
    CHARTS.set(el, chart);
    DRAWN.push(chart);
    const defaults = { animationDuration: 1200, animationEasing: "cubicOut", textStyle: { fontFamily: "inherit" } };
    const option = OPTIONS[kind](payload);
    // A timeline option keeps its shared settings in baseOption; root keys beside it are not read.
    chart.setOption(
        option.baseOption
            ? { ...option, baseOption: { ...defaults, ...option.baseOption } }
            : { ...defaults, ...option },
    );
    // Window resize rather than a ResizeObserver: an observer fires once on
    // observe(), and ECharts' resize() then cuts the entry animation short.
    window.addEventListener("resize", () => chart.resize());
}

/**
 * NIS recorded in each sub-region up to a chosen decade, as circles on the
 * Mediterranean map (area ∝ count) with the count written in each circle.
 * The slider scrubs the decades; Play runs them in order, and runs once on
 * its own the first time the map is seen.
 *
 * Zoom and drag are off: the counts are SVG text placed on each circle's
 * cx/cy, and a fixed viewport means those only move when the window resizes.
 */
async function renderSpreadMap(el, { labels, series, names }) {
    await registerMediterranean();

    const codes = Object.keys(SUBREGIONS);
    const cumulative = Object.fromEntries(
        codes.map((code) => {
            let total = 0;

            return [code, series[code].map((count) => (total += count))];
        }),
    );
    const largest = Math.max(1, ...codes.map((code) => cumulative[code].at(-1)));
    const maxRadius = () => Math.max(24, Math.min(el.clientWidth, el.clientHeight) * 0.13);
    const radius = (value) => (value > 0 ? 8 + (maxRadius() - 8) * Math.sqrt(value / largest) : 0);

    const section = el.closest("section");
    const slider = section.querySelector("[data-spread-slider]");
    const label = section.querySelector("[data-spread-label]");
    const play = section.querySelector("[data-spread-play]");
    let step = labels.length - 1;

    const map = new jsVectorMap({
        selector: el,
        map: "mediterranean_merc",
        backgroundColor: SEA,
        draggable: false,
        zoomButtons: false,
        zoomOnScroll: false,
        focusOn: { regions: codes, animate: false },
        regionStyle: {
            initial: { fill: GRAY_200, stroke: "#ffffff", strokeWidth: 0.4 },
            hover: { fillOpacity: 1, cursor: "default" },
        },
        series: {
            regions: [
                {
                    attribute: "fill",
                    scale: { sea: "#dbeef1" },
                    values: Object.fromEntries(codes.map((code) => [code, "sea"])),
                },
            ],
        },
        markers: codes.map((code) => ({
            name: names[code],
            coords: CENTROIDS[code],
            style: {
                initial: {
                    fill: SUBREGION_COLOURS[code],
                    fillOpacity: 0.85,
                    stroke: "#ffffff",
                    strokeWidth: 1.5,
                    r: 0,
                },
            },
        })),
        onRegionTooltipShow: (event) => event.preventDefault(),
        onMarkerTooltipShow(event, tooltipEl, index) {
            const code = codes[index];
            tooltipEl.text(
                `<b>${names[code]}</b><br>${cumulative[code][step]} reported NIS by the ${labels[step]}`,
                true,
            );
        },
    });

    const circles = codes.map((code, index) => el.querySelector(`circle.jvm-marker[data-index="${index}"]`));
    circles.forEach((circle) => {
        circle.style.transition = "r 0.45s ease-out";
    });

    // A count fits inside a circle from this radius; below it, it sits just above in ink.
    const INSIDE = 15;
    const counts = circles.map((circle) => {
        const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        text.setAttribute("text-anchor", "middle");
        text.setAttribute("dominant-baseline", "central");
        text.style.cssText = "font-family: inherit; font-size: 13px; font-weight: 700; pointer-events: none;";
        circle.parentNode.appendChild(text);

        return text;
    });

    const place = () =>
        codes.forEach((code, i) => {
            const r = radius(cumulative[code][step]);
            const inside = r >= INSIDE;
            counts[i].setAttribute("x", circles[i].getAttribute("cx"));
            // The attribute, not style.y: Chrome ignores the CSS y property on <text>.
            counts[i].setAttribute("y", Number(circles[i].getAttribute("cy")) - (inside ? 0 : r + 10));
            counts[i].style.fill = inside ? "#ffffff" : INK;
            counts[i].textContent = cumulative[code][step] > 0 ? cumulative[code][step].toLocaleString() : "";
        });

    const show = (index) => {
        step = index;
        slider.value = index;
        label.textContent = labels[index];
        codes.forEach((code, i) => {
            circles[i].style.r = `${radius(cumulative[code][index])}px`;
        });
        place();
    };

    // jsvectormap moves the markers itself on resize; follow them once it has.
    window.addEventListener("resize", () => setTimeout(() => show(step), 50));

    let timer = null;
    const stop = () => {
        clearInterval(timer);
        timer = null;
        play.textContent = "Play";
    };
    const start = () => {
        if (step >= labels.length - 1) {
            show(0);
        }
        play.textContent = "Pause";
        timer = setInterval(() => (step < labels.length - 1 ? show(step + 1) : stop()), 450);
    };

    slider.min = 0;
    slider.max = labels.length - 1;
    slider.addEventListener("input", () => {
        stop();
        show(Number(slider.value));
    });
    play.addEventListener("click", () => (timer ? stop() : start()));

    show(0);
    start();

    return map;
}

/**
 * Words sized by reported NIS, each linking to the data explorer listing them
 * (word.url). No rotation, so every word reads left to right; the exact values
 * are in the tooltip.
 */
function renderWordCloud(el, { words }, colourOf) {
    const chart = echarts.init(el, null, { renderer: "canvas" });
    CHARTS.set(el, chart);
    // Not in DRAWN: a word cloud lays out over several ticks, too late for the printed sheet; it prints at screen size, centred.

    // The layout draws on a raw canvas, where "inherit" is no font at all and every word falls back to 10px.
    const fontFamily = getComputedStyle(el).fontFamily;
    chart.setOption({
        textStyle: { fontFamily: "inherit" },
        tooltip: {
            formatter: (item) => `${echarts.format.encodeHTML(item.data.full)}<br><b>${item.value}</b> reported NIS`,
        },
        series: [
            {
                type: "wordCloud",
                shape: "circle",
                left: "center",
                top: "center",
                width: "96%",
                height: "96%",
                sizeRange: words.length > 20 ? [12, 40] : [13, 60],
                rotationRange: [0, 0],
                gridSize: 8,
                drawOutOfBound: false,
                // A long word at the largest size can be wider than the cloud; shrink it rather than drop it.
                shrinkToFit: true,
                textStyle: { fontFamily, fontWeight: 600, color: (item) => colourOf(item.data) },
                emphasis: { textStyle: { textShadowBlur: 6, textShadowColor: "rgba(14, 38, 48, 0.3)" } },
                data: words,
            },
        ],
    });
    chart.on("click", (item) => {
        if (item.data?.url) window.location.href = item.data.url;
    });
    window.addEventListener("resize", () => chart.resize());
}

const RENDERERS = {
    "subregion-map": (el, { values, labels }) => renderSubregionMap(el, values, labels),
    // A pathway takes its CBD category's colour (its code's first digit), a family its kingdom's.
    "pathway-cloud": (el, payload) =>
        renderWordCloud(el, payload, (word) => PATHWAY_COLOURS[word.group - 1] ?? GRAY_500),
    "family-cloud": (el, payload) => renderWordCloud(el, payload, (word) => KINGDOM_COLOURS[word.group] ?? GRAY_500),
    "spread-map": renderSpreadMap,
};

/** Charts not drawn yet: they draw when scrolled into view, or all at once when printing starts. */
const PENDING = new Set();

function draw(el) {
    observer.unobserve(el);
    PENDING.delete(el);
    const kind = el.dataset.mamiasChart;
    const payload = JSON.parse(el.closest("section").querySelector("[data-mamias-payload]").textContent);

    return Promise.resolve((RENDERERS[kind] ?? ((target, data) => renderEchart(target, kind, data)))(el, payload)).then(
        (map) => {
            if (map?.updateSize) DRAWN.push(map);
            const button = el.closest("section").querySelector("[data-chart-download]");
            if (button) button.disabled = false;
        },
    );
}

const observer = new IntersectionObserver(
    (entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) draw(entry.target);
        }
    },
    { threshold: 0.25 },
);

document.querySelectorAll("[data-mamias-chart]").forEach((el) => {
    PENDING.add(el);
    observer.observe(el);
});

/**
 * Printing (app.css, @media print): draw what has not been drawn, then fit
 * every chart to the paper while the page is laid out for print, and back
 * to the screen afterwards. Resized without animation, so the sheet catches
 * the final frame.
 */
function fitDrawn() {
    for (const item of DRAWN) {
        if (item.resize) item.resize({ animation: { duration: 0 } });
        else {
            // jsVectorMap keeps its zoom on resize: frame the basin again at the new size.
            item.updateSize();
            if (item.params.focusOn) item.setFocus(item.params.focusOn);
        }
    }
}

// The maps wait for their shapes: load them now, so a print started before
// the map was scrolled to still draws it before the sheet is laid out.
if (document.querySelector('[data-mamias-chart="spread-map"], [data-mamias-chart="subregion-map"]'))
    registerMediterranean();

window.addEventListener("beforeprint", () => [...PENDING].forEach(draw));
window.matchMedia("print").addEventListener("change", fitDrawn);
window.addEventListener("afterprint", fitDrawn);

/**
 * The chart as an image, at twice its on-screen size: an ECharts chart from
 * its canvas, a jsVectorMap map by drawing its SVG.
 */
async function chartImage(el) {
    const width = el.clientWidth;
    const height = el.clientHeight;
    const chart = CHARTS.get(el);
    if (chart) {
        return { src: chart.getDataURL({ type: "png", pixelRatio: 2, backgroundColor: "#ffffff" }), width, height };
    }

    const original = el.querySelector("svg");
    const svg = original.cloneNode(true);
    // The map's look comes from stylesheets an image cannot see: copy each shape's computed style onto the copy.
    const sources = original.querySelectorAll("path, circle, text, line, rect, g");
    svg.querySelectorAll("path, circle, text, line, rect, g").forEach((node, index) => {
        const style = getComputedStyle(sources[index]);
        for (const property of [
            "fill",
            "fill-opacity",
            "stroke",
            "stroke-width",
            "stroke-opacity",
            "opacity",
            "font-family",
            "font-size",
            "font-weight",
            "paint-order",
            "vector-effect",
        ]) {
            node.style.setProperty(property, style.getPropertyValue(property));
        }
    });
    svg.setAttribute("width", width);
    svg.setAttribute("height", height);
    // A data: URL, not a blob: one: the site's CSP (img-src 'self' data: https:) refuses blob: images.
    const markup = new XMLSerializer().serializeToString(svg);

    return { src: `data:image/svg+xml;charset=utf-8,${encodeURIComponent(markup)}`, width, height };
}

/** Text split into lines no wider than maxWidth, for the canvas. */
function wrapText(ctx, text, maxWidth) {
    const lines = [];
    let line = "";
    for (const word of text.split(" ")) {
        const candidate = line ? `${line} ${word}` : word;
        if (line && ctx.measureText(candidate).width > maxWidth) {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    }

    return [...lines, line];
}

/**
 * A PNG of one chart card, ready for a report: its title, the chart on white,
 * and where the figures come from, dated.
 */
async function downloadChart(section) {
    const el = section.querySelector("[data-mamias-chart]");
    const title = section.querySelector("h2")?.textContent.trim() || "MAMIAS chart";
    const { src, width, height } = await chartImage(el);
    const image = new Image();
    await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = reject;
        image.src = src;
    });

    const scale = 2;
    const pad = 24;
    const font = getComputedStyle(section).fontFamily;
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");
    ctx.font = `600 18px ${font}`;
    const titleLines = wrapText(ctx, title, width);
    const head = pad + titleLines.length * 24 + 8;
    const foot = 36;

    canvas.width = (width + pad * 2) * scale;
    canvas.height = (head + height + foot) * scale;
    ctx.scale(scale, scale);
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, width + pad * 2, head + height + foot);

    ctx.fillStyle = INK;
    ctx.font = `600 18px ${font}`;
    titleLines.forEach((line, index) => ctx.fillText(line, pad, pad + 18 + index * 24));

    ctx.drawImage(image, pad, head, width, height);

    ctx.fillStyle = GRAY_500;
    ctx.font = `12px ${font}`;
    // The public address, whatever host the chart was exported from; the date it was read.
    const accessed = new Date().toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" });
    ctx.fillText(`Source: MAMIAS (SPA/RAC), www.mamias.org. Accessed on: ${accessed}`, pad, head + height + 24);

    canvas.toBlob((blob) => {
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `${
            title
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-|-$/g, "") || "mamias-chart"
        }.png`;
        link.click();
        setTimeout(() => URL.revokeObjectURL(link.href), 1000);
    }, "image/png");
}

document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-chart-download]");
    if (button && !button.disabled) downloadChart(button.closest("section"));
});
