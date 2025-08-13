import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

/**
 * Breadcrumbs para la navegación de usuarios
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Gestión de usuarios',
        href: '/users',
    },
];

/**
 * Interfaz para los datos del usuario
 */
interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    is_online: boolean;
    status: string;
}

/**
 * Interfaz para las props de la página
 */
interface UsersPageProps {
    users: User[];
    total_users: number;
    verified_users: number;
    online_users: number;
}

/**
 * Obtiene el color del badge según el estado del usuario
 */
const getStatusColor = (status: string): string => {
    switch (status) {
        case 'online':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700';
        case 'recent':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
        case 'offline':
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
        case 'never':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
    }
};

/**
 * Obtiene el texto del estado del usuario
 */
const getStatusText = (status: string): string => {
    switch (status) {
        case 'online':
            return 'En línea';
        case 'recent':
            return 'Reciente';
        case 'offline':
            return 'Desconectado';
        case 'never':
            return 'Nunca';
        default:
            return 'Nunca';
    }
};

/**
 * Obtiene el icono del estado del usuario
 */
const getStatusIcon = (status: string): string => {
    switch (status) {
        case 'online':
            return 'fas fa-circle text-green-600';
        case 'recent':
            return 'fas fa-circle text-blue-600';
        case 'offline':
            return 'fas fa-circle text-gray-400';
        case 'never':
            return 'fas fa-circle text-red-600';
        default:
            return 'fas fa-circle text-gray-400';
    }
};

/**
 * Formatea la fecha de manera legible en hora de Guatemala
 */
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'Nunca';
    
    try {
        const date = new Date(dateString);
        
        // Usar la API nativa para zona horaria de Guatemala
        return date.toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'America/Guatemala'
        });
    } catch {
        return 'Fecha inválida';
    }
};

/**
 * Página de gestión de usuarios
 * Muestra una lista de todos los usuarios del sistema con su información de actividad
 * Se actualiza automáticamente cada minuto sin recargar la página
 * Mantiene la sesión del usuario activa cada 30 segundos
 */
export default function UsersIndex({ users: initialUsers, total_users: initialTotal, verified_users: initialVerified, online_users: initialOnline }: UsersPageProps) {
    // Estado local para los datos que se actualizan automáticamente
    const [users, setUsers] = useState(initialUsers);
    const [totalUsers, setTotalUsers] = useState(initialTotal);
    const [verifiedUsers, setVerifiedUsers] = useState(initialVerified);
    const [onlineUsers, setOnlineUsers] = useState(initialOnline);
    const [lastUpdate, setLastUpdate] = useState(new Date());

    /**
     * Función para actualizar los datos de usuarios
     */
    const refreshUserData = () => {
        router.visit('/users', {
            only: ['users', 'total_users', 'verified_users', 'online_users'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page: any) => {
                // Actualizar el estado local con los nuevos datos
                setUsers(page.props.users as User[]);
                setTotalUsers(page.props.total_users as number);
                setVerifiedUsers(page.props.verified_users as number);
                setOnlineUsers(page.props.online_users as number);
                setLastUpdate(new Date());
            },
        });
    };

    /**
     * Efecto para actualizar automáticamente los datos cada minuto
     */
    useEffect(() => {
        const interval = setInterval(() => {
            refreshUserData();
        }, 60000); // 1 minuto

        // Limpiar el intervalo cuando el componente se desmonte
        return () => clearInterval(interval);
    }, []);

    /**
     * Efecto para mantener la sesión del usuario activa cada 30 segundos
     * Esto actualiza el last_login_at en la base de datos
     */
    useEffect(() => {
        const sessionInterval = setInterval(() => {
            // Enviar una petición silenciosa para mantener la sesión activa
            // No esperamos respuesta para evitar conflictos con Inertia
            fetch('/users/keep-alive', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            }).catch(() => {
                // Ignorar errores para no interrumpir la funcionalidad
            });
        }, 30000); // 30 segundos

        // Limpiar el intervalo cuando el componente se desmonte
        return () => clearInterval(sessionInterval);
    }, []);

    /**
     * Función para actualizar manualmente los datos
     */
    const handleManualRefresh = () => {
        refreshUserData();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Usuarios" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {/* Header con información de última actualización */}
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Última actualización: {lastUpdate.toLocaleTimeString('es-GT', { 
                                hour: '2-digit', 
                                minute: '2-digit',
                                second: '2-digit'
                            })}
                        </p>
                    </div>
                    <button
                        onClick={handleManualRefresh}
                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <i className="fas fa-sync-alt mr-2"></i>
                        Actualizar
                    </button>
                </div>

                {/* Tarjetas de estadísticas */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total de Usuarios</CardTitle>
                            <i className="fas fa-users text-muted-foreground"></i>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalUsers}</div>
                            <p className="text-xs text-muted-foreground">
                                Usuarios registrados en el sistema
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Usuarios En Línea</CardTitle>
                            <i className="fas fa-circle text-muted-foreground"></i>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{onlineUsers}</div>
                            <p className="text-xs text-muted-foreground">
                                Activos en los últimos 5 minutos
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabla de usuarios */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Usuarios</CardTitle>
                        <CardDescription>
                            Todos los usuarios registrados en el sistema con su información de actividad
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Último Acceso</TableHead>
                                    <TableHead>Fecha de Creación</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{user.name}</div>
                                                <div className="text-sm text-muted-foreground">
                                                    {user.email}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={`${getStatusColor(user.status)} px-3 py-1 text-xs font-medium`}>
                                                <i className={`${getStatusIcon(user.status)} text-xs mr-2`}></i>
                                                <span>{getStatusText(user.status)}</span>
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(user.last_activity)}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(user.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
