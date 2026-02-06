import { router, useForm, Link } from '@inertiajs/react';
import L from 'leaflet';
import { Building2, Clock, DollarSign, Eye, EyeOff, FileText, Hash, Lock, Mail, MapPin, Navigation, Network, Pencil, Pentagon, Phone, Plus, Search, Settings, Trash2, Truck, Users, UserCog } from 'lucide-react';
import React, { useState } from 'react';
import { MapContainer, Marker, TileLayer, useMap, useMapEvents } from 'react-leaflet';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { GeomanControl } from '@/components/GeomanControl';
import { EditRestaurantsSkeleton } from '@/components/skeletons';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { AUTOCOMPLETE, PLACEHOLDERS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
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
    estimated_pickup_time: number;
    geofence_kml: string | null;
    created_at: string;
    updated_at: string;
}

interface RestaurantUser {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
}

interface Driver {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    is_available: boolean;
}

interface EditPageProps {
    restaurant: Restaurant;
    restaurant_users?: RestaurantUser[];
    drivers?: Driver[];
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
    estimated_pickup_time: string;
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

/**
 * Formulario de usuario de restaurante
 */
interface UserFormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_active: boolean;
}

export default function RestaurantEdit({ restaurant, restaurant_users = [], drivers = [] }: EditPageProps) {
    // Estado para modales de usuarios
    const [isUserModalOpen, setIsUserModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<RestaurantUser | null>(null);
    const [userToDelete, setUserToDelete] = useState<RestaurantUser | null>(null);
    const [showDeleteUserDialog, setShowDeleteUserDialog] = useState(false);
    const [isDeletingUser, setIsDeletingUser] = useState(false);
    const [showUserPassword, setShowUserPassword] = useState(false);
    const [userFormProcessing, setUserFormProcessing] = useState(false);

    // Form para usuario
    const [userForm, setUserForm] = useState<UserFormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        is_active: true,
    });
    const [userFormErrors, setUserFormErrors] = useState<Record<string, string>>({});

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
        estimated_pickup_time: restaurant.estimated_pickup_time?.toString() || '15',
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

    // Funciones para CRUD de usuarios de restaurante
    const resetUserForm = () => {
        setUserForm({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            is_active: true,
        });
        setUserFormErrors({});
        setShowUserPassword(false);
    };

    const openCreateUserModal = () => {
        setEditingUser(null);
        resetUserForm();
        setIsUserModalOpen(true);
    };

    const openEditUserModal = (user: RestaurantUser) => {
        setEditingUser(user);
        setUserForm({
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            is_active: user.is_active,
        });
        setUserFormErrors({});
        setShowUserPassword(false);
        setIsUserModalOpen(true);
    };

    const closeUserModal = () => {
        setIsUserModalOpen(false);
        setEditingUser(null);
        resetUserForm();
    };

    const handleUserFormSubmit = () => {
        setUserFormProcessing(true);
        setUserFormErrors({});

        const url = editingUser
            ? route('restaurants.users.update', { restaurant: restaurant.id, restaurantUser: editingUser.id })
            : route('restaurants.users.store', { restaurant: restaurant.id });

        // Preparar datos
        const submitData = {
            name: userForm.name,
            email: userForm.email,
            is_active: userForm.is_active,
            ...(userForm.password && {
                password: userForm.password,
                password_confirmation: userForm.password_confirmation,
            }),
        };

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                closeUserModal();
                showNotification.success(editingUser ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente');
            },
            onError: (errors: Record<string, string>) => {
                setUserFormErrors(errors);
            },
            onFinish: () => {
                setUserFormProcessing(false);
            },
        };

        if (editingUser) {
            router.put(url, submitData, options);
        } else {
            router.post(url, submitData, options);
        }
    };

    const openDeleteUserDialog = (user: RestaurantUser) => {
        setUserToDelete(user);
        setShowDeleteUserDialog(true);
    };

    const closeDeleteUserDialog = () => {
        setUserToDelete(null);
        setShowDeleteUserDialog(false);
        setIsDeletingUser(false);
    };

    const handleDeleteUser = () => {
        if (!userToDelete) return;

        setIsDeletingUser(true);
        router.delete(route('restaurants.users.destroy', { restaurant: restaurant.id, restaurantUser: userToDelete.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteUserDialog();
                showNotification.success('Usuario eliminado exitosamente');
            },
            onError: () => {
                setIsDeletingUser(false);
                showNotification.error('Error al eliminar el usuario');
            },
        });
    };

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

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Tiempo Estimado de Entrega (min)" error={errors.estimated_delivery_time}>
                        <Input
                            id="estimated_delivery_time"
                            type="number"
                            min="1"
                            value={data.estimated_delivery_time}
                            onChange={(e) => setData('estimated_delivery_time', e.target.value)}
                            placeholder={PLACEHOLDERS.estimatedTime}
                        />
                    </FormField>

                    <FormField label="Tiempo Estimado de Pickup (min)" error={errors.estimated_pickup_time}>
                        <Input
                            id="estimated_pickup_time"
                            type="number"
                            min="1"
                            value={data.estimated_pickup_time}
                            onChange={(e) => setData('estimated_pickup_time', e.target.value)}
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

                {/* Usuarios del Restaurante */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={UserCog} title="Usuarios del Restaurante" description="Usuarios con acceso al panel del restaurante">
                            <div className="flex items-center justify-between mb-4">
                                <p className="text-sm text-muted-foreground">
                                    {restaurant_users.length} usuario(s) registrado(s)
                                </p>
                                <Button type="button" variant="outline" size="sm" onClick={openCreateUserModal}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar Usuario
                                </Button>
                            </div>
                            {restaurant_users.length > 0 ? (
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Nombre</TableHead>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead className="w-[100px]">Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {restaurant_users.map((user) => (
                                                <TableRow key={user.id}>
                                                    <TableCell className="font-medium">{user.name}</TableCell>
                                                    <TableCell>{user.email}</TableCell>
                                                    <TableCell>
                                                        <StatusBadge
                                                            status={user.is_active ? 'active' : 'inactive'}
                                                            configs={ACTIVE_STATUS_CONFIGS}
                                                            className="text-xs"
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8"
                                                                onClick={() => openEditUserModal(user)}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 text-destructive hover:text-destructive"
                                                                onClick={() => openDeleteUserDialog(user)}
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <Users className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                    <p className="mt-2 text-sm text-muted-foreground">No hay usuarios asignados a este restaurante</p>
                                    <Button type="button" variant="outline" size="sm" className="mt-4" onClick={openCreateUserModal}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear primer usuario
                                    </Button>
                                </div>
                            )}
                        </FormSection>
                    </CardContent>
                </Card>

                {/* Modal de Crear/Editar Usuario - Fuera del formulario principal usando portal */}
                <Dialog open={isUserModalOpen} onOpenChange={(open) => !open && closeUserModal()}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>{editingUser ? 'Editar Usuario' : 'Nuevo Usuario'}</DialogTitle>
                            <DialogDescription>
                                {editingUser
                                    ? 'Modifica los datos del usuario del restaurante'
                                    : 'Crea un nuevo usuario con acceso al panel del restaurante'}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <FormField label="Nombre" error={userFormErrors.name} required>
                                <Input
                                    value={userForm.name}
                                    onChange={(e) => setUserForm({ ...userForm, name: e.target.value })}
                                    placeholder="Nombre completo"
                                    autoComplete={AUTOCOMPLETE.name}
                                />
                            </FormField>

                            <FormField label="Correo Electronico" error={userFormErrors.email} required>
                                <div className="relative">
                                    <Mail className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        type="email"
                                        value={userForm.email}
                                        onChange={(e) => setUserForm({ ...userForm, email: e.target.value })}
                                        placeholder={PLACEHOLDERS.email}
                                        className="pl-10"
                                        autoComplete={AUTOCOMPLETE.email}
                                    />
                                </div>
                            </FormField>

                            <FormField
                                label={editingUser ? 'Nueva Contrasena (opcional)' : 'Contrasena'}
                                error={userFormErrors.password}
                                required={!editingUser}
                                description={editingUser ? 'Dejar en blanco para mantener la actual' : 'Minimo 8 caracteres'}
                            >
                                <div className="relative">
                                    <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        type={showUserPassword ? 'text' : 'password'}
                                        value={userForm.password}
                                        onChange={(e) => setUserForm({ ...userForm, password: e.target.value })}
                                        placeholder={PLACEHOLDERS.password}
                                        className="pr-10 pl-10"
                                        autoComplete={AUTOCOMPLETE.newPassword}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="absolute top-1 right-1 h-8 w-8 p-0"
                                        onClick={() => setShowUserPassword(!showUserPassword)}
                                    >
                                        {showUserPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </Button>
                                </div>
                            </FormField>

                            {(userForm.password || !editingUser) && (
                                <FormField label="Confirmar Contrasena" error={userFormErrors.password_confirmation} required={!editingUser}>
                                    <div className="relative">
                                        <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            type={showUserPassword ? 'text' : 'password'}
                                            value={userForm.password_confirmation}
                                            onChange={(e) => setUserForm({ ...userForm, password_confirmation: e.target.value })}
                                            placeholder={PLACEHOLDERS.password}
                                            className="pl-10"
                                            autoComplete={AUTOCOMPLETE.newPassword}
                                        />
                                    </div>
                                </FormField>
                            )}

                            <div className="flex items-center justify-between">
                                <Label htmlFor="user_is_active" className="cursor-pointer">
                                    Usuario Activo
                                </Label>
                                <Switch
                                    id="user_is_active"
                                    checked={userForm.is_active}
                                    onCheckedChange={(checked) => setUserForm({ ...userForm, is_active: checked })}
                                />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={closeUserModal}>
                                    Cancelar
                                </Button>
                                <Button type="button" onClick={handleUserFormSubmit} disabled={userFormProcessing}>
                                    {userFormProcessing ? 'Guardando...' : editingUser ? 'Actualizar' : 'Crear Usuario'}
                                </Button>
                            </DialogFooter>
                        </div>
                    </DialogContent>
                </Dialog>

                {/* Dialog de Confirmacion de Eliminar Usuario */}
                <DeleteConfirmationDialog
                    isOpen={showDeleteUserDialog}
                    onClose={closeDeleteUserDialog}
                    onConfirm={handleDeleteUser}
                    isDeleting={isDeletingUser}
                    entityName={userToDelete?.name || ''}
                    entityType="el usuario"
                />

                {/* Motoristas del Restaurante (Solo visualizacion - gestion desde /drivers) */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Truck} title="Motoristas del Restaurante" description="Motoristas asignados a este restaurante. Gestionalos desde la seccion de Motoristas.">
                            <div className="flex items-center justify-between mb-4">
                                <p className="text-sm text-muted-foreground">
                                    {drivers.length} motorista(s) asignado(s)
                                </p>
                                <Link href={`/drivers?restaurant_id=${restaurant.id}`}>
                                    <Button type="button" variant="outline" size="sm">
                                        <Truck className="mr-2 h-4 w-4" />
                                        Gestionar Motoristas
                                    </Button>
                                </Link>
                            </div>
                            {drivers.length > 0 ? (
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Nombre</TableHead>
                                                <TableHead>Telefono</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Disponibilidad</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {drivers.slice(0, 5).map((driver) => (
                                                <TableRow key={driver.id}>
                                                    <TableCell className="font-medium">{driver.name}</TableCell>
                                                    <TableCell>{driver.phone || 'N/A'}</TableCell>
                                                    <TableCell>
                                                        <StatusBadge
                                                            status={driver.is_active ? 'active' : 'inactive'}
                                                            configs={ACTIVE_STATUS_CONFIGS}
                                                            className="text-xs"
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={driver.is_available ? 'default' : 'secondary'}
                                                            className={`text-xs ${driver.is_available ? 'bg-green-600' : ''}`}
                                                        >
                                                            {driver.is_available ? 'Disponible' : 'No disponible'}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                    {drivers.length > 5 && (
                                        <div className="p-3 text-center border-t">
                                            <Link href={`/drivers?restaurant_id=${restaurant.id}`}>
                                                <Button type="button" variant="ghost" size="sm">
                                                    Ver {drivers.length - 5} motoristas mas
                                                </Button>
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <Truck className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                    <p className="mt-2 text-sm text-muted-foreground">No hay motoristas asignados a este restaurante</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Asigna motoristas desde la seccion <Link href="/drivers" className="text-primary underline">Motoristas</Link>
                                    </p>
                                </div>
                            )}
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </EditPageLayout>
    );
}
