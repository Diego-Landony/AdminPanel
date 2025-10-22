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
        // @ts-expect-error - Geoman extends Leaflet map
        if (!map.pm || isInitializedRef.current) return;

        isInitializedRef.current = true;

        // Add Geoman controls
        // @ts-expect-error - Geoman types not available
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
        // @ts-expect-error - Event type not defined
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
        // @ts-expect-error - Event type not defined
        const handleEdit = (e) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon) {
                // Update the reference if it's the current polygon
                if (layer === polygonLayerRef.current) {
                    const coords = layer.getLatLngs();
                    const coordinates = coords.map((ring: L.LatLng[]) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                }
            }
        };

        // Handle polygon removal
        // @ts-expect-error - Event type not defined
        const handleRemove = (e) => {
            const layer = e.layer;
            if (layer === polygonLayerRef.current) {
                polygonLayerRef.current = null;
                onPolygonEdit?.([]);
            }
        };

        // Handle polygon update (when edit is finalized)
        // @ts-expect-error - Event type not defined
        const handleUpdate = (e) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon && layer === polygonLayerRef.current) {
                const coords = layer.getLatLngs();
                const coordinates = coords.map((ring: L.LatLng[]) =>
                    ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                );
                onPolygonEdit?.(coordinates);
            }
        };

        map.on('pm:create', handleCreate);
        map.on('pm:edit', handleEdit);
        map.on('pm:update', handleUpdate);
        map.on('pm:remove', handleRemove);

        // Cleanup
        return () => {
            // @ts-expect-error - Geoman types not available
            if (map.pm) {
                // @ts-expect-error - Geoman types not available
                map.pm.removeControls();
            }
            map.off('pm:create', handleCreate);
            map.off('pm:edit', handleEdit);
            map.off('pm:update', handleUpdate);
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
                pmIgnore: false,
            });
            polygon.addTo(map);
            polygonLayerRef.current = polygon;

            // Enable edit mode for the existing polygon
            // @ts-expect-error - Geoman types not available
            if (polygon.pm) {
                // @ts-expect-error - Geoman types not available
                polygon.pm.enable({
                    allowSelfIntersection: false,
                });

                // Disable draw mode when polygon exists
                // @ts-expect-error - Geoman types not available
                map.pm.disableDraw();

                // Listen to edit events on the polygon layer directly
                // @ts-expect-error - Event type not defined
                polygon.on('pm:edit', (e) => {
                    const coords = polygon.getLatLngs();
                    const coordinates = coords.map((ring: L.LatLng[]) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });

                // @ts-expect-error - Event type not defined
                polygon.on('pm:vertexadded', (e) => {
                    const coords = polygon.getLatLngs();
                    const coordinates = coords.map((ring: L.LatLng[]) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });

                // @ts-expect-error - Event type not defined
                polygon.on('pm:vertexremoved', (e) => {
                    const coords = polygon.getLatLngs();
                    const coordinates = coords.map((ring: L.LatLng[]) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });

                // @ts-expect-error - Event type not defined
                polygon.on('pm:markerdragend', (e) => {
                    const coords = polygon.getLatLngs();
                    const coordinates = coords.map((ring: L.LatLng[]) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });
            }

            map.fitBounds(polygon.getBounds());
        }
    }, [existingPolygon, map, onPolygonEdit]);

    return null;
}
