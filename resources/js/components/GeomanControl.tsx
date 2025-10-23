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

// Type definitions for Geoman events
interface GeomanEvent {
    layer: unknown;
}

// Type for map with Geoman plugin
type GeomanMap = L.Map & {
    pm?: {
        addControls: (options: Record<string, unknown>) => void;
    };
};

export function GeomanControl({ onPolygonCreate, onPolygonEdit, existingPolygon }: GeomanControlProps) {
    const map = useMap() as GeomanMap;
    const polygonLayerRef = useRef<L.Polygon | null>(null);
    const isInitializedRef = useRef(false);

    // Initialize controls and event handlers once
    useEffect(() => {
        if (!('pm' in map) || isInitializedRef.current) return;

        isInitializedRef.current = true;

        // Add Geoman controls
        map.pm?.addControls({
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
        const handleCreate = (e: GeomanEvent) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon) {
                // Remove previous polygon if exists
                if (polygonLayerRef.current) {
                    map.removeLayer(polygonLayerRef.current);
                }
                polygonLayerRef.current = layer;

                const coords = layer.getLatLngs() as L.LatLng[][];
                const coordinates = coords.map((ring) =>
                    ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                );
                onPolygonCreate?.(coordinates);
            }
        };

        // Handle polygon edit
        const handleEdit = (e: GeomanEvent) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon) {
                // Update the reference if it's the current polygon
                if (layer === polygonLayerRef.current) {
                    const coords = layer.getLatLngs() as L.LatLng[][];
                    const coordinates = coords.map((ring) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                }
            }
        };

        // Handle polygon removal
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const handleRemove = (e: any) => {
            const layer = e.layer;
            if (layer === polygonLayerRef.current) {
                polygonLayerRef.current = null;
                onPolygonEdit?.([]);
            }
        };

        // Handle polygon update (when edit is finalized)
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const handleUpdate = (e: any) => {
            const layer = e.layer;
            if (layer instanceof L.Polygon && layer === polygonLayerRef.current) {
                const coords = layer.getLatLngs() as L.LatLng[][];
                const coordinates = coords.map((ring) =>
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
            if ('pm' in map) {
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                (map as any).pm.removeControls();
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
            if ('pm' in polygon) {
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                (polygon as any).pm.enable({
                    allowSelfIntersection: false,
                });

                // Disable draw mode when polygon exists
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                (map as any).pm.disableDraw();

                // Listen to edit events on the polygon layer directly
                polygon.on('pm:edit', () => {
                    const coords = polygon.getLatLngs() as L.LatLng[][];
                    const coordinates = coords.map((ring) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });

                polygon.on('pm:vertexadded', () => {
                    const coords = polygon.getLatLngs() as L.LatLng[][];
                    const coordinates = coords.map((ring) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });

                polygon.on('pm:vertexremoved', () => {
                    const coords = polygon.getLatLngs() as L.LatLng[][];
                    const coordinates = coords.map((ring) =>
                        ring.map(latlng => [latlng.lat, latlng.lng] as [number, number])
                    );
                    onPolygonEdit?.(coordinates);
                });

                polygon.on('pm:markerdragend', () => {
                    const coords = polygon.getLatLngs() as L.LatLng[][];
                    const coordinates = coords.map((ring) =>
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
