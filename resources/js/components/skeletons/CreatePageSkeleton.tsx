import { Skeleton } from '@/components/ui/skeleton';
import { FormSectionSkeleton } from './FormSectionSkeleton';

interface FormSectionConfig {
    fields?: number;
    hasTextarea?: boolean;
    hasSelect?: boolean;
    hasCheckboxes?: number;
    hasTable?: boolean;
}

interface CreatePageSkeletonProps {
    sections?: FormSectionConfig[];
    showBackButton?: boolean;
    showSubmitButton?: boolean;
}

export function CreatePageSkeleton({ sections = [{ fields: 3 }], showBackButton = true, showSubmitButton = true }: CreatePageSkeletonProps) {
    return (
        <div className="space-y-6">
            {/* Page Header */}
            <div className="space-y-2">
                <div className="flex items-center gap-4">
                    {showBackButton && <Skeleton className="h-8 w-8" />}
                    <div className="space-y-1">
                        <Skeleton className="h-8 w-48" />
                        <Skeleton className="h-4 w-64" />
                    </div>
                </div>
            </div>

            {/* Form Container */}
            <div className="mx-auto max-w-4xl">
                <div className="space-y-6">
                    {/* Form Sections */}
                    {sections.map((section, index) => (
                        <FormSectionSkeleton
                            key={index}
                            fields={section.fields}
                            hasTextarea={section.hasTextarea}
                            hasSelect={section.hasSelect}
                            hasCheckboxes={section.hasCheckboxes}
                            hasTable={section.hasTable}
                        />
                    ))}

                    {/* Action Buttons */}
                    {showSubmitButton && (
                        <div className="flex items-center justify-end gap-3 border-t border-border pt-6">
                            <Skeleton className="h-10 w-20" />
                            <Skeleton className="h-10 w-28" />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
