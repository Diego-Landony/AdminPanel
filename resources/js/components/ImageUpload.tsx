import { ImageIcon, Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from './ui/button';
import { FormError } from '@/components/ui/form-error';
import { LabelWithRequired } from '@/components/LabelWithRequired';
import { showNotification } from '@/hooks/useNotifications';

interface ImageUploadProps {
    label?: string;
    currentImage?: string | null;
    onImageChange: (file: File | null, previewUrl: string | null) => void;
    error?: string;
    maxSizeMB?: number;
    acceptedFormats?: string[];
    required?: boolean;
}

export function ImageUpload({
    label = 'Imagen',
    currentImage,
    onImageChange,
    error,
    maxSizeMB = 5,
    acceptedFormats = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/svg+xml', 'image/avif'],
    required = false,
}: ImageUploadProps) {
    const [preview, setPreview] = useState<string | null>(currentImage || null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (currentImage && !preview) {
            setPreview(currentImage);
        }
    }, [currentImage, preview]);

    const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        if (!acceptedFormats.includes(file.type)) {
            showNotification.error(`Formato no válido. Usa: ${acceptedFormats.map((f) => f.split('/')[1]).join(', ')}`);
            return;
        }

        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxSizeMB) {
            showNotification.error(`La imagen debe ser menor a ${maxSizeMB}MB. Tamaño actual: ${fileSizeMB.toFixed(2)}MB`);
            return;
        }

        const reader = new FileReader();
        reader.onloadend = () => {
            const previewUrl = reader.result as string;
            setPreview(previewUrl);
            onImageChange(file, previewUrl);
        };
        reader.readAsDataURL(file);
    };

    const handleRemoveImage = () => {
        setPreview(null);
        onImageChange(null, null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleClickUpload = () => {
        fileInputRef.current?.click();
    };

    return (
        <div className="space-y-2">
            {label && <LabelWithRequired required={required}>{label}</LabelWithRequired>}

            <div className="flex flex-col items-center gap-4">
                <div className="relative w-full max-w-xs">
                    {preview ? (
                        <div className="group relative">
                            <img src={preview} alt="Preview" className="h-48 w-full rounded-lg border border-border object-cover" />
                            <div className="absolute inset-0 flex items-center justify-center gap-2 rounded-lg bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                <Button type="button" variant="secondary" size="sm" onClick={handleClickUpload}>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Cambiar
                                </Button>
                                <Button type="button" variant="destructive" size="sm" onClick={handleRemoveImage}>
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={handleClickUpload}
                            className="flex h-48 w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border transition-colors hover:border-primary hover:bg-accent/50"
                        >
                            <ImageIcon className="h-8 w-8 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">Click para seleccionar imagen</p>
                            <p className="text-xs text-muted-foreground">
                                Máx {maxSizeMB}MB • {acceptedFormats.map((f) => f.split('/')[1].toUpperCase()).join(', ')}
                            </p>
                        </button>
                    )}
                </div>

                <input ref={fileInputRef} type="file" accept={acceptedFormats.join(',')} onChange={handleFileSelect} className="hidden" />

                <FormError message={error} />
            </div>
        </div>
    );
}
