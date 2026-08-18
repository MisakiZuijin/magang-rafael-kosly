@extends('layouts.app')

@section('title', 'Peta Rute Terdekat')

@section('content')
<div class="space-y-4" x-data="{ 
    selectedOffice: '{{ $kantors->first()->id ?? '1' }}', 
    selectedKos: 'semua',
    distanceText: '',
    nearestKosName: '',
    googleMapsUrl: '#',
    setRouteInfo(name, distText, url) {
        this.nearestKosName = name;
        this.distanceText = distText;
        this.googleMapsUrl = url;
    }
}" id="admin-map-container">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Peta & Rute Terdekat</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Penentuan titik kantor (database) ke rute kos terdekat</p>
        </div>
        <span class="px-2.5 py-1 text-[10px] uppercase font-bold rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
            Admin Navigasi
        </span>
    </div>

    {{-- Panel Kontrol Lokasi Kantor & Kos Tujuan --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- 1. Lokasi Kantor Awal (Data dari Database) --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Titik Awal (Kantor Admin dari Database)
                </label>
                <select id="office-select" x-model="selectedOffice" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    @foreach($kantors as $kantor)
                    <option value="{{ $kantor->id }}" data-lat="{{ $kantor->latitude }}" data-lng="{{ $kantor->longitude }}" data-nama="{{ $kantor->nama }}">
                        🏢 {{ $kantor->nama }}
                    </option>
                    @endforeach
                    <option value="gps_live">📍 Gunakan GPS Perangkat Saya Saat Ini (Live Geolocation)</option>
                </select>
            </div>

            {{-- 2. Kos Tujuan --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Tujuan Properti Kos
                </label>
                <select id="kos-select" x-model="selectedKos" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="semua">🔍 Semua Kos (Cari Rute Kos Terdekat)</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" data-lat="{{ $loc->latitude ?? '-7.250445' }}" data-lng="{{ $loc->longitude ?? '112.768845' }}" data-nama="{{ $loc->nama }}">
                        🏡 {{ $loc->nama }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Banner Info Rute & Google Maps --}}
        <div class="p-3 grid grid-span-2 bg-emerald-50/60 dark:bg-emerald-950/30 rounded-xl border border-emerald-100 dark:border-emerald-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div class="space-y-0.5">
                <p class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    Tujuan Kos: <strong x-text="nearestKosName || 'Memuat...'" class="text-emerald-700 dark:text-emerald-400 font-bold ml-1"></strong>
                </p>
            </div>

            <a :href="googleMapsUrl" target="_blank"
                class="w-full sm:w-auto px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span>Rute Google Maps <span x-show="distanceText" x-text="'(' + distanceText + ')'" class="ml-1 font-mono text-[11px] bg-emerald-700/60 px-2 py-0.5 rounded-lg"></span></span>
            </a>
        </div>
    </div>

    {{-- Map Leaflet Container --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden p-1">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <div id="map-container" data-locations='@json($locations)' data-kantors='@json($kantors)' class="w-full h-[400px] rounded-xl z-10"></div>
    </div>

    {{-- List Data Kantor & Kos (Compact Scrollable Area) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- List Data Kantor --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <h2 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1">
                    <span>🏢</span>
                    <span>Data Kantor Admin ({{ $kantors->count() }})</span>
                </h2>
            </div>

            <div class="max-h-48 overflow-y-auto no-scrollbar space-y-2">
                @foreach($kantors as $kan)
                <div class="p-2.5 bg-gray-50/70 dark:bg-gray-800/40 rounded-xl border border-blue-100 dark:border-blue-900/40 flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs flex-shrink-0">
                        🏢
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $kan->nama }}</h3>
                        <p class="text-[10px] text-gray-500 truncate">{{ $kan->alamat ?? '-' }}</p>
                        <p class="text-[9px] text-gray-400 font-mono">Lat: {{ $kan->latitude }}, Lng: {{ $kan->longitude }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- List Titik Koordinat Kos --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <h2 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                    <span>🏡</span>
                    <span>Daftar Properti Kos ({{ $locations->count() }})</span>
                </h2>
            </div>

            <div class="max-h-48 overflow-y-auto no-scrollbar space-y-2">
                @forelse($locations as $loc)
                <div class="p-2.5 bg-gray-50/70 dark:bg-gray-800/40 rounded-xl border border-emerald-100 dark:border-emerald-900/40 flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-xs flex-shrink-0">
                        🏡
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $loc->nama }}</h3>
                        <p class="text-[10px] text-gray-500 truncate">{{ $loc->alamat ?? '-' }}</p>
                        <p class="text-[9px] text-gray-400 font-mono">Lat: {{ $loc->latitude ?? '-' }}, Lng: {{ $loc->longitude ?? '-' }}</p>
                    </div>
                </div>
                @empty
                <x-empty-state message="Belum ada data koordinat lokasi kos." />
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapContainer = document.getElementById('map-container');
        const locations = JSON.parse(mapContainer ? (mapContainer.dataset.locations || '[]') : '[]');
        const kantors = JSON.parse(mapContainer ? (mapContainer.dataset.kantors || '[]') : '[]');

        // Map kantor array to object dictionary
        const officeMap = {};
        kantors.forEach(k => {
            officeMap[k.id] = {
                id: k.id,
                name: k.nama,
                lat: parseFloat(k.latitude),
                lng: parseFloat(k.longitude)
            };
        });

        // Haversine Distance (in KM)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // Custom Leaflet Markers
        const officeIcon = L.divIcon({
            className: 'custom-office-pin',
            html: `<div style="background-color: #2563eb; color: white; width: 34px; height: 34px; borderRadius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3); font-size: 16px;">🏢</div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });

        const kosIcon = L.divIcon({
            className: 'custom-kos-pin',
            html: `<div style="background-color: #10b981; color: white; width: 32px; height: 32px; borderRadius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3); font-size: 14px;">🏡</div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        let defaultKantor = kantors.length > 0 ? officeMap[kantors[0].id] : {
            name: 'Kantor Pusat',
            lat: -7.250445,
            lng: 112.768845
        };

        let currentOfficeLat = defaultKantor.lat;
        let currentOfficeLng = defaultKantor.lng;
        let currentOfficeName = defaultKantor.name;

        const map = L.map('map-container').setView([currentOfficeLat, currentOfficeLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let officeMarker = L.marker([currentOfficeLat, currentOfficeLng], {
                icon: officeIcon
            }).addTo(map)
            .bindPopup(`<strong>${currentOfficeName} (Titik Awal)</strong>`);

        const kosMarkers = [];
        let activePolylines = [];

        // Render Kos Markers
        locations.forEach(loc => {
            if (loc.latitude && loc.longitude) {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                const marker = L.marker([lat, lng], {
                    icon: kosIcon
                }).addTo(map);

                marker.bindPopup(`
                    <div style="font-family: sans-serif;">
                        <strong style="color: #10b981;">${loc.nama}</strong><br>
                        <span style="font-size: 11px; color: #666;">${loc.alamat || ''}</span>
                    </div>
                `);

                kosMarkers.push({
                    id: loc.id,
                    nama: loc.nama,
                    lat,
                    lng,
                    marker
                });
            }
        });

        function updateRoute() {
            activePolylines.forEach(p => map.removeLayer(p));
            activePolylines = [];

            const officeSelect = document.getElementById('office-select');
            const kosSelect = document.getElementById('kos-select');
            const selectedOfficeKey = officeSelect ? officeSelect.value : (kantors[0]?.id || '1');
            const selectedKosKey = kosSelect ? kosSelect.value : 'semua';

            if (selectedOfficeKey === 'gps_live') {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(position => {
                        currentOfficeLat = position.coords.latitude;
                        currentOfficeLng = position.coords.longitude;
                        currentOfficeName = 'Lokasi GPS Perangkat Saya';
                        drawRouteLines(selectedKosKey);
                    }, () => {
                        alert('Gagal membaca posisi GPS perangkan. Menggunakan data kantor database.');
                        if (kantors.length > 0) {
                            currentOfficeLat = parseFloat(kantors[0].latitude);
                            currentOfficeLng = parseFloat(kantors[0].longitude);
                            currentOfficeName = kantors[0].nama;
                        }
                        drawRouteLines(selectedKosKey);
                    });
                }
            } else if (officeMap[selectedOfficeKey]) {
                currentOfficeLat = officeMap[selectedOfficeKey].lat;
                currentOfficeLng = officeMap[selectedOfficeKey].lng;
                currentOfficeName = officeMap[selectedOfficeKey].name;
                drawRouteLines(selectedKosKey);
            }
        }

        async function drawRouteLines(targetKosId) {
            officeMarker.setLatLng([currentOfficeLat, currentOfficeLng])
                .bindPopup(`<strong>${currentOfficeName} (Titik Awal)</strong>`);

            let nearestItem = null;
            let minDistance = Infinity;

            const validKos = kosMarkers.filter(k => targetKosId === 'semua' || k.id == targetKosId);

            validKos.forEach(k => {
                const dist = calculateDistance(currentOfficeLat, currentOfficeLng, k.lat, k.lng);
                if (dist < minDistance) {
                    minDistance = dist;
                    nearestItem = k;
                }
            });

            const alpineComponent = document.querySelector('[x-data]');

            if (nearestItem) {
                // Fetch actual road routing geometry from OSRM API
                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${currentOfficeLng},${currentOfficeLat};${nearestItem.lng},${nearestItem.lat}?overview=full&geometries=geojson`;

                try {
                    const response = await fetch(osrmUrl);
                    const data = await response.json();

                    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                        const route = data.routes[0];
                        // Convert [lng, lat] from OSRM to Leaflet [lat, lng]
                        const coordinates = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);

                        const polyline = L.polyline(coordinates, {
                            color: '#10b981',
                            weight: 6,
                            opacity: 0.9,
                            lineCap: 'round',
                            lineJoin: 'round'
                        }).addTo(map);

                        activePolylines.push(polyline);

                        const distanceKm = (route.distance / 1000).toFixed(2);
                        const durationMinutes = Math.round(route.duration / 60);
                        const gmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${currentOfficeLat},${currentOfficeLng}&destination=${nearestItem.lat},${nearestItem.lng}&travelmode=driving`;
                        const distStr = `${distanceKm} km · Est. ${durationMinutes} mnt`;

                        const el = document.getElementById('admin-map-container');
                        if (el && window.Alpine) {
                            const alpineData = Alpine.$data(el);
                            if (alpineData && typeof alpineData.setRouteInfo === 'function') {
                                alpineData.setRouteInfo(nearestItem.nama, distStr, gmapsUrl);
                            }
                        }

                        map.fitBounds(polyline.getBounds(), {
                            padding: [40, 40]
                        });
                        nearestItem.marker.openPopup();
                        return;
                    }
                } catch (e) {
                    console.warn('OSRM API routing fallback to straight line:', e);
                }

                // Fallback straight line if OSRM API is unreachable
                const polyline = L.polyline([
                    [currentOfficeLat, currentOfficeLng],
                    [nearestItem.lat, nearestItem.lng]
                ], {
                    color: '#10b981',
                    weight: 5,
                    opacity: 0.8,
                    dashArray: '10, 10',
                    lineCap: 'round'
                }).addTo(map);

                activePolylines.push(polyline);

                const gmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${currentOfficeLat},${currentOfficeLng}&destination=${nearestItem.lat},${nearestItem.lng}&travelmode=driving`;
                const distStr = minDistance.toFixed(2) + ' km';

                const el = document.getElementById('admin-map-container');
                if (el && window.Alpine) {
                    const alpineData = Alpine.$data(el);
                    if (alpineData && typeof alpineData.setRouteInfo === 'function') {
                        alpineData.setRouteInfo(nearestItem.nama, distStr, gmapsUrl);
                    }
                }

                const bounds = L.latLngBounds([
                    [currentOfficeLat, currentOfficeLng],
                    [nearestItem.lat, nearestItem.lng]
                ]);
                map.fitBounds(bounds, {
                    padding: [40, 40]
                });
                nearestItem.marker.openPopup();
            }
        }

        const officeSelect = document.getElementById('office-select');
        const kosSelect = document.getElementById('kos-select');

        if (officeSelect) officeSelect.addEventListener('change', updateRoute);
        if (kosSelect) kosSelect.addEventListener('change', updateRoute);

        updateRoute();
    });
</script>
@endpush
@endsection