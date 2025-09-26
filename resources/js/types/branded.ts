/**
 * Branded types para mejorar type safety en la aplicación
 * Los branded types previenen errores de asignación incorrecta entre diferentes tipos de IDs
 */

// Base branded type utility
declare const __brand: unique symbol;
type Brand<T, TBrand extends string> = T & { readonly [__brand]: TBrand };

// ID branded types
export type UserId = Brand<string, 'UserId'>;
export type CustomerId = Brand<string, 'CustomerId'>;
export type CustomerTypeId = Brand<string, 'CustomerTypeId'>;
export type RoleId = Brand<string, 'RoleId'>;
export type RestaurantId = Brand<string, 'RestaurantId'>;
export type ActivityId = Brand<string, 'ActivityId'>;
export type PermissionId = Brand<string, 'PermissionId'>;

// Numeric ID variants for when needed
export type UserIdNumeric = Brand<number, 'UserIdNumeric'>;
export type CustomerIdNumeric = Brand<number, 'CustomerIdNumeric'>;
export type RoleIdNumeric = Brand<number, 'RoleIdNumeric'>;

// Constructor functions para crear branded types de manera type-safe
export const UserId = {
    create: (id: string): UserId => id as UserId,
    parse: (id: string | number): UserId => String(id) as UserId,
    isValid: (id: unknown): id is UserId => typeof id === 'string' && id.length > 0,
    toString: (id: UserId): string => id,
};

export const CustomerId = {
    create: (id: string): CustomerId => id as CustomerId,
    parse: (id: string | number): CustomerId => String(id) as CustomerId,
    isValid: (id: unknown): id is CustomerId => typeof id === 'string' && id.length > 0,
    toString: (id: CustomerId): string => id,
};

export const CustomerTypeId = {
    create: (id: string): CustomerTypeId => id as CustomerTypeId,
    parse: (id: string | number): CustomerTypeId => String(id) as CustomerTypeId,
    isValid: (id: unknown): id is CustomerTypeId => typeof id === 'string' && id.length > 0,
    toString: (id: CustomerTypeId): string => id,
};

export const RoleId = {
    create: (id: string): RoleId => id as RoleId,
    parse: (id: string | number): RoleId => String(id) as RoleId,
    isValid: (id: unknown): id is RoleId => typeof id === 'string' && id.length > 0,
    toString: (id: RoleId): string => id,
};

export const RestaurantId = {
    create: (id: string): RestaurantId => id as RestaurantId,
    parse: (id: string | number): RestaurantId => String(id) as RestaurantId,
    isValid: (id: unknown): id is RestaurantId => typeof id === 'string' && id.length > 0,
    toString: (id: RestaurantId): string => id,
};

export const ActivityId = {
    create: (id: string): ActivityId => id as ActivityId,
    parse: (id: string | number): ActivityId => String(id) as ActivityId,
    isValid: (id: unknown): id is ActivityId => typeof id === 'string' && id.length > 0,
    toString: (id: ActivityId): string => id,
};

export const PermissionId = {
    create: (id: string): PermissionId => id as PermissionId,
    parse: (id: string | number): PermissionId => String(id) as PermissionId,
    isValid: (id: unknown): id is PermissionId => typeof id === 'string' && id.length > 0,
    toString: (id: PermissionId): string => id,
};

// Branded types para otros conceptos importantes
export type Email = Brand<string, 'Email'>;
export type PhoneNumber = Brand<string, 'PhoneNumber'>;
export type NIT = Brand<string, 'NIT'>;
export type SubwayCard = Brand<string, 'SubwayCard'>;
export type Money = Brand<number, 'Money'>;
export type Percentage = Brand<number, 'Percentage'>;

// Constructors para otros branded types
export const Email = {
    create: (email: string): Email => {
        if (!Email.isValid(email)) {
            throw new Error(`Invalid email format: ${email}`);
        }
        return email as Email;
    },
    isValid: (email: unknown): email is Email => {
        if (typeof email !== 'string') return false;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    toString: (email: Email): string => email,
};

export const PhoneNumber = {
    create: (phone: string): PhoneNumber => {
        if (!PhoneNumber.isValid(phone)) {
            throw new Error(`Invalid phone number format: ${phone}`);
        }
        return phone as PhoneNumber;
    },
    isValid: (phone: unknown): phone is PhoneNumber => {
        if (typeof phone !== 'string') return false;
        // Basic phone validation - adjust regex as needed for your format
        const phoneRegex = /^\+?[\d\s\-()]+$/;
        return phoneRegex.test(phone) && phone.length >= 8;
    },
    toString: (phone: PhoneNumber): string => phone,
};

export const Money = {
    create: (amount: number): Money => {
        if (!Money.isValid(amount)) {
            throw new Error(`Invalid money amount: ${amount}`);
        }
        return amount as Money;
    },
    isValid: (amount: unknown): amount is Money => {
        return typeof amount === 'number' && amount >= 0 && Number.isFinite(amount);
    },
    toNumber: (money: Money): number => money,
    format: (money: Money, currency = 'GTQ'): string => {
        return new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency,
        }).format(money);
    },
    add: (a: Money, b: Money): Money => Money.create(a + b),
    subtract: (a: Money, b: Money): Money => Money.create(Math.max(0, a - b)),
    multiply: (money: Money, factor: number): Money => Money.create(money * factor),
};

export const Percentage = {
    create: (value: number): Percentage => {
        if (!Percentage.isValid(value)) {
            throw new Error(`Invalid percentage: ${value}`);
        }
        return value as Percentage;
    },
    isValid: (value: unknown): value is Percentage => {
        return typeof value === 'number' && value >= 0 && value <= 100 && Number.isFinite(value);
    },
    toNumber: (percentage: Percentage): number => percentage,
    toDecimal: (percentage: Percentage): number => percentage / 100,
    format: (percentage: Percentage): string => `${percentage}%`,
};

// Utility types para arrays de branded types
export type UserIds = readonly UserId[];
export type CustomerIds = readonly CustomerId[];
export type RoleIds = readonly RoleId[];

// Helper para validar arrays de IDs
export const validateIds = {
    users: (ids: unknown[]): UserIds => {
        return ids.filter(UserId.isValid) as UserIds;
    },
    customers: (ids: unknown[]): CustomerIds => {
        return ids.filter(CustomerId.isValid) as CustomerIds;
    },
    roles: (ids: unknown[]): RoleIds => {
        return ids.filter(RoleId.isValid) as RoleIds;
    },
};

// Status branded types para type safety en estados
export type UserStatus = Brand<'active' | 'inactive' | 'suspended', 'UserStatus'>;
export type CustomerStatus = Brand<'active' | 'inactive', 'CustomerStatus'>;
export type RestaurantStatus = Brand<'active' | 'inactive' | 'pending', 'RestaurantStatus'>;

export const UserStatus = {
    ACTIVE: 'active' as UserStatus,
    INACTIVE: 'inactive' as UserStatus,
    SUSPENDED: 'suspended' as UserStatus,
    isValid: (status: unknown): status is UserStatus => {
        return typeof status === 'string' && ['active', 'inactive', 'suspended'].includes(status);
    },
    toString: (status: UserStatus): string => status,
};

export const CustomerStatus = {
    ACTIVE: 'active' as CustomerStatus,
    INACTIVE: 'inactive' as CustomerStatus,
    isValid: (status: unknown): status is CustomerStatus => {
        return typeof status === 'string' && ['active', 'inactive'].includes(status);
    },
    toString: (status: CustomerStatus): string => status,
};

export const RestaurantStatus = {
    ACTIVE: 'active' as RestaurantStatus,
    INACTIVE: 'inactive' as RestaurantStatus,
    PENDING: 'pending' as RestaurantStatus,
    isValid: (status: unknown): status is RestaurantStatus => {
        return typeof status === 'string' && ['active', 'inactive', 'pending'].includes(status);
    },
    toString: (status: RestaurantStatus): string => status,
};