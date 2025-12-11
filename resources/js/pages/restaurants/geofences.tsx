import { Head, router } from '@inertiajs/react';
import { featureCollection, intersect, polygon } from '@turf/turf';
import L from 'leaflet';
import { Edit, Eye, MapPin, Save, X } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { MapContainer, Marker, Polygon, Popup, TileLayer, useMap } from 'react-leaflet';

import { GeomanControl } from '@/components/GeomanControl';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { coordinatesToKML } from '@/utils/kmlParser';

interface Overlap {
    coordinates: [number, number][];
    restaurants: [string, string];
}

// Fix for default markers in React Leaflet
delete (L.Icon.Default.prototype as unknown as { _getIconUrl: unknown })._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

interface Restaurant {
    id: number;
    name: string;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    coordinates: { lat: number; lng: number } | null;
    has_geofence: boolean;
    geofence_coordinates: Array<{ lat: number; lng: number }>;
}

interface GeofencesOverviewProps {
    restaurants: Restaurant[];
}

// Component to expose map instance
function MapController({ mapRef }: { mapRef: React.MutableRefObject<L.Map | null> }) {
    const map = useMap();
    useEffect(() => {
        mapRef.current = map;
    }, [map, mapRef]);
    return null;
}

export default function RestaurantsGeofences({ restaurants }: GeofencesOverviewProps) {
    const mapRef = useRef<L.Map | null>(null);
    // Guatemala center coordinates
    const guatemalaCenter: [number, number] = [14.6, -90.5];

    // Calculate overlapping areas between geofences (memoized - only once on load)
    const overlaps = useMemo(() => {
        const result: Overlap[] = [];
        const restaurantsWithGeofence = restaurants.filter(
            (r) => r.has_geofence && r.geofence_coordinates.length >= 3
        );

        for (let i = 0; i < restaurantsWithGeofence.length; i++) {
            for (let j = i + 1; j < restaurantsWithGeofence.length; j++) {
                const r1 = restaurantsWithGeofence[i];
                const r2 = restaurantsWithGeofence[j];

                try {
                    // Convert to turf polygons (turf uses [lng, lat] format)
                    const coords1 = r1.geofence_coordinates.map((c) => [c.lng, c.lat]);
                    const coords2 = r2.geofence_coordinates.map((c) => [c.lng, c.lat]);

                    // Close the polygon if not already closed
                    if (coords1[0][0] !== coords1[coords1.length - 1][0] || coords1[0][1] !== coords1[coords1.length - 1][1]) {
                        coords1.push(coords1[0]);
                    }
                    if (coords2[0][0] !== coords2[coords2.length - 1][0] || coords2[0][1] !== coords2[coords2.length - 1][1]) {
                        coords2.push(coords2[0]);
                    }

                    const poly1 = polygon([coords1]);
                    const poly2 = polygon([coords2]);

                    const intersection = intersect(featureCollection([poly1, poly2]));

                    if (intersection && intersection.geometry) {
                        // Handle both Polygon and MultiPolygon results
                        const geom = intersection.geometry;
                        if (geom.type === 'Polygon') {
                            const overlapCoords = geom.coordinates[0].map(
                                (c: number[]) => [c[1], c[0]] as [number, number]
                            );
                            result.push({
                                coordinates: overlapCoords,
                                restaurants: [r1.name, r2.name],
                            });
                        } else if (geom.type === 'MultiPolygon') {
                            geom.coordinates.forEach((polyCoords: number[][][]) => {
                                const overlapCoords = polyCoords[0].map(
                                    (c: number[]) => [c[1], c[0]] as [number, number]
                                );
                                result.push({
                                    coordinates: overlapCoords,
                                    restaurants: [r1.name, r2.name],
                                });
                            });
                        }
                    }
                } catch {
                    // Skip invalid polygons
                    console.warn(`Could not calculate intersection between ${r1.name} and ${r2.name}`);
                }
            }
        }

        return result;
    }, [restaurants]);

    // Edit mode state
    const [selectedRestaurantId, setSelectedRestaurantId] = useState<number | null>(null);
    const [editedCoordinates, setEditedCoordinates] = useState<[number, number][]>([]);
    const [originalCoordinates, setOriginalCoordinates] = useState<[number, number][]>([]);
    const [isSaving, setIsSaving] = useState(false);

    const selectedRestaurant = restaurants.find((r) => r.id === selectedRestaurantId);

    // Check if coordinates were actually edited
    const hasChanges = selectedRestaurantId && JSON.stringify(editedCoordinates) !== JSON.stringify(originalCoordinates);

    // Calculate bounds to fit all restaurants and geofences
    const getMapBounds = () => {
        const allCoordinates: Array<{ lat: number; lng: number }> = [];

        restaurants.forEach((restaurant) => {
            // Add restaurant coordinates
            if (restaurant.coordinates) {
                allCoordinates.push(restaurant.coordinates);
            }
            // Add geofence coordinates
            if (restaurant.geofence_coordinates.length > 0) {
                allCoordinates.push(...restaurant.geofence_coordinates);
            }
        });

        if (allCoordinates.length === 0) {
            return undefined;
        }

        const latitudes = allCoordinates.map((coord) => coord.lat);
        const longitudes = allCoordinates.map((coord) => coord.lng);

        const bounds = [
            [Math.min(...latitudes), Math.min(...longitudes)],
            [Math.max(...latitudes), Math.max(...longitudes)],
        ] as [[number, number], [number, number]];

        return bounds;
    };

    const bounds = getMapBounds();

    // Get polygon color based on restaurant status
    const getPolygonColor = (restaurant: Restaurant) => {
        if (!restaurant.is_active) return '#9ca3af'; // gray-400 for inactive
        return '#008C15'; // Subway green for active
    };

    // Edit mode handlers
    const handleSelectRestaurant = (restaurantId: number) => {
        const restaurant = restaurants.find((r) => r.id === restaurantId);
        if (restaurant && restaurant.has_geofence) {
            const coords = restaurant.geofence_coordinates.map((coord) => [coord.lat, coord.lng] as [number, number]);
            setSelectedRestaurantId(restaurantId);
            setEditedCoordinates(coords);
            setOriginalCoordinates(coords);
        }
    };

    const handleCancelEdit = useCallback(() => {
        setSelectedRestaurantId(null);
        setEditedCoordinates([]);
        setOriginalCoordinates([]);
    }, []);

    // Zoom to selected geofence
    const handleFitToGeofence = useCallback(() => {
        if (mapRef.current && editedCoordinates.length > 0) {
            const bounds = L.latLngBounds(editedCoordinates.map(([lat, lng]) => [lat, lng]));
            mapRef.current.fitBounds(bounds, { padding: [50, 50] });
        }
    }, [editedCoordinates]);

    // ESC key to cancel edit - capture phase to intercept before Geoman
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && selectedRestaurantId) {
                e.preventDefault();
                e.stopPropagation();
                handleCancelEdit();
            }
        };

        document.addEventListener('keydown', handleKeyDown, true);
        return () => document.removeEventListener('keydown', handleKeyDown, true);
    }, [selectedRestaurantId, handleCancelEdit]);

    const handlePolygonEdit = (coordinates: [number, number][][]) => {
        if (coordinates.length > 0) {
            setEditedCoordinates(coordinates[0]);
        }
    };

    const handleSaveGeofence = () => {
        if (!selectedRestaurantId || editedCoordinates.length < 3) return;

        setIsSaving(true);

        try {
            const kml = coordinatesToKML(editedCoordinates);

            router.post(
                route('restaurants.geofence.save', selectedRestaurantId),
                {
                    geofence_kml: kml,
                },
                {
                    onSuccess: () => {
                        setSelectedRestaurantId(null);
                        setEditedCoordinates([]);
                        setOriginalCoordinates([]);
                        setIsSaving(false);
                    },
                    onError: () => {
                        setIsSaving(false);
                    },
                },
            );
        } catch (error) {
            console.error('Error al convertir coordenadas a KML:', error);
            setIsSaving(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Geocercas" />

            <div className="rounded-lg border bg-card">
                <div className="border-b p-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <MapPin className="h-5 w-5" />
                            <h2 className="font-semibold">Geocercas</h2>
                        </div>
                        {overlaps.length > 0 && (
                            <div className="flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <span className="font-medium">{overlaps.length}</span>
                                <span>superposición{overlaps.length !== 1 ? 'es' : ''}</span>
                            </div>
                        )}
                    </div>
                </div>
                <div className="relative">
                    <div className="h-[calc(100vh-220px)]">
                        <MapContainer center={guatemalaCenter} zoom={11} bounds={bounds} style={{ height: '100%', width: '100%' }} className="z-0">
                            <MapController mapRef={mapRef} />
                            <TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />

                            {selectedRestaurantId && editedCoordinates.length > 0 && (
                                <GeomanControl onPolygonEdit={handlePolygonEdit} existingPolygon={editedCoordinates} fitBoundsOnLoad={false} />
                            )}

                            {restaurants.map((restaurant) => (
                                <React.Fragment key={restaurant.id}>
                                    {restaurant.has_geofence &&
                                        restaurant.geofence_coordinates.length > 0 &&
                                        restaurant.id !== selectedRestaurantId && (
                                            <Polygon
                                                positions={restaurant.geofence_coordinates.map((coord) => [coord.lat, coord.lng])}
                                                pathOptions={{
                                                    fillColor: getPolygonColor(restaurant),
                                                    weight: 2,
                                                    opacity: selectedRestaurantId ? 0.8 : 1,
                                                    color: '#000000',
                                                    fillOpacity: selectedRestaurantId ? 0.35 : 0.55,
                                                    dashArray: selectedRestaurantId ? '5, 5' : undefined,
                                                    className: 'cursor-pointer',
                                                }}
                                                eventHandlers={{
                                                    click: () => {
                                                        if (!selectedRestaurantId) {
                                                            handleSelectRestaurant(restaurant.id);
                                                        }
                                                    },
                                                }}
                                            >
                                                <Popup>
                                                    <div className="font-medium">{restaurant.name}</div>
                                                    <div className="text-xs text-muted-foreground">{restaurant.address}</div>
                                                </Popup>
                                            </Polygon>
                                        )}

                                    {restaurant.coordinates && (
                                        <Marker position={[restaurant.coordinates.lat, restaurant.coordinates.lng]}>
                                            <Popup>
                                                <div className="font-medium">{restaurant.name}</div>
                                            </Popup>
                                        </Marker>
                                    )}
                                </React.Fragment>
                            ))}

                            {/* Overlap areas in red */}
                            {overlaps.map((overlap, index) => (
                                <Polygon
                                    key={`overlap-${index}`}
                                    positions={overlap.coordinates}
                                    pathOptions={{
                                        fillColor: '#dc2626',
                                        weight: 2,
                                        opacity: 1,
                                        color: '#991b1b',
                                        fillOpacity: 0.6,
                                    }}
                                >
                                    <Popup>
                                        <div className="font-medium text-red-600">Superposición</div>
                                        <div className="text-xs text-muted-foreground">
                                            {overlap.restaurants[0]} ↔ {overlap.restaurants[1]}
                                        </div>
                                    </Popup>
                                </Polygon>
                            ))}
                        </MapContainer>

                        {/* Floating Action Panel */}
                        {selectedRestaurantId && (
                            <div className="absolute bottom-6 left-1/2 z-[1000] -translate-x-1/2">
                                <div className="rounded-lg border bg-card p-4 shadow-lg">
                                    <div className="mb-3 flex items-center gap-3">
                                        <Edit className="h-4 w-4 text-primary" />
                                        <div className="flex-1">
                                            <div className="text-sm font-medium">{selectedRestaurant?.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {editedCoordinates.length} puntos {!hasChanges && '• ESC para cerrar'}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        {hasChanges && (
                                            <Button
                                                size="sm"
                                                onClick={handleSaveGeofence}
                                                disabled={isSaving || editedCoordinates.length < 3}
                                                className="flex-1"
                                            >
                                                <Save className="mr-2 h-4 w-4" />
                                                {isSaving ? 'Guardando...' : 'Guardar'}
                                            </Button>
                                        )}
                                        <Button size="sm" variant="outline" onClick={handleFitToGeofence} disabled={isSaving}>
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </Button>
                                        <Button size="sm" variant={hasChanges ? 'outline' : 'secondary'} onClick={handleCancelEdit} disabled={isSaving}>
                                            <X className="mr-2 h-4 w-4" />
                                            {hasChanges ? 'Cancelar' : 'Cerrar'}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
