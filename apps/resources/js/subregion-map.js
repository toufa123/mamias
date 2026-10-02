import jsVectorMap from 'jsvectormap'

/**
 * NIS per EcAp sub-region on jsvectormap's world_merc map.
 *
 * jsvectormap has no API for adding regions to a shipped map, so addMap is
 * wrapped while world-merc.js registers itself: the sub-region polygons are
 * projected into the same pixel space and put *first* in `paths`, so the
 * country shapes render on top and hide where the hand-drawn outlines
 * overlap land.
 *
 * ponytail: outlines are hand-simplified (lng, lat); swap in the EEA MSFD
 * sub-region polygons if the map needs to be exact.
 */
export const SUBREGIONS = {
    WMED: [[-5.6,36.0],[-4.4,36.7],[-2.0,36.7],[-0.7,37.6],[0.2,38.8],[-0.3,39.5],[0.9,41.0],[3.2,41.9],[3.1,43.1],[4.8,43.4],[6.2,43.1],[7.5,43.8],[8.8,44.4],[10.2,43.9],[10.5,42.9],[11.2,42.4],[12.4,41.6],[13.7,41.2],[14.3,40.8],[15.0,40.2],[15.7,39.9],[16.0,38.9],[15.6,38.2],[15.2,38.2],[13.3,38.2],[12.4,37.8],[11.0,37.1],[10.3,37.2],[9.8,37.3],[8.6,36.9],[6.4,37.1],[3.0,36.8],[1.0,36.5],[-1.2,35.3],[-2.9,35.3],[-4.4,35.2],[-5.3,35.9]],
    ADRIA: [[18.5,40.1],[18.0,40.6],[17.0,41.1],[16.0,41.5],[16.1,41.9],[14.7,42.1],[13.9,42.9],[13.6,43.5],[12.6,44.1],[12.3,44.9],[12.4,45.4],[13.1,45.7],[13.7,45.6],[13.6,45.1],[14.3,45.3],[14.9,44.9],[15.2,44.2],[16.4,43.5],[17.5,43.0],[18.5,42.4],[19.4,41.8],[19.5,41.1],[19.4,40.4]],
    CMED: [[12.4,37.8],[11.0,37.1],[10.8,36.4],[10.6,35.8],[11.1,35.2],[10.1,34.3],[10.9,33.7],[11.8,33.1],[13.2,32.9],[15.3,32.3],[15.8,31.3],[18.2,30.4],[20.0,32.1],[21.5,32.9],[22.5,32.8],[23.2,32.2],[23.5,35.3],[22.5,36.4],[21.7,36.8],[21.3,37.6],[21.1,38.3],[20.7,38.8],[20.2,39.4],[19.4,40.4],[18.5,40.1],[18.3,39.8],[17.2,40.4],[16.5,39.7],[17.1,39.0],[16.5,38.4],[15.7,37.9],[15.6,38.2],[15.2,37.5],[15.1,36.7],[14.4,36.8],[12.9,37.3]],
    EMED: [[22.5,36.4],[23.0,36.5],[23.2,37.4],[24.0,37.7],[23.4,38.3],[22.9,39.2],[22.6,40.3],[23.3,39.9],[24.3,40.8],[25.8,40.8],[26.2,40.1],[26.6,39.3],[26.8,38.5],[27.2,37.4],[28.0,36.8],[29.6,36.2],[30.6,36.8],[32.5,36.1],[34.6,36.8],[36.0,36.9],[35.9,35.5],[35.6,34.3],[35.1,33.1],[34.5,31.6],[33.0,31.1],[31.5,31.5],[30.0,31.4],[29.0,30.9],[27.3,31.3],[25.2,31.6],[24.0,32.0],[23.2,32.2],[23.5,35.3]],
}

const RADIUS = 6381372 // jsvectormap's Proj.radius

export const SEA = '#edf3f5'

/** Fewest to most NIS, the DESIGN-SYSTEM.md teal ramp. */
export const SCALE = ['#cdeef2', '#056273']

let registered = null

/** Same Mercator maths as jsvectormap's coordsToPoint, into the map's own pixel space. */
function project(world, [lng, lat]) {
    const { width, height, bbox: [from, to] } = world.insets[0]
    const x = RADIUS * (lng - world.projection.centralMeridian) * (Math.PI / 180)
    const y = -RADIUS * Math.log(Math.tan(Math.PI / 4 + (lat * Math.PI) / 360))

    return [((x - from.x) / (to.x - from.x)) * width, ((y - from.y) / (to.y - from.y)) * height]
}

export function register() {
    registered ??= (async () => {
        const addMap = jsVectorMap.addMap

        jsVectorMap.addMap = (name, world) => {
            const seas = Object.fromEntries(Object.entries(SUBREGIONS).map(([code, ring]) => [
                code,
                { name: code, path: 'M' + ring.map((point) => project(world, point).map((n) => n.toFixed(2)).join(',')).join('L') + 'Z' },
            ]))

            addMap('mediterranean_merc', { ...world, paths: { ...seas, ...world.paths } })
        }

        try {
            await import('jsvectormap/dist/maps/world-merc.js')
        } finally {
            jsVectorMap.addMap = addMap
        }
    })()

    return registered
}

/**
 * Exported for the public dashboard entry, which imports it. The admin widget
 * loads this file as its own Vite entry, and Vite drops an entry's exports,
 * so that path uses the window.renderSubregionMap assignment below instead.
 *
 * @param {HTMLElement} el
 * @param {Record<string, number>} values  sub-region code => NIS count
 * @param {Record<string, string>} labels  sub-region code => display name
 */
export async function renderSubregionMap(el, values, labels) {
    await register()

    return new jsVectorMap({
        selector: el,
        map: 'mediterranean_merc',
        backgroundColor: SEA,
        zoomOnScroll: false,
        focusOn: { regions: Object.keys(SUBREGIONS), animate: false },
        regionStyle: {
            initial: { fill: '#d8e3e8', stroke: '#ffffff', strokeWidth: 0.4 },
            hover: { fillOpacity: 0.85, cursor: 'default' },
        },
        visualizeData: { scale: SCALE, values },
        onRegionTooltipShow(event, tooltip, code) {
            if (!(code in values)) {
                return event.preventDefault()
            }

            tooltip.text(`<b>${labels[code]}</b><br>${values[code]} reported NIS`, true)
        },
    })
}

window.renderSubregionMap = renderSubregionMap

/**
 * jsvectormap has no export, but it draws plain SVG with inline attributes:
 * rasterise it at 2x over the map's sea colour, then hand it to
 * mamiasExportPng (resources/js/app.js) for the title and colour-scale legend
 * the chart exports get.
 *
 * @param {HTMLElement} el  the element renderSubregionMap drew into
 * @param {Record<string, number>} values  sub-region code => NIS count
 * @param {string} title
 */
window.downloadSubregionMap = async function download(el, values, title) {
    const svg = el.querySelector('svg')
    const { width, height } = svg.getBoundingClientRect()
    const clone = svg.cloneNode(true)
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg')
    clone.setAttribute('width', width)
    clone.setAttribute('height', height)

    const image = new Image()
    image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(clone))
    await image.decode()

    const canvas = Object.assign(document.createElement('canvas'), { width: width * 2, height: height * 2 })
    const context = canvas.getContext('2d')
    context.scale(2, 2)
    context.fillStyle = SEA
    context.fillRect(0, 0, width, height)
    context.drawImage(image, 0, 0, width, height)

    const counts = Object.values(values)

    await window.mamiasExportPng({
        src: canvas.toDataURL('image/png'),
        title,
        scale: { from: SCALE[0], to: SCALE[1], min: Math.min(...counts), max: Math.max(...counts), label: 'reported NIS per sub-region' },
        file: title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
    })
}
