import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Edit, Shield } from 'lucide-react';

interface LegalDocument {
    id: number;
    type: string;
    content_json: object;
    content_html: string;
}

interface PrivacyPageProps {
    document: LegalDocument | null;
}

export default function PrivacyIndex({ document }: PrivacyPageProps) {
    return (
        <AppLayout>
            <Head title="Política de Privacidad" />

            <div className="mx-auto flex h-full w-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Shield className="h-5 w-5 text-primary" />
                        </div>
                        <h1 className="text-3xl font-bold tracking-tight">Política de Privacidad</h1>
                    </div>
                    <Link href="/support/privacy-policy/edit">
                        <Button>
                            <Edit className="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        {document?.content_html ? (
                            <div
                                className="prose prose-sm dark:prose-invert max-w-none"
                                dangerouslySetInnerHTML={{ __html: document.content_html }}
                            />
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <Shield className="h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">No hay contenido configurado</p>
                                <Link href="/support/privacy-policy/edit" className="mt-4">
                                    <Button variant="outline">
                                        <Edit className="mr-2 h-4 w-4" />
                                        Agregar contenido
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
