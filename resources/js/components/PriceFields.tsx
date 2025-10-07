import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Banknote } from 'lucide-react';

interface PriceFieldsProps {
    capitalPickup: string;
    capitalDomicilio: string;
    interiorPickup: string;
    interiorDomicilio: string;
    onChangeCapitalPickup: (value: string) => void;
    onChangeCapitalDomicilio: (value: string) => void;
    onChangeInteriorPickup: (value: string) => void;
    onChangeInteriorDomicilio: (value: string) => void;
    errors?: {
        capitalPickup?: string;
        capitalDomicilio?: string;
        interiorPickup?: string;
        interiorDomicilio?: string;
    };
    prefix?: string; // Para errores de variantes: "variants.0."
}

export function PriceFields({
    capitalPickup,
    capitalDomicilio,
    interiorPickup,
    interiorDomicilio,
    onChangeCapitalPickup,
    onChangeCapitalDomicilio,
    onChangeInteriorPickup,
    onChangeInteriorDomicilio,
    errors = {},
}: PriceFieldsProps) {
    return (
        <div className="grid md:grid-cols-2 gap-4">
            {/* Card Capital */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                        <Banknote className="h-4 w-4 text-primary" />
                        Capital
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <FormField label="Pickup" error={errors.capitalPickup} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={capitalPickup}
                            onChange={(e) => onChangeCapitalPickup(e.target.value)}
                            placeholder="0.00"
                        />
                    </FormField>

                    <FormField label="Domicilio" error={errors.capitalDomicilio} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={capitalDomicilio}
                            onChange={(e) => onChangeCapitalDomicilio(e.target.value)}
                            placeholder="0.00"
                        />
                    </FormField>
                </CardContent>
            </Card>

            {/* Card Interior */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                        <Banknote className="h-4 w-4 text-primary" />
                        Interior
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <FormField label="Pickup" error={errors.interiorPickup} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={interiorPickup}
                            onChange={(e) => onChangeInteriorPickup(e.target.value)}
                            placeholder="0.00"
                        />
                    </FormField>

                    <FormField label="Domicilio" error={errors.interiorDomicilio} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={interiorDomicilio}
                            onChange={(e) => onChangeInteriorDomicilio(e.target.value)}
                            placeholder="0.00"
                        />
                    </FormField>
                </CardContent>
            </Card>
        </div>
    );
}
