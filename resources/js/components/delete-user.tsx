import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/ui/form-field';

import HeadingSmall from '@/components/heading-small';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

/**
 * Componente para eliminar la cuenta del usuario
 * Permite al usuario eliminar su cuenta de forma segura
 */
export default function DeleteUser() {
    const [showPassword, setShowPassword] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm<Required<{ password: string }>>({ password: '' });

    /**
     * Elimina la cuenta del usuario
     */
    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    /**
     * Cierra el modal y limpia los errores
     */
    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall 
                title="Eliminar Cuenta" 
                description="Elimina tu cuenta y todos sus recursos" 
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Advertencia</p>
                    <p className="text-sm">Por favor procede con precaución, esta acción no se puede deshacer.</p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="destructive">Eliminar Cuenta</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>¿Estás seguro de que quieres eliminar tu cuenta?</DialogTitle>
                        <DialogDescription>
                            Una vez que tu cuenta sea eliminada, todos sus recursos y datos también serán eliminados permanentemente. Por favor ingresa tu contraseña
                            para confirmar que deseas eliminar tu cuenta permanentemente.
                        </DialogDescription>
                        <form className="space-y-6" onSubmit={deleteUser}>
                            <FormField
                                label="Contraseña"
                                error={errors.password}
                                required
                                className="sr-only"
                            >
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        name="password"
                                        ref={passwordInput}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Contraseña"
                                        autoComplete="current-password"
                                        className="pr-10"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="absolute right-1 top-1 h-8 w-8 p-0"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                            </FormField>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={closeModal}>
                                        Cancelar
                                    </Button>
                                </DialogClose>

                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">
                                        {processing ? 'Eliminando...' : 'Eliminar Cuenta'}
                                    </button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
