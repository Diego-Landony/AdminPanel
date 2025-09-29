import { Building2, FileText, MapPin } from 'lucide-react';
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
    coordinates: { lat: number; lng: number } | null;
}

interface GeofenceCoordinate {
    lat: number;
    lng: number;
}

interface KMLPreviewProps {
    restaurant: Restaurant;
    geofence_coordinates: GeofenceCoordinate[];
}

export default function KMLPreview({ restaurant, geofence_coordinates }: KMLPreviewProps) {
    // Default center to Guatemala if no restaurant coordinates
    const defaultCenter: [number, number] = [14.6, -90.5];
    const mapCenter: [number, number] = restaurant.coordinates
        ? [restaurant.coordinates.lat, restaurant.coordinates.lng]
        : defaultCenter;

    // Calculate map bounds to fit the geofence
    const getMapBounds = () => {
        if (geofence_coordinates.length === 0) {
            return undefined;
        }

        const latitudes = geofence_coordinates.map(coord => coord.lat);
        const longitudes = geofence_coordinates.map(coord => coord.lng);

        const bounds = [
            [Math.min(...latitudes), Math.min(...longitudes)],
            [Math.max(...latitudes), Math.max(...longitudes)]
        ] as [[number, number], [number, number]];

        return bounds;
    };

    const bounds = getMapBounds();

    // Convert coordinates to Leaflet format
    const polygonPositions: [number, number][] = geofence_coordinates.map(coord => [coord.lat, coord.lng]);

    return (
        <ViewPageLayout
            title="Preview de Geocerca KML"
            description={`Visualización del área de entrega definida para ${restaurant.name}`}
            backHref={route('restaurants.edit', restaurant.id)}
            backLabel="Volver a Editar"
            pageTitle={`Preview KML - ${restaurant.name}`}
        >
            {/* Restaurant Info */}
            <FormSection
                icon={Building2}
                title="Información del Restaurante"
                description="Datos del restaurante y estado de la geocerca"
            >
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label className="text-sm font-medium text-muted-foreground">Nombre</label>
                        <p className="text-lg font-semibold">{restaurant.name}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-muted-foreground">Dirección</label>
                        <div className="flex items-center gap-2">
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                            <span className="text-sm">{restaurant.address}</span>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-muted-foreground">Estado KML</label>
                        <div className="flex items-center gap-2 mt-1">
                            <Badge className="bg-green-100 text-green-800 border-green-200">
                                <FileText className="h-3 w-3 mr-1" />
                                KML Cargado
                            </Badge>
                            {restaurant.coordinates && (
                                <Badge variant="outline" className="text-xs">
                                    GPS: {restaurant.coordinates.lat.toFixed(6)}, {restaurant.coordinates.lng.toFixed(6)}
                                </Badge>
                            )}
                        </div>
                    </div>
                </div>
            </FormSection>

            {/* Map */}
            <FormSection
                icon={MapPin}
                title="Mapa de Geocerca"
                description="El área verde representa la zona de entrega definida por el archivo KML"
            >
                <div className="h-[500px] w-full rounded-lg overflow-hidden border">
                    {geofence_coordinates.length > 0 ? (
                        <MapContainer
                            center={mapCenter}
                            zoom={13}
                            bounds={bounds}
                            style={{ height: '100%', width: '100%' }}
                            className="z-0"
                        >
                            <TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />

                            {/* Geocerca Polygon */}
                            <Polygon
                                positions={polygonPositions}
                                pathOptions={{
                                    fillColor: 'green',
                                    weight: 2,
                                    opacity: 1,
                                    color: 'darkgreen',
                                    fillOpacity: 0.4,
                                }}
                            >
                                <Popup>
                                    <div className="text-center">
                                        <strong>{restaurant.name}</strong>
                                        <br />
                                        Zona de Entrega
                                    </div>
                                </Popup>
                            </Polygon>

                            {/* Restaurant Marker */}
                            {restaurant.coordinates && (
                                <Marker position={[restaurant.coordinates.lat, restaurant.coordinates.lng]}>
                                    <Popup>
                                        <div className="text-center">
                                            <strong>{restaurant.name}</strong>
                                            <br />
                                            {restaurant.address}
                                        </div>
                                    </Popup>
                                </Marker>
                            )}
                        </MapContainer>
                    ) : (
                        <div className="flex items-center justify-center h-full bg-gray-50 dark:bg-gray-900">
                            <div className="text-center">
                                <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    No hay coordenadas para mostrar
                                </h3>
                                <p className="text-gray-500 dark:text-gray-400">
                                    El archivo KML no contiene coordenadas válidas para mostrar en el mapa.
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </FormSection>

            {/* Statistics */}
            <FormSection
                icon={FileText}
                title="Estadísticas de Geocerca"
                description="Información detallada sobre el archivo KML y las coordenadas"
            >
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded">
                                <MapPin className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Puntos del Polígono</p>
                                <p className="text-lg font-semibold">{geofence_coordinates.length}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-green-100 dark:bg-green-900 rounded">
                                <FileText className="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Estado KML</p>
                                <p className="text-lg font-semibold text-green-600">Válido</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-orange-100 dark:bg-orange-900 rounded">
                                <Building2 className="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Coordenadas GPS</p>
                                <p className="text-lg font-semibold">
                                    {restaurant.coordinates ? 'Disponibles' : 'No definidas'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </FormSection>
        </ViewPageLayout>
    );
}