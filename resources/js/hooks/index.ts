/**
 * Central export file for all custom hooks
 */

// Data table hooks
export { useDataTable, type DataTableActions, type DataTableConfig, type DataTableState, type SortCriterion } from './useDataTable';

export {
    useActivityFilters,
    useCustomerFilters,
    useTableFilters,
    useUserFilters,
    type ActivityFilters,
    type CustomerFilters,
    type TableFiltersActions,
    type TableFiltersConfig,
    type TableFiltersState,
    type UserFilters,
} from './useTableFilters';

// Status hooks
export {
    getStatusBadgeClasses,
    getStatusBadgeColor,
    getStatusLabel,
    isUserOnline,
    useOnlineStatus,
    type OnlineStatusResult,
    type StatusConfig,
    type UserStatus,
} from './useOnlineStatus';

// Form hooks
export {
    getDraftMessage,
    useFormPersistence,
    type FormPersistenceActions,
    type FormPersistenceConfig,
    type FormPersistenceState,
} from './useFormPersistence';

export { useFormDirty } from './useFormDirty';

// Bulk actions hooks
export {
    getSelectedIdsArray,
    getSelectionMessage,
    useBulkActions,
    type BulkActionsActions,
    type BulkActionsConfig,
    type BulkActionsState,
} from './useBulkActions';

// UI hooks
export { useTheme } from './use-theme';

export { useMobileNavigation } from './use-mobile-navigation';

export { useDebounce } from './use-debounce';

export { usePermissions } from './use-permissions';

export { useBreadcrumbs } from './useBreadcrumbs';

export { useNotifications } from './useNotifications';
