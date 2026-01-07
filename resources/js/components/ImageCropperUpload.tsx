import ReactCrop, { type Crop, centerCrop, makeAspectCrop } from 'react-image-crop';
import 'react-image-crop/dist/ReactCrop.css';
import { ImageIcon, Crop as CropIcon, Trash2, Check, X, Eye } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { Button } from './ui/button';
import { FormError } from '@/components/ui/form-error';
import { LabelWithRequired } from '@/components/LabelWithRequired';
import { showNotification } from '@/hooks/useNotifications';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

interface ImageCropperUploadProps {
    label?: string;
    currentImage?: string | null;
    onImageChange: (file: File | null) => void;
    error?: string;
    maxSizeMB?: number;
    acceptedFormats?: string[];
    required?: boolean;
    aspectRatio: number;
    aspectLabel?: string;
}

function centerAspectCrop(mediaWidth: number, mediaHeight: number, aspect: number): Crop {
    return centerCrop(
        makeAspectCrop(
            {
                unit: '%',
                width: 90,
            },
            aspect,
            mediaWidth,
            mediaHeight
        ),
        mediaWidth,
        mediaHeight
    );
}

async function getCroppedImg(image: HTMLImageElement, crop: Crop): Promise<Blob> {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    if (!ctx) {
        throw new Error('No 2d context');
    }

    const scaleX = image.naturalWidth / image.width;
    const scaleY = image.naturalHeight / image.height;

    const pixelCrop = {
        x: (crop.x / 100) * image.width * scaleX,
        y: (crop.y / 100) * image.height * scaleY,
        width: (crop.width / 100) * image.width * scaleX,
        height: (crop.height / 100) * image.height * scaleY,
    };

    canvas.width = pixelCrop.width;
    canvas.height = pixelCrop.height;

    ctx.drawImage(
        image,
        pixelCrop.x,
        pixelCrop.y,
        pixelCrop.width,
        pixelCrop.height,
        0,
        0,
        pixelCrop.width,
        pixelCrop.height
    );

    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('Canvas is empty'));
                }
            },
            'image/webp',
            0.85
        );
    });
}

export function ImageCropperUpload({
    label = 'Imagen',
    currentImage,
    onImageChange,
    error,
    maxSizeMB = 5,
    acceptedFormats = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/avif'],
    required = false,
    aspectRatio,
    aspectLabel,
}: ImageCropperUploadProps) {
    const [preview, setPreview] = useState<string | null>(currentImage || null);
    const [tempImage, setTempImage] = useState<string | null>(null);
    const [showCropper, setShowCropper] = useState(false);
    const [showPreviewModal, setShowPreviewModal] = useState(false);
    const [crop, setCrop] = useState<Crop>();
    const [isProcessing, setIsProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const imgRef = useRef<HTMLImageElement>(null);

    const onImageLoad = useCallback(
        (e: React.SyntheticEvent<HTMLImageElement>) => {
            const { width, height } = e.currentTarget;
            setCrop(centerAspectCrop(width, height, aspectRatio));
        },
        [aspectRatio]
    );

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
            const imageUrl = reader.result as string;
            setTempImage(imageUrl);
            setCrop(undefined);
            setShowCropper(true);
        };
        reader.readAsDataURL(file);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleCropConfirm = async () => {
        if (!imgRef.current || !crop) {
            return;
        }

        setIsProcessing(true);
        try {
            const croppedBlob = await getCroppedImg(imgRef.current, crop);
            const croppedFile = new File([croppedBlob], 'banner.webp', { type: 'image/webp' });

            const previewUrl = URL.createObjectURL(croppedBlob);
            setPreview(previewUrl);
            onImageChange(croppedFile);
            setShowCropper(false);
            setTempImage(null);
        } catch (e) {
            console.error('Error cropping image:', e);
            showNotification.error('Error al recortar la imagen');
        } finally {
            setIsProcessing(false);
        }
    };

    const handleCropCancel = () => {
        setShowCropper(false);
        setTempImage(null);
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

    const getAspectRatioLabel = () => {
        if (aspectLabel) return aspectLabel;
        if (aspectRatio === 16 / 9) return '16:9';
        if (aspectRatio === 3 / 4) return '3:4';
        if (aspectRatio === 4 / 3) return '4:3';
        if (aspectRatio === 9 / 16) return '9:16';
        if (aspectRatio === 1.91) return '1.91:1';
        return `${aspectRatio.toFixed(2)}:1`;
    };

    const getPreviewHeight = () => {
        if (aspectRatio < 1) {
            return 'h-72';
        }
        return 'h-48';
    };

    return (
        <div className="space-y-2">
            {label && <LabelWithRequired required={required}>{label}</LabelWithRequired>}

            <div className="flex flex-col items-center gap-4">
                <div className="relative w-full max-w-xs">
                    {preview ? (
                        <div className="group relative">
                            <img
                                src={preview}
                                alt="Preview"
                                className={`${getPreviewHeight()} w-full rounded-lg border border-border object-cover`}
                            />
                            <div className="absolute inset-0 flex items-center justify-center gap-2 rounded-lg bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                <Button type="button" variant="secondary" size="sm" onClick={() => setShowPreviewModal(true)}>
                                    <Eye className="h-4 w-4" />
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
                            className={`flex ${getPreviewHeight()} w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border transition-colors hover:border-primary hover:bg-accent/50`}
                        >
                            <ImageIcon className="h-8 w-8 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">Click para seleccionar imagen</p>
                            <p className="text-xs text-muted-foreground">
                                Ratio: {getAspectRatioLabel()} • Máx {maxSizeMB}MB
                            </p>
                        </button>
                    )}
                </div>

                <input ref={fileInputRef} type="file" accept={acceptedFormats.join(',')} onChange={handleFileSelect} className="hidden" />

                <FormError message={error} />
            </div>

            {/* Modal de recorte */}
            <Dialog open={showCropper} onOpenChange={(open) => !open && handleCropCancel()}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <CropIcon className="h-5 w-5" />
                            Recortar imagen ({getAspectRatioLabel()})
                        </DialogTitle>
                    </DialogHeader>

                    <div className="flex items-center justify-center rounded-lg bg-muted p-4">
                        {tempImage && (
                            <ReactCrop
                                crop={crop}
                                onChange={(_, percentCrop) => setCrop(percentCrop)}
                                aspect={aspectRatio}
                                className="max-h-[500px]"
                            >
                                <img
                                    ref={imgRef}
                                    src={tempImage}
                                    alt="Crop"
                                    onLoad={onImageLoad}
                                    style={{ maxHeight: '500px', maxWidth: '100%' }}
                                />
                            </ReactCrop>
                        )}
                    </div>

                    <p className="text-center text-sm text-muted-foreground">
                        Arrastra las esquinas o bordes para ajustar el área de recorte
                    </p>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button type="button" variant="outline" onClick={handleCropCancel} disabled={isProcessing}>
                            <X className="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                        <Button type="button" onClick={handleCropConfirm} disabled={isProcessing || !crop}>
                            {isProcessing ? (
                                <span className="flex items-center">
                                    <CropIcon className="mr-2 h-4 w-4 animate-pulse" />
                                    Procesando...
                                </span>
                            ) : (
                                <>
                                    <Check className="mr-2 h-4 w-4" />
                                    Aplicar recorte
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal de vista previa */}
            <Dialog open={showPreviewModal} onOpenChange={setShowPreviewModal}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Eye className="h-5 w-5" />
                            Vista previa
                        </DialogTitle>
                    </DialogHeader>

                    <div className="flex items-center justify-center">
                        {preview && (
                            <img
                                src={preview}
                                alt="Preview"
                                className="max-h-[70vh] w-auto rounded-lg"
                            />
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}
