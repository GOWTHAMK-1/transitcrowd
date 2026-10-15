/**
 * Enhanced Leaflet OpenStreetMap Interactive Route and Stop Visualizer
 */

function initRouteMap(elementId, stops) {
    const mapElement = document.getElementById(elementId);
    if (!mapElement || typeof L === 'undefined' || !stops || stops.length === 0) {
        return null;
    }

    // Filter stops that have valid lat/lng
    const validStops = stops.filter(s => s.latitude && s.longitude);
    if (validStops.length === 0) {
        mapElement.innerHTML = '<div style="padding: 3rem; text-align: center; color: #64748b; font-size: 0.95rem;">No geographical coordinates registered for this route.</div>';
        return null;
    }

    // Center map around first stop
    const center = [validStops[0].latitude, validStops[0].longitude];
    const map = L.map(elementId, {
        scrollWheelZoom: false, // Prevents accidental scroll hijacking
    }).setView(center, 13);

    // OpenStreetMap tile layer with clear attribution
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(map);

    const latLngs = [];

    validStops.forEach((stop, index) => {
        const point = [stop.latitude, stop.longitude];
        latLngs.push(point);

        // Custom styled DivIcon marker
        const customIcon = L.divIcon({
            className: 'custom-transit-pin',
            html: `
                <div style="
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    background: #1e40af;
                    border: 3px solid #ffffff;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
                    color: #ffffff;
                    font-weight: 800;
                    font-size: 11px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: -apple-system, sans-serif;
                ">
                    ${index + 1}
                </div>
            `,
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15],
        });

        const marker = L.marker(point, { icon: customIcon }).addTo(map);
        const popupContent = `
            <div style="font-family: -apple-system, sans-serif; font-size: 0.9rem; padding: 2px;">
                <div style="font-weight: 800; color: #0f172a; font-size: 1rem; margin-bottom: 2px;">
                    ${index + 1}. ${stop.stop_name}
                </div>
                <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 6px;">
                    Mode: <strong style="text-transform: uppercase;">${stop.transport_mode}</strong> • Zone: ${stop.zone || 'Standard'}
                </div>
                <a href="/stops/${stop._id}" style="
                    display: inline-block;
                    background: #2563eb;
                    color: #ffffff;
                    text-decoration: none;
                    padding: 4px 10px;
                    border-radius: 4px;
                    font-size: 0.78rem;
                    font-weight: 600;
                ">Station Details & Predictions →</a>
            </div>
        `;
        marker.bindPopup(popupContent);
    });

    // Draw route polyline if 2 or more stops exist
    if (latLngs.length > 1) {
        const polyline = L.polyline(latLngs, {
            color: '#2563eb',
            weight: 5,
            opacity: 0.85,
            dashArray: '8, 10',
            lineJoin: 'round',
        }).addTo(map);

        map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
    }

    return map;
}
