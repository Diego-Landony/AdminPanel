import { Head, router } from '@inertiajs/react';
import { MapPin, Edit, Save, X } from 'lucide-react';
import React, { useState } from 'react';
import { MapContainer, TileLayer, Polygon, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';

import { Button } from '@/components/ui/button';
import { GeomanControl } from '@/components/GeomanControl';
import { coordinatesToKML } from '@/utils/kmlParser';
import AppLayout from '@/layouts/app-layout';

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

export default function RestaurantsGeofences({ restaurants }: GeofencesOverviewProps) {
    // Guatemala center coordinates
    const guatemalaCenter: [number, number] = [14.6, -90.5];

    // Edit mode state
    const [selectedRestaurantId, setSelectedRestaurantId] = useState<number | null>(null);
    const [editedCoordinates, setEditedCoordinates] = useState<[number, number][]>([]);
    const [originalCoordinates, setOriginalCoordinates] = useState<[number, number][]>([]);
    const [isSaving, setIsSaving] = useState(false);

    const selectedRestaurant = restaurants.find(r => r.id === selectedRestaurantId);

    // Check if coordinates were actually edited
    const hasChanges = selectedRestaurantId &&
        JSON.stringify(editedCoordinates) !== JSON.stringify(originalCoordinates);

    // Calculate bounds to fit all restaurants and geofences
    const getMapBounds = () => {
        const allCoordinates: Array<{ lat: number; lng: number }> = [];

        restaurants.forEach(restaurant => {
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

        const latitudes = allCoordinates.map(coord => coord.lat);
        const longitudes = allCoordinates.map(coord => coord.lng);

        const bounds = [
            [Math.min(...latitudes), Math.min(...longitudes)],
            [Math.max(...latitudes), Math.max(...longitudes)]
        ] as [[number, number], [number, number]];

        return bounds;
    };

    const bounds = getMapBounds();

    // Get polygon color based on restaurant status
    const getPolygonColor = (restaurant: Restaurant) => {
        if (!restaurant.is_active) return '#dc2626'; // red-600 for inactive
        if (restaurant.delivery_active) return '#16a34a'; // green-600 for delivery
        if (restaurant.pickup_active) return '#ea580c'; // orange-600 for pickup only
        return '#6b7280'; // gray-500 for others
    };


    // Edit mode handlers
    const handleSelectRestaurant = (restaurantId: number) => {
        const restaurant = restaurants.find(r => r.id === restaurantId);
        if (restaurant && restaurant.has_geofence) {
            const coords = restaurant.geofence_coordinates.map(coord => [coord.lat, coord.lng] as [number, number]);
            setSelectedRestaurantId(restaurantId);
            setEditedCoordinates(coords);
            setOriginalCoordinates(coords);
        }
    };

    const handleCancelEdit = () => {
        setSelectedRestaurantId(null);
        setEditedCoordinates([]);
        setOriginalCoordinates([]);
    };

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

            router.post(route('restaurants.geofence.save', selectedRestaurantId), {
                geofence_kml: kml,
            }, {
                onSuccess: () => {
                    setSelectedRestaurantId(null);
                    setEditedCoordinates([]);
                    setOriginalCoordinates([]);
                    setIsSaving(false);
                },
                onError: () => {
                    setIsSaving(false);
                },
            });
        } catch (error) {
            console.error('Error al convertir coordenadas a KML:', error);
            setIsSaving(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Geocercas" />

            <div className="rounded-lg border bg-card">
                <div className="p-6 border-b">
                    <div className="flex items-center gap-2">
                        <MapPin className="h-5 w-5" />
                        <h2 className="font-semibold">Geocercas</h2>
                    </div>
                </div>
                <div className="relative">
                    <div className="h-[calc(100vh-220px)]">
                        <MapContainer
                            center={guatemalaCenter}
                            zoom={11}
                            bounds={bounds}
                            style={{ height: '100%', width: '100%' }}
                            className="z-0"
                        >
                            <TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />

                            {selectedRestaurantId && editedCoordinates.length > 0 && (
                                <GeomanControl
                                    onPolygonEdit={handlePolygonEdit}
                                    existingPolygon={editedCoordinates}
                                />
                            )}

                            {restaurants.map((restaurant) => (
                                <React.Fragment key={restaurant.id}>
                                    {restaurant.has_geofence &&
                                        restaurant.geofence_coordinates.length > 0 &&
                                        restaurant.id !== selectedRestaurantId && (
                                        <Polygon
                                            positions={restaurant.geofence_coordinates.map(coord => [coord.lat, coord.lng])}
                                            pathOptions={{
                                                fillColor: getPolygonColor(restaurant),
                                                weight: 2,
                                                opacity: selectedRestaurantId ? 0.3 : 1,
                                                color: getPolygonColor(restaurant),
                                                fillOpacity: selectedRestaurantId ? 0.1 : 0.4,
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
                        </MapContainer>

                        {/* Floating Action Panel */}
                        {hasChanges && (
                            <div className="absolute bottom-6 left-1/2 -translate-x-1/2 z-[1000]">
                                <div className="bg-card border rounded-lg shadow-lg p-4">
                                    <div className="flex items-center gap-3 mb-3">
                                        <Edit className="h-4 w-4 text-primary" />
                                        <div className="flex-1">
                                            <div className="font-medium text-sm">{selectedRestaurant?.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {editedCoordinates.length} puntos
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            onClick={handleSaveGeofence}
                                            disabled={isSaving || editedCoordinates.length < 3}
                                            className="flex-1"
                                        >
                                            <Save className="h-4 w-4 mr-2" />
                                            {isSaving ? 'Guardando...' : 'Guardar'}
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={handleCancelEdit}
                                            disabled={isSaving}
                                        >
                                            <X className="h-4 w-4" />
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