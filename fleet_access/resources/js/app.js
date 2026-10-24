

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { addBaseLayers } from './map-layers';

window.Alpine = Alpine;
window.Chart = Chart;
window.L = L;
window.addBaseLayers = addBaseLayers;

Alpine.start();

