import { Building2, FileText, MapPin, Truck, ShoppingBag } from 'lucide-react';
import React from 'react';
import { MapContainer, TileLayer, Polygon, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';

import { Badge } from '@/components/ui/badge';
import { ViewPageLayout } from '@/components/view-page-layout';
import { FormSection } from '@/components/form-section';

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

    const getServiceType = (restaurant: Restaurant) => {
        if (!restaurant.is_active) return 'Inactivo';
        if (restaurant.delivery_active && restaurant.pickup_active) return 'Delivery + Pickup';
        if (restaurant.delivery_active) return 'Solo Delivery';
        if (restaurant.pickup_active) return 'Solo Pickup';
        return 'Sin servicio';
    };

    return (
        <ViewPageLayout
            title="Geocercas"
            description="Mapa de zonas de entrega de restaurantes"
            backHref={route('restaurants.index')}
            backLabel="Volver a Restaurantes"
            pageTitle="Geocercas Restaurantes"
        >

            {/* Map */}
            <FormSection
                icon={MapPin}
                title="Mapa de Geocercas"
                description="Haz clic en las geocercas y marcadores para más información."
            >
                <div className="h-[700px] w-full rounded-lg overflow-hidden border">
                    <MapContainer
                        center={guatemalaCenter}
                        zoom={11}
                        bounds={bounds}
                        style={{ height: '100%', width: '100%' }}
                        className="z-0"
                    >
                        <TileLayer
                            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        />

                        {restaurants.map((restaurant) => (
                            <React.Fragment key={restaurant.id}>
                                {/* Geofence Polygon */}
                                {restaurant.has_geofence && restaurant.geofence_coordinates.length > 0 && (
                                    <Polygon
                                        positions={restaurant.geofence_coordinates.map(coord => [coord.lat, coord.lng])}
                                        pathOptions={{
                                            fillColor: getPolygonColor(restaurant),
                                            weight: 2,
                                            opacity: 1,
                                            color: getPolygonColor(restaurant),
                                            fillOpacity: 0.4,
                                        }}
                                    >
                                        <Popup>
                                            <div className="min-w-48">
                                                <div className="font-semibold text-base mb-2">{restaurant.name}</div>
                                                <div className="space-y-1 text-sm">
                                                    <div className="flex items-center gap-2">
                                                        <MapPin className="h-3 w-3 text-gray-500" />
                                                        <span className="text-gray-700">{restaurant.address}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <FileText className="h-3 w-3 text-green-500" />
                                                        <span>Zona de entrega: {restaurant.geofence_coordinates.length} puntos</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {restaurant.delivery_active ? (
                                                            <Truck className="h-3 w-3 text-green-500" />
                                                        ) : restaurant.pickup_active ? (
                                                            <ShoppingBag className="h-3 w-3 text-orange-500" />
                                                        ) : (
                                                            <Building2 className="h-3 w-3 text-gray-500" />
                                                        )}
                                                        <span>{getServiceType(restaurant)}</span>
                                                    </div>
                                                    <div className="mt-2">
                                                        <Badge
                                                            className={`text-xs ${
                                                                restaurant.is_active
                                                                    ? 'bg-green-100 text-green-800 border-green-200'
                                                                    : 'bg-red-100 text-red-800 border-red-200'
                                                            }`}
                                                        >
                                                            {restaurant.is_active ? 'Activo' : 'Inactivo'}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        </Popup>
                                    </Polygon>
                                )}

                                {/* Restaurant Marker */}
                                {restaurant.coordinates && (
                                    <Marker
                                        position={[restaurant.coordinates.lat, restaurant.coordinates.lng]}
                                    >
                                        <Popup>
                                            <div className="min-w-48">
                                                <div className="font-semibold text-base mb-2">{restaurant.name}</div>
                                                <div className="space-y-1 text-sm">
                                                    <div className="flex items-center gap-2">
                                                        <MapPin className="h-3 w-3 text-gray-500" />
                                                        <span className="text-gray-700">{restaurant.address}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Building2 className="h-3 w-3 text-blue-500" />
                                                        <span>Ubicación del restaurante</span>
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        GPS: {restaurant.coordinates.lat.toFixed(6)}, {restaurant.coordinates.lng.toFixed(6)}
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {restaurant.delivery_active ? (
                                                            <Truck className="h-3 w-3 text-green-500" />
                                                        ) : restaurant.pickup_active ? (
                                                            <ShoppingBag className="h-3 w-3 text-orange-500" />
                                                        ) : (
                                                            <Building2 className="h-3 w-3 text-gray-500" />
                                                        )}
                                                        <span>{getServiceType(restaurant)}</span>
                                                    </div>
                                                    <div className="mt-2 space-y-1">
                                                        <Badge
                                                            className={`text-xs mr-1 ${
                                                                restaurant.is_active
                                                                    ? 'bg-green-100 text-green-800 border-green-200'
                                                                    : 'bg-red-100 text-red-800 border-red-200'
                                                            }`}
                                                        >
                                                            {restaurant.is_active ? 'Activo' : 'Inactivo'}
                                                        </Badge>
                                                        {restaurant.has_geofence && (
                                                            <Badge className="text-xs bg-blue-100 text-blue-800 border-blue-200">
                                                                <FileText className="h-3 w-3 mr-1" />
                                                                Con KML
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </Popup>
                                    </Marker>
                                )}
                            </React.Fragment>
                        ))}
                    </MapContainer>
                </div>
            </FormSection>
        </ViewPageLayout>
    );
}