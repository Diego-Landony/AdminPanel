import React from 'react';

interface FormProgressProps {
    currentStep: number;
    totalSteps: number;
    steps?: string[];
    className?: string;
}

/**
 * Simple progress indicator for multi-step forms
 * Shows visual progress and step names
 */
export const FormProgress: React.FC<FormProgressProps> = ({ currentStep, totalSteps, steps = [], className = '' }) => {
    const progress = (currentStep / totalSteps) * 100;

    return (
        <div className={`space-y-2 ${className}`}>
            {/* Progress Bar */}
            <div className="h-2 w-full rounded-full bg-muted">
                <div className="h-2 rounded-full bg-primary transition-all duration-500 ease-out" style={{ width: `${progress}%` }} />
            </div>

            {/* Step Indicator */}
            <div className="flex justify-between text-xs text-muted-foreground">
                <span>
                    Paso {currentStep} de {totalSteps}
                </span>
                <span>{Math.round(progress)}% completado</span>
            </div>

            {/* Step Names (optional) */}
            {steps.length > 0 && (
                <div className="flex justify-between text-xs font-medium">
                    {steps.map((step, index) => (
                        <span key={index} className={index < currentStep ? 'text-primary' : 'text-muted-foreground'}>
                            {step}
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
};
