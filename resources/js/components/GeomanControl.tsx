import { useEffect, useRef } from 'react';
import { useMap } from 'react-leaflet';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import L from 'leaflet';

interface GeomanControlProps {
    onPolygonCreate?: (coordinates: [number, number][][]) => void;
    onPolygonEdit?: (coordinates: [number, number][][]) => void;
    existingPolygon?: [number, number][];
}

export function GeomanControl({ onPolygonCreate, onPolygonEdit, existingPolygon }: GeomanControlProps) {
    const map = useMap();
    const polygonLayerRef = useRef<L.Polygon | null>(null);
    const isInitializedRef = useRef(false);

    // Initialize controls and event handlers once
    useEffect(() => {
        // @ts-ignore - Geoman extends Leaflet map
        if (!map.pm || isInitializedRef.current) return;

        isInitializedRef.current = true;

        // Add Geoman controls
        // @ts-ignore
        map.pm.addControls({
            position: 'topleft',
            drawMarker: false,
            drawCircleMarker: false,
            drawPolyline: false,
            drawRectangle: false,
            drawCircle: false,
            drawPolygon: true,
            editMode: true,
            dragMode: false,
            cutPolygon: false,
            removalMode: true,
        });

        // Handle polygon creation
        // @ts-ignore
        const handleCreate = (e) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon) {
                // Remove previous polygon if exists
                if (polygonLayerRef.current) {
                    map.removeLayer(polygonLayerRef.current);
                }
                polygonLayerRef.current = layer;

                const coords = layer.getLatLngs();
                const coordinates = coords.map((ring: L.LatLng[]) =>
                    ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                );
                onPolygonCreate?.(coordinates);
            }
        };

        // Handle polygon edit
        // @ts-ignore
        const handleEdit = (e) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon) {
                const coords = layer.getLatLngs();
                const coordinates = coords.map((ring: L.LatLng[]) =>
                    ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                );
                onPolygonEdit?.(coordinates);
            }
        };

        // Handle polygon removal
        // @ts-ignore
        const handleRemove = (e) => {
            const layer = e.layer;
            if (layer === polygonLayerRef.current) {
                polygonLayerRef.current = null;
            }
        };

        map.on('pm:create', handleCreate);
        map.on('pm:edit', handleEdit);
        map.on('pm:remove', handleRemove);

        // Cleanup
        return () => {
            // @ts-ignore
            if (map.pm) {
                // @ts-ignore
                map.pm.removeControls();
            }
            map.off('pm:create', handleCreate);
            map.off('pm:edit', handleEdit);
            map.off('pm:remove', handleRemove);

            // Remove polygon layer if exists
            if (polygonLayerRef.current) {
                map.removeLayer(polygonLayerRef.current);
                polygonLayerRef.current = null;
            }
        };
    }, [map, onPolygonCreate, onPolygonEdit]);

    // Load existing polygon separately (only once)
    useEffect(() => {
        if (existingPolygon && existingPolygon.length > 0 && !polygonLayerRef.current) {
            const polygon = L.polygon(existingPolygon, {
                color: '#3388ff',
                fillColor: '#3388ff',
                fillOpacity: 0.2,
            });
            polygon.addTo(map);
            polygonLayerRef.current = polygon;
            map.fitBounds(polygon.getBounds());
        }
    }, [existingPolygon, map]);

    return null;
}
