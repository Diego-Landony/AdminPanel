import { CreatePageLayout } from '@/components/create-page-layout';
import { FormFieldError } from '@/components/FormFieldError';
import { TiptapEditor } from '@/components/TiptapEditor';
import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface LegalDocument {
    id: number;
    type: string;
    content_json: object;
    content_html: string;
}

interface EditTermsPageProps {
    document: LegalDocument | null;
}

export default function EditTerms({ document }: EditTermsPageProps) {
    const [isSaving, setIsSaving] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    const [formData, setFormData] = useState({
        content_json: document?.content_json || null,
        content_html: document?.content_html || '',
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

        router.put('/support/terms-and-conditions', formData, {
            onSuccess: () => {
                showNotification.success('Términos y condiciones actualizados');
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string[]>);
                showNotification.error('Error al guardar');
            },
            onFinish: () => setIsSaving(false),
        });
    };

    return (
        <CreatePageLayout
            title="Términos y Condiciones"
            backHref="/support/terms-and-conditions"
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSaving}
            pageTitle="Editar Términos y Condiciones"
        >
            <div className="space-y-2">
                <TiptapEditor content={formData.content_json} onChange={handleEditorChange} />
                <FormFieldError errors={errors} field="content_json" />
                <FormFieldError errors={errors} field="content_html" />
            </div>
        </CreatePageLayout>
    );
}
