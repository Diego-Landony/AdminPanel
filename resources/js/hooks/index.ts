/**
 * Central export file for all custom hooks
 */

// Data table hooks
export {
    useDataTable,
    type DataTableConfig,
    type DataTableState,
    type DataTableActions,
    type SortCriterion,
} from './useDataTable';

export {
    useTableFilters,
    useActivityFilters,
    useUserFilters,
    useCustomerFilters,
    type TableFiltersConfig,
    type TableFiltersState,
    type TableFiltersActions,
    type ActivityFilters,
    type UserFilters,
    type CustomerFilters,
} from './useTableFilters';

// Status hooks
export {
    useOnlineStatus,
    getStatusBadgeColor,
    getStatusLabel,
    isUserOnline,
    getStatusBadgeClasses,
    type UserStatus,
    type StatusConfig,
    type OnlineStatusResult,
} from './useOnlineStatus';

// Form hooks
export {
    useFormPersistence,
    getDraftMessage,
    type FormPersistenceConfig,
    type FormPersistenceState,
    type FormPersistenceActions,
} from './useFormPersistence';

export {
    useFormDirty,
} from './useFormDirty';

// Bulk actions hooks
export {
    useBulkActions,
    getSelectionMessage,
    getSelectedIdsArray,
    type BulkActionsConfig,
    type BulkActionsState,
    type BulkActionsActions,
} from './useBulkActions';

// UI hooks
export {
    useTheme,
} from './use-theme';

export {
    useMobileNavigation,
} from './use-mobile-navigation';

export {
    useDebounce,
} from './use-debounce';

export {
    usePermissions,
} from './use-permissions';

export {
    useBreadcrumbs,
} from './useBreadcrumbs';

export {
    useNotifications,
} from './useNotifications';
