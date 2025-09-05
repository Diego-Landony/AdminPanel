# Laravel Admin Panel - Technical Documentation

## 1. Application Overview

**Admin Panel** is a comprehensive Laravel-based administrative system designed for user, role, and permission management with real-time activity tracking. Built with modern web technologies, it provides a scalable foundation for administrative operations with automatic permission discovery and role-based access control (RBAC).

### Core Purpose
- Administrative dashboard for system management
- User lifecycle management with role-based permissions
- Real-time activity monitoring and audit logging
- Customer management system with loyalty points
- Automated permission synchronization for new features

## 2. Technical Architecture

### Backend Stack
- **Framework**: Laravel 12.22.1
- **PHP Version**: 8.4.1
- **Database**: MySQL with comprehensive indexing
- **Authentication**: Laravel's built-in authentication system
- **Testing**: Pest 3.8.2

### Frontend Stack
- **Framework**: React 19.0.0
- **Routing**: Inertia.js 2.0.4 (SPA-like experience)
- **Styling**: Tailwind CSS 4.0.0
- **UI Components**: shadcn/ui with Lucide icons
- **Notifications**: Sonner toast system
- **Date Handling**: react-day-picker

### Supporting Technologies
- **Route Management**: Ziggy 2.5.3 (Laravel routes in JavaScript)
- **Code Quality**: Laravel Pint 1.24.0
- **Build Tools**: Vite for asset compilation

## 3. Page Structure

### Core Pages
- **Dashboard** (`/dashboard`): Main landing page with system overview
- **Users** (`/users`): Complete user management (CRUD operations)
- **Roles** (`/roles`): Role and permission management system
- **Activity** (`/activity`): Unified activity and audit log viewer
- **Customers** (`/customers`): Customer management with loyalty points
- **Customer Types** (`/customer-types`): Loyalty tier management system

### Page Capabilities
- **Responsive Design**: Desktop tables, mobile card layouts
- **Real-time Updates**: Auto-refresh every 60 seconds for user activity
- **Advanced Search**: Multi-field search with preserved state
- **Pagination**: Configurable results per page (10, 25, 50, 100)

## 4. User Management System

### User Features
- **CRUD Operations**: Create, read, update, delete users
- **Real-time Status**: Online (<5min), Recent (<15min), Offline, Never
- **Activity Tracking**: Last login and activity timestamps
- **Security Protection**: Cannot delete admin user or self-delete
- **Email Verification**: Auto-verification for admin-created users

### User States
- **Online**: Activity within 5 minutes
- **Recent**: Activity within 15 minutes  
- **Offline**: Activity over 15 minutes ago
- **Never**: No recorded activity

### Database Schema (Users)
```sql
users:
├── id (bigint, primary key)
├── name (varchar)
├── email (varchar, unique)
├── email_verified_at (timestamp)
├── password (varchar, hashed)
├── last_login_at (timestamp)
├── last_activity_at (timestamp, indexed)
├── timezone (varchar, default: America/Guatemala)
├── remember_token (varchar)
└── timestamps + soft deletes
```

## 5. Role-Based Access Control (RBAC)

### Automated Permission System
The system features **PermissionDiscoveryService** that automatically:
- Scans `/resources/js/pages/` directory for new pages
- Generates permissions using pattern: `{page}.{action}`
- Creates four standard actions: `view`, `create`, `edit`, `delete`
- Updates admin role with all permissions automatically

### Permission Structure
```
dashboard.view       → "Ver Dashboard"
users.view          → "Ver Usuarios"
users.create        → "Crear Usuarios"
users.edit          → "Editar Usuarios"
users.delete        → "Eliminar Usuarios"
roles.view          → "Ver Roles y Permisos"
activity.view       → "Ver Actividad"
customers.view      → "Ver Clientes"
customer-types.view → "Ver Tipos de Cliente"
```

### Role Management
- **Admin Role**: System-protected, cannot be deleted, auto-gets all permissions
- **Custom Roles**: User-created with granular permission assignment
- **Default Access**: Users without roles can only access dashboard
- **Permission Interface**: Simple checkbox grid by page and action

### Database Schema (RBAC)
```sql
roles:
├── id (bigint, primary key)
├── name (varchar, unique)
├── description (text)
├── is_system (boolean)
└── timestamps

permissions:
├── id (bigint, primary key)
├── name (varchar, unique, e.g., "users.view")
├── display_name (varchar, e.g., "Ver Usuarios")
├── description (text)
├── group (varchar, e.g., "users")
└── timestamps

pivot tables: role_user, permission_role
```

## 6. Activity Monitoring System

### Dual Activity Tracking
The system combines two data sources for comprehensive monitoring:

#### UserActivity Table
- General user activities (page views, navigation)
- HTTP method and URL tracking
- Metadata storage (JSON format)
- Excludes: heartbeat, page_view events

#### ActivityLog Table  
- Audit trail for system changes
- Before/after value tracking (old_values, new_values JSON)
- Target model and ID reference
- User agent tracking

### Activity Types
**Authentication:**
- `login`, `logout`

**User Management:**
- `user_created`, `user_updated`, `user_deleted`, `user_restored`

**Role Management:**
- `role_created`, `role_updated`, `role_deleted`, `role_users_updated`

**System:**
- `theme_changed`, `action`

### Activity Features
- **Unified View**: Combined display of UserActivity + ActivityLog
- **Advanced Filtering**: By type, user, date range, and global search
- **Enhanced Descriptions**: Color-coded changes (red=old, green=new)
- **Manual Pagination**: For combined dataset handling
- **Real-time Statistics**: Total events, unique users, daily events

## 7. Customer Management System

### Customer Features
- **Full Profile Management**: Personal details, contact information
- **Loyalty System**: Points tracking with customer types (Regular, Bronze, Silver, Gold, Platinum)
- **Purchase Tracking**: Last purchase date and activity monitoring
- **Card System**: Unique subway card identification
- **Customer Types**: Hierarchical system with point multipliers

### Customer Types System
The loyalty program includes five tiers with automatic promotion based on points:

| Type | Internal Name | Points Required | Multiplier | Color |
|------|---------------|----------------|------------|-------|
| Regular | `regular` | 0 | 1.00x | Gray |
| Bronce | `bronze` | 50 | 1.25x | Orange |
| Plata | `silver` | 125 | 1.50x | Slate |
| Oro | `gold` | 325 | 1.75x | Yellow |
| Platino | `platinum` | 1000 | 2.00x | Purple |

### Database Schema (Customers)
```sql
customers:
├── id (bigint, primary key)
├── full_name, email (varchar, unique, indexed)
├── subway_card (varchar, unique, indexed)
├── birth_date, gender, phone, address
├── customer_type_id (foreign key → customer_types.id)
├── last_purchase_at (timestamp, indexed)
├── puntos (integer, default 0)
├── puntos_updated_at (timestamp)
└── standard timestamps + soft deletes

customer_types:
├── name (internal: 'regular', 'bronze', etc.)
├── display_name (user-facing names)
├── points_required (integer)
├── multiplier (decimal, points multiplier)
├── color, sort_order, is_active
```

## 8. Technical Features

### Security Implementation
- **Middleware Protection**: Route-level permission checks
- **Frontend Validation**: Component-level permission verification
- **CSRF Protection**: Laravel's built-in CSRF tokens
- **Password Security**: Custom password validation rules
- **Admin Protection**: Cannot delete admin@admin.com user

### Performance Optimizations
- **Eager Loading**: Prevents N+1 queries with `->with('user')`
- **Database Indexing**: Strategic indexes on activity timestamps and search fields
- **Manual Pagination**: Efficient handling of combined datasets
- **Preserved State**: Maintains filters during navigation

### Real-time Features
- **Auto-refresh**: User status updates every 60 seconds
- **Keep-alive**: Activity tracking every 30 seconds
- **Toast Notifications**: Immediate feedback for user actions
- **Progressive Enhancement**: Works without JavaScript for core functions

### Data Management
- **Soft Deletes**: Recoverable deletion for users and customers
- **Audit Trail**: Complete change tracking with before/after values
- **JSON Metadata**: Flexible data storage for additional information
- **Timezone Support**: Configurable per user (default: America/Guatemala)

## 9. Notification System

### Implementation
- **Library**: Sonner toast notifications
- **Types**: Success, error, info messages
- **Integration**: Laravel flash messages + frontend toast
- **Positioning**: Standard toast positioning
- **Duration**: Configurable display time

### Usage Patterns
```typescript
// Success operations
toast.success('Usuario creado exitosamente');

// Error handling  
toast.error('Error al cargar los datos');

// Information
toast.info('No se encontraron resultados');
```

## 10. Development Commands

### Permission Management
```bash
# Sync all permissions after adding new pages
php artisan db:seed --class=DatabaseSeeder

# Create customer type permissions
php artisan db:seed CustomerTypePermissionsSeeder

# Update existing customers with types
php artisan db:seed UpdateCustomersWithTypesSeeder

# Check available routes
php artisan route:list

# Verify permissions in database
php artisan tinker --execute="App\Models\Permission::all()"
```

### Development Workflow
1. Create new page in `resources/js/pages/`
2. Add routes with permission middleware
3. Run `php artisan db:seed --class=DatabaseSeeder`
4. Test permission assignment in roles interface

## 11. Database Schema Summary

### Core Tables (17 total)
- **users**: System administrators and operators
- **customers**: End users with loyalty program
- **customer_types**: Loyalty tier definitions with multipliers
- **roles**: Role definitions with system protection
- **permissions**: Auto-generated page permissions
- **activity_logs**: Audit trail with change tracking
- **user_activities**: General activity monitoring

### System Tables
- **sessions**: User session management
- **cache**: Application caching
- **jobs**: Background job queue
- **migrations**: Schema version control

### Relationships
- Users ↔ Roles (many-to-many)
- Roles ↔ Permissions (many-to-many)
- Users → ActivityLog (one-to-many)
- Users → UserActivity (one-to-many)
- Customers → CustomerTypes (many-to-one)

## 12. Customer Type Management

### Automatic Type Assignment
- **Point-based Promotion**: Customers automatically upgrade based on points
- **Real-time Updates**: Type changes when points are modified
- **Multiplier Application**: Points earned multiplied by customer type multiplier
- **Color-coded Display**: Visual differentiation in UI

### Management Interface
- **CRUD Operations**: Full customer type management
- **Validation**: Prevents deletion of types with assigned customers
- **Sorting**: Configurable display order
- **Status Control**: Active/inactive type management

---

This Laravel admin panel provides a robust, scalable foundation for administrative operations with automated permission management, comprehensive audit logging, customer loyalty system, and modern user experience patterns. The system is designed to grow automatically with new features while maintaining security and usability standards.