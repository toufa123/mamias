import * as echarts from 'echarts/core'
import { EffectScatterChart, ScatterChart } from 'echarts/charts'
import { GridComponent, TooltipComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'

/**
 * ECharts bubbles over the Leaflet map of the public map page
 * (App\Livewire\NisAreaMap, countries layer): one bubble per country of
 * first record, sized by species, the picked one rippling.
 *
 * Leaflet has no ECharts layer, so the chart is a transparent canvas laid on
 * the map: each anchor is converted to a container pixel with the map's own
 * projection (EPSG:4326 here) and the chart redrawn on every move. The canvas
 * lets the mouse through, so the map still drags; clicks and hovers are
 * hit-tested here instead and reported back to Livewire ("country-picked").
 *
 * The server sends the bubbles in a data attribute on first render and in a
 * "nis-bubbles" browser event on every render after.
 */
echarts.use([ScatterChart, EffectScatterChart, GridComponent, TooltipComponent, CanvasRenderer])

const TEAL = '#2f8a83'
const DARK = '#134f4c'
const OUTLINE = '#0b1f26'
const MEDITERRANEAN = [[30, -6], [46, 36.5]]

/** Diameter in pixels: area grows with the count, so the size with its square root. */
const sizeOf = (value, max) => 14 + 46 * Math.sqrt(value / Math.max(1, max))

function attach(root) {
    let bubbles = JSON.parse(root.dataset.nisBubbles || '[]')
    let map = null
    let chart = null
    let overlay = null
    let wasShowing = false

    const leaflet = () => {
        const el = root.querySelector('[x-data^="leafletMapWidget"]')
        const core = el && window.Alpine?.$data(el)?.mapCore
        return core?.map ? window.Alpine.raw(core.map) : null
    }

    // Pixel position and size of every bubble in the map's current view.
    const placed = () => {
        const max = Math.max(1, ...bubbles.map((b) => b.value))
        return bubbles.map((b) => {
            const p = map.latLngToContainerPoint([b.lat, b.lng])
            return { ...b, x: p.x, y: p.y, size: sizeOf(b.value, max) }
        })
    }

    const draw = () => {
        if (!chart) return
        const { x: width, y: height } = map.getSize()
        const points = placed()
        const datum = (b) => ({ name: b.label, value: [b.x, b.y, b.value], symbolSize: b.size })

        chart.setOption({
            animation: false,
            grid: { left: 0, right: 0, top: 0, bottom: 0 },
            xAxis: { type: 'value', min: 0, max: width, show: false },
            yAxis: { type: 'value', min: 0, max: height, inverse: true, show: false },
            tooltip: {
                trigger: 'item',
                formatter: (item) => `${item.name}: ${item.value[2]} species`,
            },
            series: [
                {
                    type: 'scatter',
                    data: points.filter((b) => !b.selected).map(datum),
                    itemStyle: { color: TEAL, opacity: 0.78, borderColor: '#ffffff', borderWidth: 1 },
                    label: { show: true, formatter: (item) => (item.data.symbolSize >= 28 ? item.value[2] : ''), color: '#ffffff', fontSize: 11, fontWeight: 600 },
                    z: 2,
                },
                {
                    type: 'effectScatter',
                    data: points.filter((b) => b.selected).map(datum),
                    rippleEffect: { scale: 2.2, brushType: 'stroke', period: 3 },
                    itemStyle: { color: DARK, opacity: 0.9, borderColor: OUTLINE, borderWidth: 2 },
                    label: { show: true, formatter: (item) => item.value[2], color: '#ffffff', fontSize: 12, fontWeight: 700 },
                    z: 3,
                },
            ],
        }, { replaceMerge: ['series'] })
    }

    // The bubble under a container point, smallest first so a small one on a big one stays reachable.
    const hit = (point) =>
        placed()
            .sort((a, b) => a.size - b.size)
            .find((b) => Math.hypot(b.x - point.x, b.y - point.y) <= b.size / 2)

    const showing = () => {
        overlay.style.display = bubbles.length ? '' : 'none'
        // Fit every bubble once, when the layer opens, should an anchor lie outside the basin view.
        if (bubbles.length && !wasShowing) {
            const bounds = window.L.latLngBounds(MEDITERRANEAN)
            bubbles.forEach((b) => bounds.extend([b.lat, b.lng]))
            map.fitBounds(bounds, { padding: [24, 24], animate: false })
        }
        wasShowing = bubbles.length > 0
        draw()
    }

    const start = () => {
        map = leaflet()
        if (!map) return requestAnimationFrame(start)

        overlay = document.createElement('div')
        overlay.style.cssText = 'position:absolute;inset:0;z-index:450;pointer-events:none'
        map.getContainer().appendChild(overlay)
        chart = echarts.init(overlay, null, { renderer: 'canvas' })

        map.on('move zoomend viewreset', draw)
        map.on('zoomstart', () => (overlay.style.opacity = '0'))
        map.on('zoomend', () => (overlay.style.opacity = '1'))
        map.on('resize', () => {
            chart.resize()
            draw()
        })
        map.on('mousemove', (e) => {
            const bubble = bubbles.length ? hit(e.containerPoint) : null
            map.getContainer().style.cursor = bubble ? 'pointer' : ''
            const all = placed()
            if (bubble) {
                const seriesIndex = bubble.selected ? 1 : 0
                const dataIndex = all.filter((b) => b.selected === bubble.selected).findIndex((b) => b.name === bubble.name)
                chart.dispatchAction({ type: 'showTip', seriesIndex, dataIndex })
            } else {
                chart.dispatchAction({ type: 'hideTip' })
            }
        })
        map.on('mouseout', () => {
            map.getContainer().style.cursor = ''
            chart.dispatchAction({ type: 'hideTip' })
        })
        map.on('click', (e) => {
            const bubble = bubbles.length ? hit(e.containerPoint) : null
            if (bubble) window.Livewire.dispatch('country-picked', { name: bubble.name })
        })

        showing()
    }

    window.addEventListener('nis-bubbles', (event) => {
        bubbles = event.detail.bubbles ?? []
        if (map) showing()
    })

    start()
}

const boot = () => document.querySelectorAll('[data-nis-bubbles]').forEach((root) => {
    if (root.dataset.nisBubblesReady) return
    root.dataset.nisBubblesReady = '1'
    attach(root)
})

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot()
