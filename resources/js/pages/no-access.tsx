import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, LogOut, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

/**
 * Página que se muestra cuando el usuario no tiene acceso a ninguna funcionalidad del sistema
 */
export default function NoAccess() {
    return (
        <>
            <Head title="Sin Acceso" />
            
            <div className="min-h-screen bg-background flex items-center justify-center p-4">
                <div className="w-full max-w-md">
                    <Card className="border-destructive/50">
                        <CardHeader className="text-center pb-4">
                            <div className="mx-auto w-16 h-16 bg-destructive/10 rounded-full flex items-center justify-center mb-4">
                                <AlertTriangle className="w-8 h-8 text-destructive" />
                            </div>
                            <CardTitle className="text-xl text-destructive">
                                Acceso Restringido
                            </CardTitle>
                            <CardDescription className="text-muted-foreground">
                                No tienes permisos para acceder al sistema
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent className="space-y-4">
                            <div className="text-center text-sm text-muted-foreground space-y-2">
                                <p>
                                    Tu cuenta no tiene asignado ningún rol o permiso en el sistema.
                                </p>
                                <p>
                                    Contacta al administrador para que te asigne los permisos necesarios.
                                </p>
                            </div>

                            <div className="flex justify-center pt-4">
                                <Button 
                                    variant="destructive" 
                                    asChild
                                    className="w-full"
                                >
                                    <Link href={route('logout')} method="post" as="button">
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Cerrar Sesión
                                    </Link>
                                </Button>
                            </div>

                            <div className="pt-4 border-t border-border">
                                <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground">
                                    <Shield className="w-3 h-3" />
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
