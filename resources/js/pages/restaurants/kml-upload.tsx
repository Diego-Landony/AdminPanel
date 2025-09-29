import { useForm } from '@inertiajs/react';
import { FileText, Upload, Trash2, Eye, MapPin, Building2 } from 'lucide-react';
import React, { useState } from 'react';

import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/react';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';

interface Restaurant {
    id: number;
    name: string;
    address: string;
    has_geofence: boolean;
    geofence_kml: string | null;
}

interface KMLUploadPageProps {
    restaurant: Restaurant;
}

export default function KMLUpload({ restaurant }: KMLUploadPageProps) {
    const [isDragging, setIsDragging] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        kml_file: null as File | null,
    });

    const handleFileSelect = (file: File) => {
        setData('kml_file', file);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);

        const files = Array.from(e.dataTransfer.files);
        const kmlFile = files.find(file => file.name.toLowerCase().endsWith('.kml'));

        if (kmlFile) {
            handleFileSelect(kmlFile);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.kml_file) return;

        post(route('restaurants.kml.upload', restaurant.id), {
            forceFormData: true,
        });
    };

    const handleRemoveKML = () => {
        router.delete(route('restaurants.kml.remove', restaurant.id));
    };

    const handlePreview = () => {
        router.get(route('restaurants.kml.preview', restaurant.id));
    };

    return (
        <EditPageLayout
            title="Gestión de Geocerca KML"
            description={`Administra el archivo KML para ${restaurant.name}`}
            backHref={route('restaurants.edit', restaurant.id)}
            backLabel="Volver a Editar"
            onSubmit={handleSubmit}
            submitLabel={data.kml_file ? 'Cargar KML' : 'Seleccionar Archivo'}
            processing={processing}
            disabled={!data.kml_file}
            pageTitle={`Geocerca KML - ${restaurant.name}`}
        >
            {/* Restaurant Info */}
            <FormSection
                icon={Building2}
                title="Información del Restaurante"
                description="Detalles del restaurante y estado actual de la geocerca"
            >
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <div className="mt-1">
                            {restaurant.has_geofence ? (
                                <Badge className="bg-green-100 text-green-800 border-green-200">
                                    <FileText className="h-3 w-3 mr-1" />
                                    KML Cargado
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="text-gray-600">
                                    Sin KML
                                </Badge>
                            )}
                        </div>
                    </div>
                </div>
            </FormSection>

            {restaurant.has_geofence && (
                <FormSection
                    icon={FileText}
                    title="KML Actual"
                    description="Administrar el archivo KML existente"
                >
                    <div className="flex flex-col sm:flex-row gap-3">
                        <Button
                            type="button"
                            onClick={handlePreview}
                            variant="outline"
                            className="flex items-center gap-2"
                        >
                            <Eye className="h-4 w-4" />
                            Previsualizar
                        </Button>
                        <Button
                            type="button"
                            onClick={handleRemoveKML}
                            variant="destructive"
                            className="flex items-center gap-2"
                        >
                            <Trash2 className="h-4 w-4" />
                            Eliminar KML
                        </Button>
                    </div>
                </FormSection>
            )}

            {/* File Upload */}
            <FormSection
                icon={Upload}
                title={restaurant.has_geofence ? 'Actualizar KML' : 'Cargar KML'}
                description="Arrastra y suelta un archivo .kml o haz clic para seleccionar"
            >
                <div
                    className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                        isDragging
                            ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                            : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                    }`}
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                >
                    {data.kml_file ? (
                        <div className="space-y-2">
                            <FileText className="h-12 w-12 mx-auto text-green-500" />
                            <p className="text-lg font-medium text-green-700 dark:text-green-400">
                                {data.kml_file.name}
                            </p>
                            <p className="text-sm text-gray-500">
                                {(data.kml_file.size / 1024).toFixed(2)} KB
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setData('kml_file', null)}
                            >
                                Cambiar archivo
                            </Button>
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <Upload className="h-12 w-12 mx-auto text-gray-400" />
                            <p className="text-lg font-medium text-gray-700 dark:text-gray-300">
                                Arrastra un archivo KML aquí
                            </p>
                            <p className="text-sm text-gray-500">
                                o haz clic para seleccionar
                            </p>
                        </div>
                    )}

                    <FormField error={errors.kml_file} className="mt-4">
                        <Input
                            type="file"
                            accept=".kml"
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) handleFileSelect(file);
                            }}
                            className="hidden"
                            id="kml-file-input"
                        />
                        <label
                            htmlFor="kml-file-input"
                            className="inline-block cursor-pointer"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                className="mt-2"
                                asChild
                            >
                                <span>Seleccionar archivo</span>
                            </Button>
                        </label>
                    </FormField>
                </div>
            </FormSection>

            {/* Information */}
            <FormSection
                icon={FileText}
                title="Información sobre archivos KML"
                description="Requisitos y especificaciones para los archivos KML"
            >
                <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <ul className="space-y-2 text-sm">
                        <li className="flex items-start gap-2">
                            <span className="text-blue-600 dark:text-blue-400">•</span>
                            <span>Los archivos KML deben contener datos de polígono para definir la geocerca</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-blue-600 dark:text-blue-400">•</span>
                            <span>Tamaño máximo: 2MB</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-blue-600 dark:text-blue-400">•</span>
                            <span>Formato soportado: .kml (Google Earth)</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-blue-600 dark:text-blue-400">•</span>
                            <span>El archivo reemplazará cualquier geocerca existente</span>
                        </li>
                    </ul>
                </div>
            </FormSection>
        </EditPageLayout>
    );
}