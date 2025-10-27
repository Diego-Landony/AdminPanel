import { Skeleton } from '@/components/ui/skeleton';
import { FormSectionSkeleton, type FormSectionConfig } from './FormSectionSkeleton';

interface EditPageSkeletonProps {
    sections?: FormSectionConfig[];
    showBackButton?: boolean;
    showSubmitButton?: boolean;
    showResetButton?: boolean;
    showInfoCard?: boolean;
}

export function EditPageSkeleton({
    sections = [{ fields: 3 }],
    showBackButton = true,
    showSubmitButton = true,
    showResetButton = false,
    showInfoCard = false,
}: EditPageSkeletonProps) {
    return (
        <>
            {/* Header */}
            <div className="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                <div className="min-w-0 flex-1 space-y-2">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-5 w-96" />
                </div>
                {showBackButton && <Skeleton className="h-10 w-32" />}
            </div>

            {/* Content Area */}
            <div className="mx-auto w-full max-w-4xl min-w-0 px-1">
                {showInfoCard && (
                    <div className="mb-6 space-y-4 rounded-lg border bg-muted/30 p-6">
                        <div className="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                            <div className="space-y-1">
                                <Skeleton className="h-4 w-8" />
                                <Skeleton className="h-4 w-16" />
                            </div>
                            <div className="space-y-1">
                                <Skeleton className="h-4 w-12" />
                                <Skeleton className="h-4 w-32" />
                            </div>
                            <div className="space-y-1">
                                <Skeleton className="h-4 w-20" />
                                <Skeleton className="h-4 w-28" />
                            </div>
                        </div>
                    </div>
                )}

                {/* Form Sections */}
                <div className="space-y-6">
                    {sections.map((section, index) => (
                        <FormSectionSkeleton
                            key={index}
                            fields={section.fields}
                            hasTextarea={section.hasTextarea}
                            hasSelect={section.hasSelect}
                            hasCheckboxes={section.hasCheckboxes}
                            hasTable={section.hasTable}
                            showIcon={section.showIcon}
                            showTitle={section.showTitle}
                        />
                    ))}
                </div>
            </div>

            {/* Action Buttons */}
            <div className="mt-8 flex flex-col items-stretch justify-end gap-3 px-1 sm:flex-row sm:items-center sm:gap-4">
                {showResetButton && (
                    <div className="hidden text-sm sm:block">
                        <Skeleton className="h-4 w-32" />
                    </div>
                )}
                <div className="flex flex-col gap-3 sm:flex-row sm:gap-4">
                    {showResetButton && <Skeleton className="h-10 w-full sm:w-36" />}
                    <Skeleton className="h-10 w-full sm:w-24" />
                    {showSubmitButton && <Skeleton className="h-10 w-full sm:w-36" />}
                </div>
            </div>
        </>
    );
}
