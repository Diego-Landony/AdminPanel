import { router, useForm } from '@inertiajs/react';
import L from 'leaflet';
import { Building2, Clock, DollarSign, FileText, Hash, Mail, MapPin, Navigation, Network, Pentagon, Phone, Search, Settings } from 'lucide-react';
import React, { useState } from 'react';
import { MapContainer, Marker, TileLayer, useMap, useMapEvents } from 'react-leaflet';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { GeomanControl } from '@/components/GeomanControl';
import { EditRestaurantsSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { AUTOCOMPLETE, PLACEHOLDERS } from '@/constants/ui-constants';
import { coordinatesToKML, parseKMLToCoordinates } from '@/utils/kmlParser';

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
    price_location: 'capital' | 'interior';
    latitude: number | null;
    longitude: number | null;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string | null;
    email: string | null;
    ip: string | null;
    franchise_number: string | null;
    schedule: Record<string, { is_open: boolean; open: string; close: string }> | null;
    minimum_order_amount: number;
    estimated_delivery_time: number;
    geofence_kml: string | null;
    created_at: string;
    updated_at: string;
}

interface EditPageProps {
    restaurant: Restaurant;
}

interface RestaurantFormData {
    name: string;
    address: string;
    price_location: 'capital' | 'interior';
    latitude: string;
    longitude: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string;
    email: string;
    ip: string;
    franchise_number: string;
    schedule: Record<string, { is_open: boolean; open: string; close: string }>;
    minimum_order_amount: string;
    estimated_delivery_time: string;
}

// Component to handle map clicks
interface LocationSelectorProps {
    onLocationSelect: (lat: number, lng: number) => void;
}

function LocationSelector({ onLocationSelect }: LocationSelectorProps) {
    useMapEvents({
        dblclick: (e) => {
            onLocationSelect(e.latlng.lat, e.latlng.lng);
        },
    });
    return null;
}

// Component to update map center when search result comes
interface MapCenterUpdaterProps {
    center: [number, number] | null;
}

function MapCenterUpdater({ center }: MapCenterUpdaterProps) {
    const map = useMap();

    if (center) {
        map.setView(center, 16, {
            animate: true,
            duration: 1,
        });
    }

    return null;
}

export default function RestaurantEdit({ restaurant }: EditPageProps) {
    const { data, setData, put, processing, errors } = useForm<RestaurantFormData>({
        name: restaurant.name,
        address: restaurant.address,
        price_location: restaurant.price_location || 'capital',
        latitude: restaurant.latitude?.toString() || '',
        longitude: restaurant.longitude?.toString() || '',
        is_active: restaurant.is_active,
        delivery_active: restaurant.delivery_active,
        pickup_active: restaurant.pickup_active,
        phone: restaurant.phone || '',
        email: restaurant.email || '',
        ip: restaurant.ip || '',
        franchise_number: restaurant.franchise_number || '',
        schedule: restaurant.schedule || {
            monday: { is_open: true, open: '08:00', close: '22:00' },
            tuesday: { is_open: true, open: '08:00', close: '22:00' },
            wednesday: { is_open: true, open: '08:00', close: '22:00' },
            thursday: { is_open: true, open: '08:00', close: '22:00' },
            friday: { is_open: true, open: '08:00', close: '22:00' },
            saturday: { is_open: true, open: '08:00', close: '22:00' },
            sunday: { is_open: true, open: '08:00', close: '22:00' },
        },
        minimum_order_amount: restaurant.minimum_order_amount.toString(),
        estimated_delivery_time: restaurant.estimated_delivery_time.toString(),
    });

    // Map marker position state
    const [markerPosition, setMarkerPosition] = useState<[number, number] | null>(
        restaurant.latitude && restaurant.longitude ? [restaurant.latitude, restaurant.longitude] : null,
    );

    // Search state
    const [searchQuery, setSearchQuery] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchError, setSearchError] = useState('');
    const [mapCenterToUpdate, setMapCenterToUpdate] = useState<[number, number] | null>(null);
    const [isMapModalOpen, setIsMapModalOpen] = useState(false);

    // Geofence state
    const [isGeofenceModalOpen, setIsGeofenceModalOpen] = useState(false);
    const [geofenceCoordinates, setGeofenceCoordinates] = useState<[number, number][]>(() => {
        if (restaurant.geofence_kml) {
            return parseKMLToCoordinates(restaurant.geofence_kml) || [];
        }
        return [];
    });
    const [geofenceSearchQuery, setGeofenceSearchQuery] = useState('');
    const [isGeofenceSearching, setIsGeofenceSearching] = useState(false);
    const [geofenceSearchError, setGeofenceSearchError] = useState('');
    const [geofenceMapCenter, setGeofenceMapCenter] = useState<[number, number] | null>(null);
    const [isSavingGeofence, setIsSavingGeofence] = useState(false);

    // Default center for Guatemala
    const guatemalaCenter: [number, number] = [14.6349, -90.5069];
    const mapCenter = markerPosition || guatemalaCenter;
    // Geofence center: prioritize map search, then restaurant location, then existing polygon, then Guatemala
    const geofenceCenter = geofenceMapCenter || markerPosition || (geofenceCoordinates.length > 0 ? geofenceCoordinates[0] : guatemalaCenter);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('restaurants.update', restaurant.id));
    };

    const handleScheduleChange = (day: string, field: string, value: boolean | string) => {
        setData('schedule', {
            ...data.schedule,
            [day]: {
                ...data.schedule[day],
                [field]: value,
            },
        });
    };

    const handleLocationSelect = (lat: number, lng: number) => {
        const latStr = lat.toFixed(7);
        const lngStr = lng.toFixed(7);
        setData('latitude', latStr);
        setData('longitude', lngStr);
        setMarkerPosition([lat, lng]);
    };

    const handleSearch = async () => {
        if (!searchQuery.trim()) return;

        setIsSearching(true);
        setSearchError('');

        try {
            // Using Nominatim API (OpenStreetMap) with proper headers and format
            const searchParams = new URLSearchParams({
                q: searchQuery,
                format: 'jsonv2',
                addressdetails: '1',
                limit: '5',
                countrycodes: 'gt',
            });

            const response = await fetch(`https://nominatim.openstreetmap.org/search?${searchParams.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'User-Agent': 'RestaurantLocationPicker/1.0',
                },
            });

            if (!response.ok) {
                throw new Error('Error en la búsqueda');
            }

            const results = await response.json();

            if (results && results.length > 0) {
                const { lat, lon } = results[0];
                const latitude = parseFloat(lat);
                const longitude = parseFloat(lon);
                handleLocationSelect(latitude, longitude);
                setMapCenterToUpdate([latitude, longitude]); // Trigger map center update
                setSearchError('');
            } else {
                setSearchError('No se encontró la ubicación. Intenta con términos más específicos (ej: "zona 10 Guatemala", "Antigua Guatemala").');
            }
        } catch (error) {
            console.error('Error al buscar ubicación:', error);
            setSearchError('Error al buscar la ubicación. Verifica tu conexión e intenta nuevamente.');
        } finally {
            setIsSearching(false);
        }
    };

    // Geofence handlers
    const handleGeofenceSearch = async () => {
        if (!geofenceSearchQuery.trim()) return;

        setIsGeofenceSearching(true);
        setGeofenceSearchError('');

        try {
            const searchParams = new URLSearchParams({
                q: geofenceSearchQuery,
                format: 'jsonv2',
                addressdetails: '1',
                limit: '5',
                countrycodes: 'gt',
            });

            const response = await fetch(`https://nominatim.openstreetmap.org/search?${searchParams.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'User-Agent': 'RestaurantLocationPicker/1.0',
                },
            });

            if (!response.ok) {
                throw new Error('Error en la búsqueda');
            }

            const results = await response.json();

            if (results && results.length > 0) {
                const { lat, lon } = results[0];
                const latitude = parseFloat(lat);
                const longitude = parseFloat(lon);
                setGeofenceMapCenter([latitude, longitude]);
                setGeofenceSearchError('');
            } else {
                setGeofenceSearchError('No se encontró la ubicación. Intenta con términos más específicos.');
            }
        } catch (error) {
            console.error('Error al buscar ubicación:', error);
            setGeofenceSearchError('Error al buscar la ubicación. Verifica tu conexión e intenta nuevamente.');
        } finally {
            setIsGeofenceSearching(false);
        }
    };

    const handlePolygonCreate = (coordinates: [number, number][][]) => {
        if (coordinates.length > 0) {
            setGeofenceCoordinates(coordinates[0]);
        }
    };

    const handlePolygonEdit = (coordinates: [number, number][][]) => {
        if (coordinates.length > 0) {
            setGeofenceCoordinates(coordinates[0]);
        }
    };

    const handleSaveGeofence = () => {
        if (geofenceCoordinates.length < 3) {
            setGeofenceSearchError('Debes dibujar un polígono con al menos 3 puntos.');
            return;
        }

        setIsSavingGeofence(true);

        try {
            const kml = coordinatesToKML(geofenceCoordinates);

            router.post(
                route('restaurants.geofence.save', restaurant.id),
                {
                    geofence_kml: kml,
                },
                {
                    onSuccess: () => {
                        setIsGeofenceModalOpen(false);
                        setIsSavingGeofence(false);
                    },
                    onError: () => {
                        setGeofenceSearchError('Error al guardar la geocerca. Intenta nuevamente.');
                        setIsSavingGeofence(false);
                    },
                },
            );
        } catch (error) {
            console.error('Error al convertir coordenadas a KML:', error);
            setGeofenceSearchError('Error al procesar las coordenadas.');
            setIsSavingGeofence(false);
        }
    };

    const dayLabels: Record<string, string> = {
        monday: 'Lun',
        tuesday: 'Mar',
        wednesday: 'Mie',
        thursday: 'Jue',
        friday: 'Vie',
        saturday: 'Sab',
        sunday: 'Dom',
    };

    const hasGeofence = !!restaurant.geofence_kml;

    return (
        <EditPageLayout
            title="Editar Restaurante"
            description={`Modifica los datos de "${restaurant.name}"`}
            backHref={route('restaurants.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle={`Editar ${restaurant.name}`}
            loading={false}
            loadingSkeleton={EditRestaurantsSkeleton}
        >
            <div className="space-y-8">
                {/* Información Básica */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Building2} title="Información Básica" description="Datos principales del restaurante">
                            <div className="space-y-6">
                                <FormField label="Nombre" error={errors.name} required>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        autoComplete={AUTOCOMPLETE.organizationName}
                                    />
                                </FormField>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div className="md:col-span-2">
                                        <FormField label="Dirección" error={errors.address} required>
                                            <div className="relative">
                                                <MapPin className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                                <Input
                                                    id="address"
                                                    value={data.address}
                                                    onChange={(e) => setData('address', e.target.value)}
                                                    placeholder={PLACEHOLDERS.address}
                                                    className="pl-10"
                                                    autoComplete={AUTOCOMPLETE.address}
                                                />
                                            </div>
                                        </FormField>
                                    </div>

                                    <FormField label="Tipo de Precio" error={errors.price_location} required>
                                        <div className="relative">
                                            <DollarSign className="pointer-events-none absolute top-3 left-3 z-10 h-4 w-4 text-muted-foreground" />
                                            <Select value={data.price_location} onValueChange={(value: 'capital' | 'interior') => setData('price_location', value)}>
                                                <SelectTrigger className="pl-10">
                                                    <SelectValue placeholder="Seleccionar tipo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="capital">Capital</SelectItem>
                                                    <SelectItem value="interior">Interior</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </FormField>
                                </div>

                <div className="space-y-4">
                    <Dialog open={isMapModalOpen} onOpenChange={setIsMapModalOpen}>
                        <DialogTrigger asChild>
                            <Button type="button" variant="outline" className="w-full">
                                <MapPin className="mr-2 h-4 w-4" />
                                Seleccionar Ubicación en Mapa
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="flex h-[90vh] flex-col sm:max-w-6xl">
                            <DialogHeader>
                                <DialogTitle>Seleccionar Ubicación del Restaurante</DialogTitle>
                                <DialogDescription>Haz doble clic en el mapa para seleccionar la ubicación exacta</DialogDescription>
                            </DialogHeader>

                            <div className="flex min-h-0 flex-1 flex-col gap-4">
                                {/* Search Control - Always visible */}
                                <div className="flex flex-shrink-0 gap-2">
                                    <div className="relative flex-1">
                                        <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            type="text"
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    handleSearch();
                                                }
                                            }}
                                            placeholder="Buscar dirección en Guatemala..."
                                            className="pl-10"
                                        />
                                    </div>
                                    <Button type="button" variant="default" onClick={handleSearch} disabled={isSearching || !searchQuery.trim()}>
                                        {isSearching ? 'Buscando...' : 'Buscar'}
                                    </Button>
                                </div>

                                {/* Error message */}
                                {searchError && (
                                    <div className="flex-shrink-0 rounded-md border border-destructive/20 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                        {searchError}
                                    </div>
                                )}

                                {/* Map Container - Takes remaining space */}
                                <div className="min-h-0 flex-1 overflow-hidden rounded-lg border">
                                    <MapContainer center={mapCenter} zoom={13} style={{ height: '100%', width: '100%' }} className="z-0">
                                        <TileLayer
                                            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                        />
                                        <LocationSelector onLocationSelect={handleLocationSelect} />
                                        <MapCenterUpdater center={mapCenterToUpdate} />
                                        {markerPosition && <Marker position={markerPosition} />}
                                    </MapContainer>
                                </div>

                                {/* Coordinates Display - Always at bottom */}
                                {markerPosition && (
                                    <div className="flex flex-shrink-0 items-center gap-4 text-sm text-muted-foreground">
                                        <span>Coordenadas seleccionadas:</span>
                                        <Badge variant="outline">Lat: {parseFloat(data.latitude).toFixed(6)}</Badge>
                                        <Badge variant="outline">Lng: {parseFloat(data.longitude).toFixed(6)}</Badge>
                                    </div>
                                )}
                            </div>
                        </DialogContent>
                    </Dialog>

                    {/* Geofence Modal */}
                    <Dialog open={isGeofenceModalOpen} onOpenChange={setIsGeofenceModalOpen}>
                        <DialogTrigger asChild>
                            <Button type="button" variant="outline" className="flex w-full items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Pentagon className="h-4 w-4" />
                                    <span>{hasGeofence ? 'Editar Geocerca' : 'Crear Geocerca'}</span>
                                </div>
                                {hasGeofence && (
                                    <Badge variant="outline" className="ml-2 border-green-200 bg-green-50 text-green-700">
                                        <FileText className="mr-1 h-3 w-3" />
                                        Configurada
                                    </Badge>
                                )}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="flex h-[90vh] flex-col sm:max-w-6xl">
                            <DialogHeader>
                                <DialogTitle>Geocerca del Restaurante</DialogTitle>
                                <DialogDescription>Dibuja un polígono haciendo clic en el mapa para delimitar el área de entrega</DialogDescription>
                            </DialogHeader>

                            <div className="flex min-h-0 flex-1 flex-col gap-4">
                                {/* Search Control */}
                                <div className="flex flex-shrink-0 gap-2">
                                    <div className="relative flex-1">
                                        <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            type="text"
                                            value={geofenceSearchQuery}
                                            onChange={(e) => setGeofenceSearchQuery(e.target.value)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    handleGeofenceSearch();
                                                }
                                            }}
                                            placeholder="Buscar dirección en Guatemala..."
                                            className="pl-10"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="default"
                                        onClick={handleGeofenceSearch}
                                        disabled={isGeofenceSearching || !geofenceSearchQuery.trim()}
                                    >
                                        {isGeofenceSearching ? 'Buscando...' : 'Buscar'}
                                    </Button>
                                </div>

                                {/* Error message */}
                                {geofenceSearchError && (
                                    <div className="flex-shrink-0 rounded-md border border-destructive/20 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                        {geofenceSearchError}
                                    </div>
                                )}

                                {/* Map Container */}
                                <div className="min-h-0 flex-1 overflow-hidden rounded-lg border">
                                    <MapContainer center={geofenceCenter} zoom={13} style={{ height: '100%', width: '100%' }} className="z-0">
                                        <TileLayer
                                            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                        />
                                        <GeomanControl
                                            onPolygonCreate={handlePolygonCreate}
                                            onPolygonEdit={handlePolygonEdit}
                                            existingPolygon={geofenceCoordinates}
                                        />
                                        {markerPosition && <Marker position={markerPosition} />}
                                    </MapContainer>
                                </div>

                                {/* Polygon Info & Save Button */}
                                <div className="flex flex-shrink-0 items-center justify-between">
                                    {geofenceCoordinates.length > 0 ? (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Pentagon className="h-4 w-4" />
                                            <span>Polígono con {geofenceCoordinates.length} puntos</span>
                                        </div>
                                    ) : (
                                        <div className="text-sm text-muted-foreground">Usa las herramientas del mapa para dibujar la geocerca</div>
                                    )}
                                    <Button type="button" onClick={handleSaveGeofence} disabled={isSavingGeofence || geofenceCoordinates.length < 3}>
                                        {isSavingGeofence ? 'Guardando...' : 'Guardar Geocerca'}
                                    </Button>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField label="Latitud" error={errors.latitude}>
                            <div className="relative">
                                <Navigation className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="latitude"
                                    type="text"
                                    value={data.latitude}
                                    placeholder={PLACEHOLDERS.latitude}
                                    className="pl-10"
                                    readOnly
                                />
                            </div>
                        </FormField>

                        <FormField label="Longitud" error={errors.longitude}>
                            <div className="relative">
                                <Navigation className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="longitude"
                                    type="text"
                                    value={data.longitude}
                                    placeholder={PLACEHOLDERS.longitude}
                                    className="pl-10"
                                    readOnly
                                />
                            </div>
                        </FormField>
                    </div>
                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="Teléfono" error={errors.phone}>
                                        <div className="relative">
                                            <Phone className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="phone"
                                                value={data.phone}
                                                onChange={(e) => setData('phone', e.target.value)}
                                                placeholder={PLACEHOLDERS.phone}
                                                className="pl-10"
                                                autoComplete={AUTOCOMPLETE.phone}
                                            />
                                        </div>
                                    </FormField>

                                    <FormField label="Email" error={errors.email}>
                                        <div className="relative">
                                            <Mail className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="email"
                                                type="email"
                                                value={data.email}
                                                onChange={(e) => setData('email', e.target.value)}
                                                placeholder={PLACEHOLDERS.email}
                                                className="pl-10"
                                                autoComplete={AUTOCOMPLETE.email}
                                            />
                                        </div>
                                    </FormField>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="IP del Restaurante" error={errors.ip}>
                                        <div className="relative">
                                            <Network className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="ip"
                                                value={data.ip}
                                                onChange={(e) => setData('ip', e.target.value)}
                                                placeholder={PLACEHOLDERS.ip}
                                                className="pl-10"
                                            />
                                        </div>
                                    </FormField>

                                    <FormField label="Número de Franquicia" error={errors.franchise_number}>
                                        <div className="relative">
                                            <Hash className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="franchise_number"
                                                value={data.franchise_number}
                                                onChange={(e) => setData('franchise_number', e.target.value)}
                                                placeholder={PLACEHOLDERS.franchiseNumber}
                                                className="pl-10"
                                            />
                                        </div>
                                    </FormField>
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                {/* Configuración de Servicios */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Settings} title="Configuración de Servicios" description="Opciones de delivery y pickup">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="is_active" className="cursor-pointer">
                            Restaurante Activo
                        </Label>
                        <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                    </div>

                    <div className="flex items-center justify-between">
                        <Label htmlFor="delivery_active" className="cursor-pointer">
                            Servicio de Delivery
                        </Label>
                        <Switch
                            id="delivery_active"
                            checked={data.delivery_active}
                            onCheckedChange={(checked) => setData('delivery_active', checked as boolean)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <Label htmlFor="pickup_active" className="cursor-pointer">
                            Servicio de Pickup
                        </Label>
                        <Switch
                            id="pickup_active"
                            checked={data.pickup_active}
                            onCheckedChange={(checked) => setData('pickup_active', checked as boolean)}
                        />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Monto Mínimo de Pedido (Q)" error={errors.minimum_order_amount}>
                        <Input
                            id="minimum_order_amount"
                            type="number"
                            step="0.01"
                            value={data.minimum_order_amount}
                            onChange={(e) => setData('minimum_order_amount', e.target.value)}
                            placeholder={PLACEHOLDERS.amount}
                        />
                    </FormField>

                    <FormField label="Tiempo Estimado de Entrega (min)" error={errors.estimated_delivery_time}>
                        <Input
                            id="estimated_delivery_time"
                            type="number"
                            value={data.estimated_delivery_time}
                            onChange={(e) => setData('estimated_delivery_time', e.target.value)}
                            placeholder={PLACEHOLDERS.estimatedTime}
                        />
                    </FormField>
                </div>
                        </FormSection>
                    </CardContent>
                </Card>

                {/* Horarios de Atención */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Clock} title="Horarios de Atención" description="Define los horarios de atención para cada día de la semana">
                <div className="space-y-4">
                    {Object.entries(dayLabels).map(([day, label]) => (
                        <div key={day} className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                            <div className="w-24 flex-shrink-0">
                                <Label className="font-medium">{label}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    checked={data.schedule[day]?.is_open || false}
                                    onCheckedChange={(checked) => handleScheduleChange(day, 'is_open', checked as boolean)}
                                />
                                <Label className="text-sm">Abierto</Label>
                            </div>
                            {data.schedule[day]?.is_open && (
                                <div className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <Label className="text-sm font-medium">De:</Label>
                                        <Input
                                            type="time"
                                            value={data.schedule[day]?.open || '08:00'}
                                            onChange={(e) => handleScheduleChange(day, 'open', e.target.value)}
                                            className="w-32"
                                        />
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Label className="text-sm font-medium">A:</Label>
                                        <Input
                                            type="time"
                                            value={data.schedule[day]?.close || '22:00'}
                                            onChange={(e) => handleScheduleChange(day, 'close', e.target.value)}
                                            className="w-32"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </EditPageLayout>
    );
}
