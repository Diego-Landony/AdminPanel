import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import { ImageIcon, Trash2, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from './ui/button';
import { Label } from './ui/label';

interface ImageUploadProps {
    label?: string;
    currentImage?: string | null;
    onImageChange: (imageUrl: string | null) => void;
    error?: string;
    uploadEndpoint?: string;
    maxSizeMB?: number;
    acceptedFormats?: string[];
    required?: boolean;
}

export function ImageUpload({
    label = 'Imagen',
    currentImage,
    onImageChange,
    error,
    uploadEndpoint = '/api/upload/image',
    maxSizeMB = 5,
    acceptedFormats = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
    required = false,
}: ImageUploadProps) {
    const [isUploading, setIsUploading] = useState(false);
    const [preview, setPreview] = useState<string | null>(currentImage || null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
        console.log('=== INICIO IMAGE UPLOAD ===');
        const file = event.target.files?.[0];
        console.log('1. Archivo seleccionado:', file);

        if (!file) {
            console.log('❌ No hay archivo, abortando');
            return;
        }

        // Validar formato
        console.log('2. Validando formato. Type:', file.type);
        console.log('   Formatos aceptados:', acceptedFormats);
        if (!acceptedFormats.includes(file.type)) {
            console.error('❌ Formato no válido');
            showNotification.error(
                `Formato no válido. Usa: ${acceptedFormats.map(f => f.split('/')[1]).join(', ')}`
            );
            return;
        }
        console.log('✅ Formato válido');

        // Validar tamaño
        const fileSizeMB = file.size / (1024 * 1024);
        console.log('3. Validando tamaño. Size:', fileSizeMB.toFixed(2), 'MB. Max:', maxSizeMB, 'MB');
        if (fileSizeMB > maxSizeMB) {
            console.error('❌ Tamaño excedido');
            showNotification.error(`La imagen debe ser menor a ${maxSizeMB}MB. Tamaño actual: ${fileSizeMB.toFixed(2)}MB`);
            return;
        }
        console.log('✅ Tamaño válido');

        // Crear preview local
        console.log('4. Creando preview local...');
        const reader = new FileReader();
        reader.onloadend = () => {
            console.log('✅ Preview creado');
            setPreview(reader.result as string);
        };
        reader.readAsDataURL(file);

        // Subir imagen
        console.log('5. Preparando subida de imagen...');
        setIsUploading(true);
        const formData = new FormData();
        formData.append('image', file);
        console.log('   FormData creado');
        console.log('   Upload endpoint:', uploadEndpoint);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            console.log('6. CSRF Token:', csrfToken ? 'Presente' : 'NO ENCONTRADO');
            console.log('7. Enviando fetch request...');

            const response = await fetch(uploadEndpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            console.log('8. Response recibida. Status:', response.status, response.statusText);
            console.log('   Response OK:', response.ok);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Response no OK. Error text:', errorText);
                throw new Error('Error al subir la imagen');
            }

            const data = await response.json();
            console.log('9. Data parseada:', data);

            if (data.url) {
                console.log('✅ URL recibida:', data.url);
                onImageChange(data.url);
                showNotification.success('Imagen subida correctamente');
            } else {
                console.error('❌ No se recibió URL en la respuesta');
                throw new Error('No se recibió URL de la imagen');
            }
        } catch (error) {
            console.error('❌ Error en upload:', error);
            showNotification.error('Error al subir la imagen. Intenta de nuevo.');
            setPreview(currentImage || null);
        } finally {
            console.log('🏁 Upload finalizado');
            setIsUploading(false);
            console.log('=== FIN IMAGE UPLOAD ===');
        }
    };

    const handleRemoveImage = () => {
        setPreview(null);
        onImageChange(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleClickUpload = () => {
        fileInputRef.current?.click();
    };

    return (
        <div className="space-y-2">
            {label && (
                <Label>
                    {label}
                    {required && <span className="text-destructive ml-1">*</span>}
                </Label>
            )}

            <div className="flex flex-col gap-4">
                {/* Preview o placeholder */}
                <div className="relative w-full max-w-xs">
                    {preview ? (
                        <div className="relative group">
                            <img
                                src={preview}
                                alt="Preview"
                                className="w-full h-48 object-cover rounded-lg border border-border"
                            />
                            <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    onClick={handleClickUpload}
                                    disabled={isUploading}
                                >
                                    <Upload className="h-4 w-4 mr-2" />
                                    Cambiar
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    onClick={handleRemoveImage}
                                    disabled={isUploading}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={handleClickUpload}
                            disabled={isUploading}
                            className="w-full h-48 border-2 border-dashed border-border rounded-lg flex flex-col items-center justify-center gap-2 hover:border-primary hover:bg-accent/50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isUploading ? (
                                <>
                                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                    <p className="text-sm text-muted-foreground">Subiendo imagen...</p>
                                </>
                            ) : (
                                <>
                                    <ImageIcon className="h-8 w-8 text-muted-foreground" />
                                    <p className="text-sm text-muted-foreground">Click para subir imagen</p>
                                    <p className="text-xs text-muted-foreground">
                                        Máx {maxSizeMB}MB • {acceptedFormats.map(f => f.split('/')[1].toUpperCase()).join(', ')}
                                    </p>
                                </>
                            )}
                        </button>
                    )}
                </div>

                {/* Input oculto */}
                <input
                    ref={fileInputRef}
                    type="file"
                    accept={acceptedFormats.join(',')}
                    onChange={handleFileSelect}
                    className="hidden"
                />

                {/* Error */}
                {error && (
                    <p className="text-sm text-destructive">{error}</p>
                )}
            </div>
        </div>
    );
}
