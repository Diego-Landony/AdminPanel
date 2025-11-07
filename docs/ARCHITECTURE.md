# SubwayApp - System Architecture

**Version**: 1.0
**Updated**: November 2025

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Architecture Diagrams](#architecture-diagrams)
3. [Component Details](#component-details)
4. [Data Flow](#data-flow)
5. [Infrastructure](#infrastructure)
6. [Security Architecture](#security-architecture)
7. [Scalability Considerations](#scalability-considerations)

---

## System Overview

SubwayApp is a full-stack restaurant management and mobile ordering platform built for Subway Guatemala. The system consists of three main components:

1. **Admin Panel** (Web) - Restaurant management interface
2. **REST API** (Backend) - Mobile app backend
3. **Mobile App** (React Native) - Customer ordering app

### Technology Stack

```
Frontend (Admin Panel):
├── React 19
├── Inertia.js v2
├── TypeScript
├── Tailwind CSS v4
└── Vite

Backend (API + Admin):
├── Laravel 12
├── PHP 8.4
├── MariaDB
├── Laravel Sanctum (API auth)
├── Laravel Socialite (OAuth)
└── Firebase PHP SDK (Push notifications)

Mobile App:
├── React Native
├── TypeScript
└── Firebase SDK
```

---

## Architecture Diagrams

### High-Level System Architecture

```mermaid
graph TB
    subgraph "Client Layer"
        A1[Admin Web Panel<br/>React + Inertia.js]
        A2[Mobile App iOS<br/>React Native]
        A3[Mobile App Android<br/>React Native]
    end

    subgraph "API Gateway"
        B1[Laravel API<br/>/api/v1/*]
        B2[Middleware Layer<br/>Auth, Rate Limit, CORS]
    end

    subgraph "Application Layer"
        C1[Controllers]
        C2[Services]
        C3[Form Requests]
        C4[Resources]
    end

    subgraph "Domain Layer"
        D1[Customer Module]
        D2[Menu Module]
        D3[Order Module]
        D4[Restaurant Module]
        D5[Promotion Module]
    end

    subgraph "Infrastructure Layer"
        E1[(MariaDB)]
        E2[Laravel Sanctum<br/>Token Manager]
        E3[Firebase<br/>Push Notifications]
        E4[Google OAuth]
        E5[Apple Sign-In]
        E6[Storage<br/>S3/Local]
    end

    A1 -->|Session Auth| C1
    A2 -->|Bearer Token| B1
    A3 -->|Bearer Token| B1

    B1 --> B2
    B2 --> C1

    C1 --> C2
    C1 --> C3
    C1 --> C4

    C2 --> D1
    C2 --> D2
    C2 --> D3
    C2 --> D4
    C2 --> D5

    D1 --> E1
    D2 --> E1
    D3 --> E1
    D4 --> E1
    D5 --> E1

    C2 --> E2
    C2 --> E3
    C2 --> E4
    C2 --> E5
    C2 --> E6
```

### API Request Flow

```mermaid
sequenceDiagram
    participant M as Mobile App
    participant G as API Gateway<br/>/api/v1/*
    participant MW as Middleware
    participant C as Controller
    participant S as Service
    participant D as Database
    participant F as Firebase

    M->>G: POST /auth/login
    G->>MW: Validate & Rate Limit
    MW->>C: AuthController@login
    C->>D: Verify Credentials
    D-->>C: Customer Data
    C->>D: Create Sanctum Token
    D-->>C: Token Created
    C->>M: Return Token + Customer

    M->>G: POST /devices/register<br/>Authorization: Bearer <token>
    G->>MW: Verify Token
    MW->>C: DeviceController@register
    C->>D: Save Device + FCM Token
    D-->>C: Device Saved
    C->>M: Return Device Info

    Note over S,F: Admin sends promotion
    S->>D: Get Customer Devices
    D-->>S: FCM Tokens
    S->>F: Send Push Notification
    F-->>M: Push Received
```

### Authentication Architecture

```mermaid
graph LR
    subgraph "Authentication Methods"
        A1[Email + Password]
        A2[Google OAuth]
        A3[Apple Sign-In]
    end

    subgraph "Laravel Sanctum"
        B1[Token Generation]
        B2[Token Storage<br/>personal_access_tokens]
        B3[Token Validation]
    end

    subgraph "Multi-Device Management"
        C1[Device 1: iPhone<br/>Token ABC]
        C2[Device 2: Android<br/>Token XYZ]
        C3[Device 3: iPad<br/>Token QWE]
    end

    A1 --> B1
    A2 --> B1
    A3 --> B1

    B1 --> B2
    B2 --> B3

    B3 --> C1
    B3 --> C2
    B3 --> C3

    C1 -.-> D1[(customer_devices)]
    C2 -.-> D1
    C3 -.-> D1
```

### Database Schema (Core Tables)

```mermaid
erDiagram
    CUSTOMERS ||--o{ CUSTOMER_ADDRESSES : has
    CUSTOMERS ||--o{ CUSTOMER_NITS : has
    CUSTOMERS ||--o{ CUSTOMER_DEVICES : owns
    CUSTOMERS }o--|| CUSTOMER_TYPES : belongs_to
    CUSTOMER_DEVICES }o--o| PERSONAL_ACCESS_TOKENS : linked_to

    PRODUCTS ||--o{ PRODUCT_VARIANTS : has
    PRODUCTS }o--o{ CATEGORIES : belongs_to
    PRODUCTS ||--o{ PRODUCT_SECTIONS : includes

    SECTIONS ||--o{ SECTION_OPTIONS : has

    COMBOS ||--o{ COMBO_ITEMS : contains
    COMBO_ITEMS ||--o{ COMBO_ITEM_OPTIONS : has_options

    PROMOTIONS ||--o{ PROMOTION_ITEMS : applies_to
    PROMOTIONS ||--o{ BUNDLE_PROMOTION_ITEMS : includes

    CUSTOMERS {
        bigint id PK
        string name
        string email UK
        string password
        string google_id UK
        string apple_id UK
        enum oauth_provider
        string subway_card UK
        date birth_date
        string gender
        bigint customer_type_id FK
        string phone
        int points
        timestamp last_login_at
        timestamp email_verified_at
    }

    CUSTOMER_TYPES {
        bigint id PK
        string name
        int points_required
        decimal multiplier
        string color
        boolean is_active
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        string name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
    }

    CUSTOMER_DEVICES {
        bigint id PK
        bigint customer_id FK
        bigint sanctum_token_id FK
        string fcm_token UK
        string device_identifier UK
        enum device_type
        string device_name
        string app_version
        string os_version
        boolean is_active
        timestamp last_used_at
    }

    CATEGORIES {
        bigint id PK
        string name
        boolean is_active
        boolean uses_variants
        json variant_definitions
        int sort_order
    }

    PRODUCTS {
        bigint id PK
        bigint category_id FK
        string name
        text description
        string image
        boolean has_variants
        decimal precio_pickup_capital
        decimal precio_domicilio_capital
        decimal precio_pickup_interior
        decimal precio_domicilio_interior
        boolean is_active
    }

    SECTIONS {
        bigint id PK
        string title
        boolean is_required
        boolean allow_multiple
        int min_selections
        int max_selections
    }

    SECTION_OPTIONS {
        bigint id PK
        bigint section_id FK
        string name
        boolean is_extra
        decimal price_modifier
    }
```

### Promotion System Architecture

```mermaid
graph TB
    subgraph "Promotion Types"
        P1[Daily Special<br/>Sub del Día]
        P2[2x1<br/>Buy 2 Pay 1]
        P3[Percentage<br/>Discount]
        P4[Bundle Special<br/>Combo Promo]
    end

    subgraph "Configuration"
        C1[Valid Dates<br/>From/To]
        C2[Time Schedule<br/>Hours]
        C3[Weekdays<br/>Mon-Sun]
        C4[Service Type<br/>Pickup/Delivery]
    end

    subgraph "Scope"
        S1[Specific Product]
        S2[Product Variant]
        S3[Entire Category]
        S4[Combo Items]
    end

    subgraph "Price Calculation"
        PC[Promotion Engine]
    end

    P1 --> C1
    P2 --> C2
    P3 --> C3
    P4 --> C4

    P1 --> S1
    P2 --> S3
    P3 --> S2
    P4 --> S4

    S1 --> PC
    S2 --> PC
    S3 --> PC
    S4 --> PC
```

---

## Component Details

### 1. API Gateway Layer

**Responsibilities**:
- Route API requests to appropriate controllers
- Apply middleware (auth, rate limiting, CORS)
- Transform all responses to JSON
- Handle errors consistently

**Middleware Stack**:
```php
api.v1:
├── ForceJsonResponse         // Ensure JSON responses
├── RateLimiter               // Throttle requests
│   ├── auth: 5/min
│   ├── oauth: 10/min
│   └── api: 120/min
├── CORS                      // Cross-origin headers
└── Sanctum (auth:sanctum)    // Token validation
```

### 2. Authentication Layer

**Components**:

**AuthController**:
- Register new customers
- Login (email/password)
- Logout (single/all devices)
- Password reset flow
- Email verification

**OAuthController**:
- Google Sign-In integration
- Apple Sign-In integration
- Account linking by email
- Token generation

**Sanctum Token Manager**:
- Generate long-lived tokens (365 days)
- Store tokens with device metadata
- Validate tokens on each request
- Revoke tokens on logout

### 3. Domain Modules

#### Customer Module
```
app/Models/
├── Customer.php              // Main customer model
├── CustomerType.php          // Loyalty tiers
├── CustomerAddress.php       // Delivery addresses
├── CustomerNit.php           // Billing info
└── CustomerDevice.php        // Push notification devices

app/Http/Controllers/
├── Api/V1/
│   ├── ProfileController.php
│   └── DeviceController.php
└── CustomerController.php    // Admin panel

app/Services/
├── SocialAuthService.php     // OAuth logic
└── FCMService.php            // Push notifications
```

#### Menu Module
```
app/Models/Menu/
├── Category.php              // Sandwiches, Bebidas, etc.
├── Product.php               // Individual products
├── ProductVariant.php        // 15cm, 30cm sizes
├── Section.php               // Pan, Vegetales, Salsas
└── SectionOption.php         // Specific options

Features:
- Dynamic pricing (Pickup/Delivery × Capital/Interior)
- Product variants (sizes)
- Customization sections
- Extra charges
```

#### Combo Module
```
app/Models/Menu/
├── Combo.php                 // Combo definitions
├── ComboItem.php             // Fixed/Choice items
└── ComboItemOption.php       // Customer choices

Features:
- Fixed items (always included)
- Choice groups (customer selects)
- Single combo price
```

#### Promotion Module
```
app/Models/Menu/
├── Promotion.php             // Promotion header
├── PromotionItem.php         // 2x1, Percentage
└── BundlePromotionItem.php   // Bundle specials

Features:
- Daily specials (día-specific pricing)
- 2x1 (category-wide)
- Percentage discounts
- Bundle promotions
- Date/time/weekday restrictions
```

#### Restaurant Module
```
app/Models/
├── Restaurant.php

Features:
- GPS coordinates
- Geofencing (KML polygons)
- Delivery zone validation
- Opening hours by weekday
- Pickup/Delivery toggles
```

### 4. Infrastructure Services

#### Firebase Cloud Messaging
```php
FCMService:
├── sendToDevice($fcmToken, $data)
├── sendToCustomer($customerId, $data)
├── sendToMultipleCustomers($customerIds, $data)
└── sendToAllCustomers($data)

Features:
- Multi-device support
- Invalid token handling
- Batch sending (500/batch)
```

#### Laravel Sanctum
```
Token Management:
├── 365-day expiration
├── Device-specific naming
├── Multiple simultaneous tokens
├── Individual revocation
└── Bulk revocation (logout all)
```

#### Laravel Socialite
```
OAuth Providers:
├── Google
│   ├── Verify id_token
│   ├── Extract user data
│   └── Link/create account
└── Apple
    ├── Verify id_token
    ├── Extract user data
    └── Link/create account
```

---

## Data Flow

### Customer Registration Flow

```mermaid
flowchart TD
    A[User submits registration] --> B{Validate input}
    B -->|Invalid| C[Return 422 with errors]
    B -->|Valid| D{Email exists?}
    D -->|Yes| E[Return 422: Email taken]
    D -->|No| F[Hash password]
    F --> G[Create customer record]
    G --> H[Generate Sanctum token]
    H --> I[Create verification email job]
    I --> J[Return token + customer]
```

### OAuth Login Flow

```mermaid
flowchart TD
    A[App receives id_token from OAuth provider] --> B{Provider?}
    B -->|Google| C[Verify with Google API]
    B -->|Apple| D[Verify with Apple API]
    C --> E{Valid?}
    D --> E
    E -->|Invalid| F[Return 401: Invalid token]
    E -->|Valid| G[Extract email, name, avatar]
    G --> H{Customer exists with oauth_id?}
    H -->|Yes| I[Load existing customer]
    H -->|No| J{Customer exists with email?}
    J -->|Yes| K[Link oauth_id to existing]
    J -->|No| L[Create new customer]
    I --> M[Generate Sanctum token]
    K --> M
    L --> M
    M --> N[Return token + customer]
```

### Push Notification Flow

```mermaid
flowchart LR
    A[Admin triggers notification] --> B[FCMService]
    B --> C{Target}
    C -->|Single Customer| D[Get customer devices]
    C -->|Multiple Customers| E[Get all devices]
    C -->|All Customers| F[Get all active devices]
    D --> G[Filter active devices]
    E --> G
    F --> G
    G --> H[Extract FCM tokens]
    H --> I{Batch?}
    I -->|< 500| J[Send via Firebase]
    I -->|> 500| K[Split into batches]
    K --> J
    J --> L{Success?}
    L -->|Token invalid| M[Mark device inactive]
    L -->|Success| N[Update last_sent_at]
```

### Order Placement Flow (Future)

```mermaid
flowchart TD
    A[Customer builds order] --> B[Add products/combos]
    B --> C[Select customizations]
    C --> D[Choose restaurant]
    D --> E{Service type?}
    E -->|Delivery| F[Validate address in geofence]
    E -->|Pickup| G[Select pickup time]
    F --> H{In coverage?}
    H -->|No| I[Show error + suggest restaurant]
    H -->|Yes| J[Calculate pricing]
    G --> J
    J --> K[Apply promotions]
    K --> L[Calculate points earned]
    L --> M{Payment method?}
    M -->|Cash| N[Create order: PENDING]
    M -->|Card| O[Process payment via Infile]
    O --> P{Payment success?}
    P -->|Yes| N
    P -->|No| Q[Return payment error]
    N --> R[Send FCM to restaurant]
    R --> S[Send FCM to customer]
    S --> T[Return order confirmation]
```

---

## Infrastructure

### Deployment Architecture

```
┌─────────────────────────────────────────┐
│         Load Balancer / CDN             │
│         (Cloudflare / AWS ALB)          │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴───────┐
       │               │
┌──────▼──────┐ ┌──────▼──────┐
│ Web Server 1│ │ Web Server 2│
│  Laravel    │ │  Laravel    │
│  Nginx/PHP  │ │  Nginx/PHP  │
└──────┬──────┘ └──────┬──────┘
       │               │
       └───────┬───────┘
               │
┌──────────────▼──────────────────┐
│         Database Cluster        │
│         MariaDB Master          │
│         MariaDB Replicas        │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│    External Services            │
├─────────────────────────────────┤
│  • Firebase (Push Notifications)│
│  • Google OAuth                 │
│  • Apple Sign-In                │
│  • S3 (Image Storage)           │
└─────────────────────────────────┘
```

### Environment Configuration

**Development**:
```
APP_ENV=local
APP_DEBUG=true
DB_HOST=localhost
```

**Staging**:
```
APP_ENV=staging
APP_DEBUG=true
DB_HOST=staging-db.internal
```

**Production**:
```
APP_ENV=production
APP_DEBUG=false
DB_HOST=prod-db-cluster.internal
SANCTUM_STATEFUL_DOMAINS=
```

---

## Security Architecture

### Defense in Depth

```
Layer 1: Network Security
├── HTTPS Only (TLS 1.3)
├── Firewall Rules
└── DDoS Protection

Layer 2: API Gateway
├── Rate Limiting (5-120 req/min)
├── CORS (Restricted origins)
└── Request Validation

Layer 3: Authentication
├── Laravel Sanctum (Token-based)
├── OAuth Verification (Google/Apple)
└── Password Hashing (bcrypt)

Layer 4: Authorization
├── Token Validation
├── Resource Ownership Checks
└── Admin Role Permissions

Layer 5: Data Protection
├── Encrypted Database (at rest)
├── Sensitive Field Exclusion (API Resources)
└── Soft Deletes (Data Recovery)

Layer 6: Monitoring
├── Error Logging (Laravel Log)
├── Activity Logs (User Actions)
└── Security Alerts
```

### Authentication Security

```
Password Requirements:
├── Minimum 6 characters
├── Bcrypt hashing (cost factor: 10)
└── No plaintext storage

Token Security:
├── 64-character random token
├── SHA-256 hashing
├── 365-day expiration
├── Revocable per-device
└── Last used tracking

OAuth Security:
├── Token verification with provider
├── HTTPS-only communication
├── State parameter validation
└── No client secrets in mobile
```

---

## Scalability Considerations

### Current Capacity

- **API**: 120 requests/min per user
- **Database**: Single MariaDB instance
- **Storage**: Local filesystem
- **Push Notifications**: 500 devices per batch

### Scaling Strategy

**Horizontal Scaling (Phase 1)**:
```
1. Add more web servers behind load balancer
2. Session-less API (Sanctum tokens)
3. Database read replicas
4. Redis cache for:
   - Rate limiting
   - Session storage
   - Query results
```

**Vertical Scaling**:
```
1. Upgrade database server (CPU/RAM)
2. Optimize queries (N+1 prevention)
3. Index optimization
4. Database partitioning
```

**Caching Strategy**:
```
Laravel Cache:
├── Menu Items (1 hour TTL)
├── Categories (1 hour TTL)
├── Promotions (15 min TTL)
├── Restaurants (1 hour TTL)
└── Customer Data (Session duration)

CDN:
├── Product Images
├── Static Assets
└── API Documentation
```

### Performance Optimization

**Database**:
- Eager loading (prevent N+1)
- Composite indexes on frequent queries
- Query result caching
- Soft delete indexes

**API Response**:
- Conditional resource loading
- Pagination (default 20 items)
- Field selection (sparse fieldsets)
- Response compression (gzip)

**Push Notifications**:
- Batch sending (500/batch)
- Async jobs for large sends
- Exponential backoff on failures
- Dead token cleanup (weekly)

---

## Monitoring & Observability

### Metrics to Track

**Application**:
- Response times (P50, P95, P99)
- Error rates (4xx, 5xx)
- API endpoint usage
- Token generation/revocation

**Infrastructure**:
- Server CPU/Memory
- Database connections
- Disk I/O
- Network bandwidth

**Business**:
- Customer registrations
- Login success rate
- OAuth adoption
- Push notification delivery rate

### Logging Strategy

```
Laravel Log Channels:
├── daily: Application logs (30 day retention)
├── stack: Error logs (90 day retention)
└── syslog: Security events (1 year retention)

Log Levels:
├── ERROR: Exceptions, critical failures
├── WARNING: Deprecated features, recoverable errors
├── INFO: User actions, API calls
└── DEBUG: Query logs (dev only)
```

---

## Future Architecture Enhancements

### Phase 2 (Q1 2026)

1. **Order Processing System**:
   - Real-time order tracking
   - Kitchen display system
   - Driver assignment
   - Payment processing (Infile)

2. **Loyalty Points Engine**:
   - Points accumulation
   - Points redemption
   - Tier upgrades
   - Expiration management

3. **Call Center Integration**:
   - Phone order entry
   - Customer lookup
   - Order management

### Phase 3 (Q2 2026)

1. **Advanced Analytics**:
   - Sales reports
   - Customer insights
   - Promotion effectiveness
   - Inventory predictions

2. **Microservices Split**:
   - Order Service
   - Payment Service
   - Notification Service
   - Customer Service

3. **Event-Driven Architecture**:
   - Message queue (RabbitMQ/SQS)
   - Event sourcing
   - CQRS pattern

---

## Conclusion

SubwayApp is built on a solid foundation with Laravel 12, following best practices for security, scalability, and maintainability. The architecture supports:

- ✅ Multi-channel authentication
- ✅ Real-time push notifications
- ✅ Complex business logic (promotions, combos)
- ✅ Geographic features (geofencing)
- ✅ Extensible domain model
- ✅ API-first design

The system is ready for Phase 1 (mobile app demo) and architected to scale for Phases 2-3 (orders, payments, analytics).
