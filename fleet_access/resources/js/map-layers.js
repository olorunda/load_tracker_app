import L from 'leaflet';

const STORAGE_KEY = 'fleet.baseLayer';
const MAX_ZOOM = 20;

/**
 * Shared base maps for every Leaflet map in the app.
 * Tiles past a provider's native zoom are upscaled, so close-up zoom still works.
 */
function createBaseLayers() {
    const esri = 'https://server.arcgisonline.com/ArcGIS/rest/services';

    return {
        Streets: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxNativeZoom: 19,
            maxZoom: MAX_ZOOM,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }),
        Satellite: L.layerGroup([
            L.tileLayer(`${esri}/World_Imagery/MapServer/tile/{z}/{y}/{x}`, {
                maxNativeZoom: 18,
                maxZoom: MAX_ZOOM,
                attribution: 'Imagery &copy; Esri, Maxar, Earthstar Geographics',
            }),
            L.tileLayer(`${esri}/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}`, {
                maxNativeZoom: 18,
                maxZoom: MAX_ZOOM,
            }),
            L.tileLayer(`${esri}/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}`, {
                maxNativeZoom: 18,
                maxZoom: MAX_ZOOM,
            }),
        ]),
        Light: L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png?key=cb1_4c3e_1_e8878c7b595a5fe459c526a1', {
            maxNativeZoom: 19,
            maxZoom: MAX_ZOOM,
            attribution: '&copy; OpenStreetMap &copy; CARTO',
        }),
    };
}

function readPreference() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function writePreference(name) {
    try {
        localStorage.setItem(STORAGE_KEY, name);
    } catch {
        // Storage can be unavailable (private mode); the choice just won't persist
    }
}

/**
 * Add the base map switcher to a map and restore the viewer's last choice.
 */
export function addBaseLayers(map, { position = 'topright' } = {}) {
    const layers = createBaseLayers();
    const saved = readPreference();
    const initial = layers[saved] ? saved : 'Streets';

    map.setMaxZoom(MAX_ZOOM);
    layers[initial].addTo(map);
    L.control.layers(layers, null, { position }).addTo(map);
    map.on('baselayerchange', (e) => writePreference(e.name));

    return layers;
}
