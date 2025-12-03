/**
 * @deprecated Use FormError from '@/components/ui/form-error' instead.
 * This component will be removed in a future version.
 */
import { FormError } from '@/components/ui/form-error';

interface InputErrorProps {
    message?: string;
    className?: string;
}

export default function InputError({ message, className }: InputErrorProps) {
    return <FormError message={message} className={className} showIcon={false} />;
}
