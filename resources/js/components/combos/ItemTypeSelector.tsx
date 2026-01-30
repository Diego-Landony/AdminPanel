import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { COMBO_LABELS } from '@/constants/ui-constants';
import { HelpCircle, ListChecks, Package } from 'lucide-react';

interface ItemTypeSelectorProps {
    value: 'fixed' | 'choice';
    onChange: (value: 'fixed' | 'choice') => void;
    disabled?: boolean;
    id?: string;
}

export function ItemTypeSelector({ value, onChange, disabled = false, id }: ItemTypeSelectorProps) {
    const fixedId = id ? `fixed-${id}` : 'fixed';
    const choiceId = id ? `choice-${id}` : 'choice';

    const handleChange = (newValue: string) => {
        onChange(newValue as 'fixed' | 'choice');
    };

    return (
        <div className="space-y-2">
            <Label>Tipo de Item</Label>
            <RadioGroup value={value} onValueChange={handleChange} disabled={disabled} className="flex flex-col gap-4 sm:flex-row">
                <div className="flex items-center space-x-2">
                    <RadioGroupItem value="fixed" id={fixedId} />
                    <Label htmlFor={fixedId} className="flex cursor-pointer items-center gap-2 font-normal">
                        <Package className="h-4 w-4" />
                        {COMBO_LABELS.itemTypes.fixed}
                    </Label>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button type="button" className="text-muted-foreground transition-colors hover:text-foreground">
                                <HelpCircle className="h-4 w-4" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{COMBO_LABELS.itemTypesDescription.fixed}</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
                <div className="flex items-center space-x-2">
                    <RadioGroupItem value="choice" id={choiceId} />
                    <Label htmlFor={choiceId} className="flex cursor-pointer items-center gap-2 font-normal">
                        <ListChecks className="h-4 w-4" />
                        {COMBO_LABELS.itemTypes.choiceGroup}
                    </Label>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button type="button" className="text-muted-foreground transition-colors hover:text-foreground">
                                <HelpCircle className="h-4 w-4" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{COMBO_LABELS.itemTypesDescription.choiceGroup}</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            </RadioGroup>
        </div>
    );
}
