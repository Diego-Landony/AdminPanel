import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import React, { useState } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageCropperUpload } from '@/components/ImageCropperUpload';
import { WeekdaySelector } from '@/components/WeekdaySelector';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Calendar, Image, Link2, Monitor, Smartphone } from 'lucide-react';

interface LinkOption {
    id: number;
    name: string;
}

interface LinkOptions {
    products: LinkOption[];
    combos: LinkOption[];
    categories: LinkOption[];
}

interface Banner {
    id: number;
    title: string;
    description: string | null;
    image: string;
    image_url: string | null;
    orientation: 'horizontal' | 'vertical';
    display_seconds: number;
    link_type: string | null;
    link_id: number | null;
    link_url: string | null;
    validity_type: 'permanent' | 'date_range' | 'weekdays';
    valid_from: string | null;
    valid_until: string | null;
    weekdays: number[] | null;
    is_active: boolean;
}

interface EditPageProps {
    banner: Banner;
    linkOptions: LinkOptions;
}

// Configuración de aspect ratios con dimensiones sugeridas
const ORIENTATION_CONFIG = {
    horizontal: {
        ratio: 16 / 9,
        label: '16:9',
        dimensions: '1280 × 720 px',
        description: 'Carrusel',
    },
    vertical: {
        ratio: 3 / 4,
        label: '3:4',
        dimensions: '1200 × 1600 px',
        description: 'Promociones',
    },
};

export default function BannerEdit({ banner, linkOptions }: EditPageProps) {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [imageKey, setImageKey] = useState(0);

    const [data, setData] = useState({
        title: banner.title,
        description: banner.description || '',
        image: null as File | null,
        orientation: banner.orientation,
        display_seconds: banner.display_seconds,
        link_type: banner.link_type || '',
        link_id: banner.link_id ? String(banner.link_id) : '',
        link_url: banner.link_url || '',
        validity_type: banner.validity_type,
        valid_from: banner.valid_from || '',
        valid_until: banner.valid_until || '',
        weekdays: banner.weekdays || [],
        is_active: banner.is_active,
    });

    const [currentImageUrl, setCurrentImageUrl] = useState<string | null>(banner.image_url);

    const handleChange = (field: string, value: unknown) => {
        setData((prev) => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const handleOrientationChange = (orientation: 'horizontal' | 'vertical') => {
        if (orientation !== data.orientation) {
            // Si cambia la orientación, limpiar la imagen porque el aspect ratio es diferente
            setData((prev) => ({
                ...prev,
                orientation,
                image: null,
            }));
            setCurrentImageUrl(null);
            // Forzar re-render del cropper
            setImageKey((prev) => prev + 1);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('title', data.title);
        formData.append('description', data.description || '');
        if (data.image) {
            formData.append('image', data.image);
        }
        formData.append('orientation', data.orientation);
        formData.append('display_seconds', String(data.display_seconds));
        formData.append('link_type', data.link_type || '');
        formData.append('link_id', data.link_id || '');
        formData.append('link_url', data.link_url || '');
        formData.append('validity_type', data.validity_type);
        formData.append('valid_from', data.valid_from || '');
        formData.append('valid_until', data.valid_until || '');
        if (data.weekdays.length > 0) {
            data.weekdays.forEach((day) => formData.append('weekdays[]', String(day)));
        }
        formData.append('is_active', data.is_active ? '1' : '0');

        router.post(route('marketing.banners.update', banner.id), formData, {
            forceFormData: true,
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setProcessing(false);
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
            onFinish: () => setProcessing(false),
        });
    };

    const getLinkOptions = () => {
        switch (data.link_type) {
            case 'product':
                return linkOptions.products;
            case 'combo':
                return linkOptions.combos;
            case 'category':
                return linkOptions.categories;
            default:
                return [];
        }
    };

    const currentConfig = ORIENTATION_CONFIG[data.orientation as keyof typeof ORIENTATION_CONFIG];
    const currentAspectRatio = currentConfig.ratio;
    const aspectLabel = currentConfig.label;

    return (
        <CreatePageLayout
            title="Editar Banner"
            backHref={route('marketing.banners.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar Cambios"
            processing={processing}
            pageTitle={`Editar: ${banner.title}`}
        >
            <div className="space-y-6">
                <Card className="border-0 shadow-none">
                    <CardContent className="p-0">
                        <FormSection icon={Image} title="Información Básica" description="Datos principales del banner">
                            <div className="space-y-6">
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                        Banner Activo
                                    </Label>
                                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => handleChange('is_active', checked)} />
                                </div>

                                <FormField label="Título" error={errors.title} required>
                                    <Input id="title" type="text" value={data.title} onChange={(e) => handleChange('title', e.target.value)} maxLength={100} />
                                </FormField>

                                <FormField label="Descripción" error={errors.description}>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => handleChange('description', e.target.value)}
                                        maxLength={255}
                                        rows={2}
                                    />
                                </FormField>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card className="border-0 shadow-none">
                    <CardContent className="p-0">
                        <FormSection icon={Monitor} title="Configuración de Display" description="Orientación, imagen y tiempo de visualización">
                            <div className="space-y-6">
                                <FormField label="Orientación" error={errors.orientation} required>
                                    <div className="flex gap-4">
                                        <button
                                            type="button"
                                            onClick={() => handleOrientationChange('horizontal')}
                                            className={`flex flex-1 flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors ${
                                                data.orientation === 'horizontal' ? 'border-primary bg-primary/5' : 'border-input hover:bg-accent'
                                            }`}
                                        >
                                            <Monitor className="h-8 w-8" />
                                            <span className="text-sm font-medium">Horizontal</span>
                                            <span className="text-xs text-muted-foreground">
                                                {ORIENTATION_CONFIG.horizontal.label} • {ORIENTATION_CONFIG.horizontal.description}
                                            </span>
                                            <span className="text-xs text-muted-foreground">{ORIENTATION_CONFIG.horizontal.dimensions}</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleOrientationChange('vertical')}
                                            className={`flex flex-1 flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors ${
                                                data.orientation === 'vertical' ? 'border-primary bg-primary/5' : 'border-input hover:bg-accent'
                                            }`}
                                        >
                                            <Smartphone className="h-8 w-8" />
                                            <span className="text-sm font-medium">Vertical</span>
                                            <span className="text-xs text-muted-foreground">
                                                {ORIENTATION_CONFIG.vertical.label} • {ORIENTATION_CONFIG.vertical.description}
                                            </span>
                                            <span className="text-xs text-muted-foreground">{ORIENTATION_CONFIG.vertical.dimensions}</span>
                                        </button>
                                    </div>
                                </FormField>

                                <ImageCropperUpload
                                    key={imageKey}
                                    label="Imagen del Banner"
                                    currentImage={currentImageUrl}
                                    onImageChange={(file) => handleChange('image', file)}
                                    error={errors.image}
                                    maxSizeMB={5}
                                    aspectRatio={currentAspectRatio}
                                    aspectLabel={aspectLabel}
                                />

                                <FormField label="Tiempo de visualización (segundos)" error={errors.display_seconds} required>
                                    <Input
                                        id="display_seconds"
                                        type="number"
                                        min={1}
                                        max={30}
                                        value={data.display_seconds}
                                        onChange={(e) => handleChange('display_seconds', e.target.value === '' ? '' : parseInt(e.target.value) || '')}
                                    />
                                </FormField>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card className="border-0 shadow-none">
                    <CardContent className="p-0">
                        <FormSection icon={Link2} title="Enlace (Opcional)" description="Acción al tocar el banner">
                            <div className="space-y-4">
                                <FormField label="Tipo de enlace" error={errors.link_type}>
                                    <Select value={data.link_type} onValueChange={(value) => handleChange('link_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sin enlace" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Sin enlace</SelectItem>
                                            <SelectItem value="product">Producto</SelectItem>
                                            <SelectItem value="combo">Combo</SelectItem>
                                            <SelectItem value="category">Categoría</SelectItem>
                                            <SelectItem value="url">URL externa</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>

                                {data.link_type && data.link_type !== 'none' && data.link_type !== 'url' && (
                                    <FormField label="Seleccionar elemento" error={errors.link_id} required>
                                        <Select value={data.link_id} onValueChange={(value) => handleChange('link_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {getLinkOptions().map((option) => (
                                                    <SelectItem key={option.id} value={String(option.id)}>
                                                        {option.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </FormField>
                                )}

                                {data.link_type === 'url' && (
                                    <FormField label="URL" error={errors.link_url} required>
                                        <Input
                                            id="link_url"
                                            type="url"
                                            value={data.link_url}
                                            onChange={(e) => handleChange('link_url', e.target.value)}
                                            placeholder="https://ejemplo.com"
                                        />
                                    </FormField>
                                )}
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card className="border-0 shadow-none">
                    <CardContent className="p-0">
                        <FormSection icon={Calendar} title="Validez" description="Cuándo se muestra el banner">
                            <div className="space-y-4">
                                <FormField label="Tipo de validez" error={errors.validity_type} required>
                                    <Select value={data.validity_type} onValueChange={(value) => handleChange('validity_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="permanent">Permanente</SelectItem>
                                            <SelectItem value="date_range">Rango de fechas</SelectItem>
                                            <SelectItem value="weekdays">Días de la semana</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>

                                {data.validity_type === 'date_range' && (
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <FormField label="Fecha de inicio" error={errors.valid_from} required>
                                            <Input
                                                id="valid_from"
                                                type="date"
                                                value={data.valid_from}
                                                onChange={(e) => handleChange('valid_from', e.target.value)}
                                            />
                                        </FormField>
                                        <FormField label="Fecha de fin" error={errors.valid_until} required>
                                            <Input
                                                id="valid_until"
                                                type="date"
                                                value={data.valid_until}
                                                onChange={(e) => handleChange('valid_until', e.target.value)}
                                            />
                                        </FormField>
                                    </div>
                                )}

                                {data.validity_type === 'weekdays' && (
                                    <WeekdaySelector
                                        value={data.weekdays}
                                        onChange={(days) => handleChange('weekdays', days)}
                                        error={errors.weekdays}
                                        required
                                    />
                                )}
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </CreatePageLayout>
    );
}
