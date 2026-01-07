import { CreatePageLayout } from '@/components/create-page-layout';
import { FormFieldError } from '@/components/FormFieldError';
import { TiptapEditor } from '@/components/TiptapEditor';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { showNotification } from '@/hooks/useNotifications';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface LegalDocument {
    id: number;
    type: string;
    version: string;
    content_json: object;
    content_html: string;
    is_published: boolean;
}

interface EditPrivacyPageProps {
    document: LegalDocument | null;
    suggestedVersion: string;
    latestContent?: object | null;
}

export default function EditPrivacy({ document, suggestedVersion, latestContent }: EditPrivacyPageProps) {
    const isEditing = !!document;
    const [isSaving, setIsSaving] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    const [formData, setFormData] = useState({
        version: document?.version || suggestedVersion,
        content_json: document?.content_json || latestContent || null,
        content_html: document?.content_html || '',
        publish: false,
    });

    const handleEditorChange = (json: object, html: string) => {
        setFormData((prev) => ({
            ...prev,
            content_json: json,
            content_html: html,
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);
        setErrors({});

        const url = isEditing ? `/support/privacy-policy/${document.id}` : '/support/privacy-policy';

        const method = isEditing ? 'put' : 'post';

        router[method](url, formData, {
            onSuccess: () => {
                showNotification.success(isEditing ? 'Documento actualizado' : 'Documento creado');
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string[]>);
                showNotification.error('Error al guardar el documento');
            },
            onFinish: () => setIsSaving(false),
        });
    };

    return (
        <AppLayout>
            <Head title={isEditing ? 'Editar Política de Privacidad' : 'Crear Política de Privacidad'} />

            <CreatePageLayout
                title={isEditing ? 'Editar Política de Privacidad' : 'Nueva versión de Política de Privacidad'}
                description="Escribe el contenido de la política de privacidad usando el editor"
                backHref="/support/privacy-policy"
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="version">Versión</Label>
                        <Input
                            id="version"
                            value={formData.version}
                            onChange={(e) => setFormData((prev) => ({ ...prev, version: e.target.value }))}
                            placeholder="1.0"
                            className="max-w-xs"
                        />
                        <FormFieldError errors={errors} field="version" />
                    </div>

                    <div className="space-y-2">
                        <Label>Contenido</Label>
                        <TiptapEditor content={formData.content_json} onChange={handleEditorChange} />
                        <FormFieldError errors={errors} field="content_json" />
                        <FormFieldError errors={errors} field="content_html" />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="publish"
                            checked={formData.publish}
                            onCheckedChange={(checked) => setFormData((prev) => ({ ...prev, publish: checked as boolean }))}
                        />
                        <Label htmlFor="publish" className="text-sm font-normal">
                            Publicar inmediatamente (esto despublicará la versión anterior)
                        </Label>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => router.visit('/support/privacy-policy')}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={isSaving}>
                            {isSaving ? 'Guardando...' : isEditing ? 'Actualizar' : 'Crear'}
                        </Button>
                    </div>
                </form>
            </CreatePageLayout>
        </AppLayout>
    );
}
