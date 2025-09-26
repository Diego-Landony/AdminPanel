import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, LogOut, Shield } from 'lucide-react';

/**
 * Página que se muestra cuando el usuario no tiene acceso a ninguna funcionalidad del sistema
 */
export default function NoAccess() {
    return (
        <>
            <Head title="Sin Acceso" />

            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <div className="w-full max-w-md">
                    <Card className="border-destructive/50">
                        <CardHeader className="pb-4 text-center">
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10">
                                <AlertTriangle className="h-8 w-8 text-destructive" />
                            </div>
                            <CardTitle className="text-xl text-destructive">Acceso Restringido</CardTitle>
                            <CardDescription className="text-muted-foreground">No tienes permisos para acceder al sistema</CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <div className="space-y-2 text-center text-sm text-muted-foreground">
                                <p>Tu cuenta no tiene asignado ningún rol o permiso en el sistema.</p>
                                <p>Contacta al administrador para que te asigne los permisos necesarios.</p>
                            </div>

                            <div className="flex justify-center pt-4">
                                <Button variant="destructive" asChild className="w-full">
                                    <Link href={route('logout')} method="post" as="button">
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Cerrar Sesión
                                    </Link>
                                </Button>
                            </div>

                            <div className="border-t border-border pt-4">
                                <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground">
                                    <Shield className="h-3 w-3" />
                                    <span>Sistema de Permisos</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
