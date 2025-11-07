# SubwayApp - Admin Panel & API

<div align="center">

**Restaurant Management System & Mobile API for Subway Guatemala**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://php.net)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript)](https://www.typescriptlang.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-38B2AC?logo=tailwind-css)](https://tailwindcss.com)

[Features](#features) • [Tech Stack](#tech-stack) • [Quick Start](#quick-start) • [Documentation](#documentation) • [API](#api-rest) • [Contributing](#contributing)

</div>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [API REST](#api-rest)
- [Documentation](#documentation)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

---

## 🎯 Overview

SubwayApp is a comprehensive restaurant management platform designed specifically for Subway Guatemala. The system consists of:

1. **Admin Web Panel**: Full-featured management interface for restaurants, menu, customers, and orders
2. **REST API**: Secure, scalable backend for mobile applications
3. **Mobile App** (upcoming): React Native app for customer orders

### Key Highlights

- 🔐 **Multi-channel Authentication**: Email/password + OAuth (Google, Apple)
- 📱 **Multi-device Support**: Customers can use multiple devices simultaneously
- 🔔 **Push Notifications**: Firebase Cloud Messaging integration
- 🎁 **Complex Promotions**: Daily specials, 2x1, percentage discounts, bundles
- 🍔 **Advanced Menu System**: Products with variants, combos, customizations
- 📍 **Geofencing**: Delivery zone validation with KML polygons
- 👥 **Customer Loyalty**: Bronze/Silver/Gold/Platinum tiers with points
- 📊 **Activity Tracking**: Comprehensive audit logs

---

## ✨ Features

### Admin Panel

#### 1. **Customer Management**
- Customer profiles with Subway Card integration
- Multiple delivery addresses per customer
- Multiple NITs (tax IDs) for invoicing
- Customer tier system (Bronze → Platinum)
- Points tracking and history
- Device management (push notification tokens)

#### 2. **Restaurant Management**
- GPS coordinates and map visualization
- **Geofencing**: Define delivery coverage zones with KML
- Operating hours by day of week
- Individual toggles for pickup/delivery
- Real-time open/closed status
- Minimum order amounts

#### 3. **Menu Management**
- **Categories**: Sandwiches, Drinks, Desserts, etc.
- **Products** with 4 price points:
  - Pickup Capital / Delivery Capital
  - Pickup Interior / Delivery Interior
- **Product Variants**: Automatic size management (15cm, 30cm)
- **Customization Sections**:
  - Bread types (White, Wheat, Flatbread)
  - Vegetables (Lettuce, Tomato, etc.)
  - Sauces (Mustard, Chipotle, etc.)
  - Extras with upcharge (Avocado +Q10, Extra Cheese +Q5)

#### 4. **Combo System**
- **Fixed Items**: Always included (e.g., cookie + drink)
- **Choice Groups**: Customer selects (e.g., choose your sub)
- Dynamic pricing based on selections
- Multiple customization options per item

#### 5. **Promotion Engine**
Four promotion types:

**Sub del Día (Daily Special)**:
- Product-specific discounted price
- Day-of-week targeting
- Doesn't stack with other promotions

**2x1 (Two-for-One)**:
- Applies to entire category
- Buy 2, pay for the most expensive
- Example: Coca-Cola Q15 + Sprite Q10 = Pay Q15

**Percentage Discount**:
- 5%, 10%, 20%, etc. on selected products
- Can apply to categories or individual items

**Bundle Special**:
- Fixed-price product bundles
- Mix-and-match items
- Choice groups supported

**All promotions support**:
- Date range restrictions
- Time-of-day restrictions
- Weekday restrictions
- Service type filtering (pickup/delivery only)

#### 6. **Access Control**
- User authentication and management
- Role-based permissions (Admin, Manager, Supervisor, Marketing)
- Granular permissions per module (view/create/edit/delete)
- Comprehensive activity logging
- User session tracking

### REST API (v1)

#### Authentication
- ✅ Email/password registration and login
- ✅ Google OAuth integration (Sign-in with Google)
- ✅ Apple Sign-In integration
- ✅ Multi-device token management
- ✅ Email verification flow
- ✅ Password reset flow
- ✅ Logout (single device or all devices)

#### Profile Management
- ✅ Get customer profile
- ✅ Update profile (name, email, phone, etc.)
- ✅ Avatar upload/delete
- ✅ Change password
- ✅ Delete account

#### Device Management
- ✅ Register device for push notifications
- ✅ List customer devices
- ✅ Remove device

#### Infrastructure
- ✅ Laravel Sanctum (token-based auth)
- ✅ Rate limiting (5-120 req/min)
- ✅ Firebase Cloud Messaging (push notifications)
- ✅ Swagger UI (interactive API documentation)
- ✅ CORS configuration
- ✅ Comprehensive error handling

---

## 🛠 Tech Stack

### Backend
```
Laravel        12.0     PHP Framework
PHP            8.4      Programming Language
MariaDB        10.6+    Database
Sanctum        4.2      API Authentication
Socialite      5.23     OAuth Integration
Firebase PHP   7.23     Push Notifications
L5-Swagger     9.0      API Documentation
```

### Frontend (Admin Panel)
```
React          19.0     UI Library
Inertia.js     2.0      Server-Side Routing
TypeScript     5.0      Type Safety
Tailwind CSS   4.0      Styling
Vite           5.0      Build Tool
```

### Mobile App (Upcoming)
```
React Native   0.73+    Cross-platform Framework
TypeScript     5.0      Type Safety
Firebase       Latest   Push Notifications
```

### Development Tools
```
Pest           3.8      Testing Framework
Pint           1.24     Code Formatter
Laravel Boost  1.0      Development Assistant
Vite           5.0      Frontend Build Tool
```

---

## 📁 Project Structure

```
AdminPanel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/              # API Controllers
│   │   │   │   ├── Auth/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   └── OAuthController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   └── DeviceController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── Menu/
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ComboController.php
│   │   │   │   ├── SectionController.php
│   │   │   │   └── PromotionController.php
│   │   │   ├── RestaurantController.php
│   │   │   ├── RoleController.php
│   │   │   └── UserController.php
│   │   ├── Middleware/
│   │   │   └── ForceJsonResponse.php
│   │   ├── Requests/Api/V1/         # API Form Requests
│   │   └── Resources/               # API Resources
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── CustomerDevice.php
│   │   ├── Menu/
│   │   │   ├── Category.php
│   │   │   ├── Product.php
│   │   │   ├── Combo.php
│   │   │   ├── Promotion.php
│   │   │   └── Section.php
│   │   ├── Restaurant.php
│   │   ├── Role.php
│   │   └── User.php
│   └── Services/
│       ├── FCMService.php           # Push Notifications
│       └── SocialAuthService.php    # OAuth Logic
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/
│   ├── API_DOCUMENTATION.md         # Complete API guide
│   ├── ARCHITECTURE.md              # System architecture
│   ├── DEVELOPER_GUIDE.md           # Development guide
│   ├── API-REST-AUTH-PLAN.md        # Implementation plan
│   └── RULES.md                     # Business rules
├── resources/
│   ├── js/
│   │   ├── components/              # React components
│   │   ├── layouts/                 # Page layouts
│   │   ├── pages/                   # Inertia.js pages
│   │   └── types/                   # TypeScript types
│   └── views/
├── routes/
│   ├── api.php                      # API routes
│   ├── web.php                      # Web routes
│   └── console.php                  # Artisan commands
├── storage/
│   ├── app/firebase/                # Firebase credentials
│   └── api-docs/                    # Swagger JSON
└── tests/
    ├── Feature/Api/                 # API integration tests
    └── Unit/                        # Unit tests
```

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 18+
- MariaDB 10.6+
- Git

### Installation

```bash
# 1. Clone repository
git clone <repository-url>
cd AdminPanel

# 2. Install dependencies
composer install
npm install

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# 4. Configure database
# Edit .env with your database credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=subwayapp
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# 5. Run migrations and seed data
php artisan migrate:fresh --seed

# 6. Build frontend assets
npm run build

# 7. Start development servers
composer run dev
# This starts:
# - Laravel server (http://localhost:8000)
# - Queue worker
# - Real-time logs (pail)
# - Vite dev server (http://localhost:5173)
```

### Access the Application

**Admin Panel**:
- URL: `http://localhost:8000`
- Email: `admin@admin.com`
- Password: `admin`

**API Documentation (Swagger UI)**:
- URL: `http://localhost:8000/api/documentation`

**Test API Credentials**:
- Email: `customer.bronze@subway.gt` | Password: `password`
- Email: `customer.silver@subway.gt` | Password: `password`
- Email: `api@subway.gt` | Password: `password`

---

## 🔌 API REST

### Base URL

```
Development: http://localhost:8000/api/v1
Production:  https://admin.subwaycardgt.com/api/v1
```

### Quick Example

```bash
# 1. Login to get token
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer.bronze@subway.gt",
    "password": "password",
    "device_name": "Test Device"
  }'

# Response:
# {
#   "access_token": "1|xyz123...",
#   "token_type": "Bearer",
#   "expires_in": 525600,
#   "customer": { ... }
# }

# 2. Use token for authenticated requests
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer 1|xyz123..." \
  -H "Accept: application/json"
```

### Available Endpoints

**Authentication** (11 endpoints):
- `POST /auth/register` - Register new customer
- `POST /auth/login` - Login with email/password
- `POST /auth/oauth/google` - Login with Google
- `POST /auth/oauth/apple` - Login with Apple
- `POST /auth/logout` - Logout current device
- `POST /auth/logout-all` - Logout all devices
- `POST /auth/forgot-password` - Request password reset
- `POST /auth/reset-password` - Reset password
- `POST /auth/email/verify/{id}/{hash}` - Verify email
- `POST /auth/email/resend` - Resend verification
- `POST /auth/refresh` - Refresh token

**Profile** (6 endpoints):
- `GET /profile` - Get customer profile
- `PUT /profile` - Update profile
- `DELETE /profile` - Delete account
- `POST /profile/avatar` - Upload avatar
- `DELETE /profile/avatar` - Delete avatar
- `PUT /profile/password` - Change password

**Devices** (3 endpoints):
- `GET /devices` - List customer devices
- `POST /devices/register` - Register device for push
- `DELETE /devices/{device}` - Remove device

### Rate Limiting

| Group | Limit | Applies To |
|-------|-------|------------|
| auth | 5 req/min | Login, Register, Forgot Password |
| oauth | 10 req/min | Google OAuth, Apple OAuth |
| api | 120 req/min | All authenticated endpoints |

### Documentation

**Interactive API Docs**: [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)

**Complete Guide**: [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)

---

## 📚 Documentation

### Core Documentation

- **[API Documentation](docs/API_DOCUMENTATION.md)** - Complete API reference with examples
- **[Architecture Guide](docs/ARCHITECTURE.md)** - System architecture, diagrams, and data flows
- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Development setup, best practices, troubleshooting
- **[Implementation Plan](docs/API-REST-AUTH-PLAN.md)** - Detailed implementation roadmap
- **[Business Rules](docs/RULES.md)** - Domain logic and business constraints

### Additional Guides

- **[Deployment Guide](docs/DEPLOYMENT-PRODUCTION.md)** - Production deployment instructions
- **[Test Improvement Plan](docs/TEST_IMPROVEMENT_PLAN.md)** - Testing strategy
- **[UX/UI Guidelines](docs/UX-UI.md)** - Frontend design patterns

---

## 💻 Development

### Running the Application

```bash
# Start all services (recommended)
composer run dev

# Or start services individually:
php artisan serve              # Laravel: http://localhost:8000
npm run dev                    # Vite: http://localhost:5173
php artisan queue:listen       # Queue worker
php artisan pail               # Real-time logs
```

### Code Quality

```bash
# Format code with Laravel Pint
vendor/bin/pint

# Run only on changed files
vendor/bin/pint --dirty

# Check without fixing
vendor/bin/pint --test
```

### Database Management

```bash
# Create migration
php artisan make:migration create_orders_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh database with seeders
php artisan migrate:fresh --seed
```

### Tinker (REPL)

```bash
php artisan tinker

# Examples:
>>> Customer::count()
>>> $customer = Customer::with('orders')->first()
>>> $customer->orders
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/V1/Auth/LoginTest.php

# Run with filter
php artisan test --filter=login

# Run with coverage
php artisan test --coverage
```

### Test Coverage

```
Current Coverage: 15 tests
├── Authentication: 5 tests ✅
├── Registration: 5 tests ✅
├── Password Management: 5 tests ✅
└── OAuth: Pending ⏳
```

### Writing Tests (Pest)

```php
// tests/Feature/Api/V1/ExampleTest.php
use function Pest\Laravel\{actingAs, postJson};

it('can create order', function () {
    $customer = Customer::factory()->create();

    $response = actingAs($customer, 'sanctum')
        ->postJson('/api/v1/orders', [
            'restaurant_id' => 1,
            'items' => [...]
        ]);

    $response->assertCreated();
});
```

---

## 🚢 Deployment

### Environment Setup

```bash
# 1. Set production environment
APP_ENV=production
APP_DEBUG=false

# 2. Configure database
DB_HOST=your-production-host
DB_DATABASE=subwayapp_prod

# 3. Set OAuth credentials
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
APPLE_CLIENT_ID=...

# 4. Configure Firebase
FIREBASE_CREDENTIALS=storage/app/firebase/prod-credentials.json
```

### Deployment Steps

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm ci

# 3. Clear and cache config
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force

# 5. Build frontend
npm run build

# 6. Restart services
php artisan queue:restart
php artisan octane:reload  # If using Octane
```

### Production Checklist

- [ ] `.env` configured with production values
- [ ] `APP_DEBUG=false`
- [ ] Database backups configured
- [ ] SSL certificate installed (HTTPS)
- [ ] Queue worker running (supervisor)
- [ ] Scheduler configured (cron)
- [ ] Log rotation configured
- [ ] Firebase credentials uploaded
- [ ] OAuth credentials configured
- [ ] Rate limiting tested
- [ ] Error monitoring active

---

## 🗺 Roadmap

### ✅ Completed (October 2025)

- Admin panel with full catalog management
- Customer, restaurant, menu, combo, promotion modules
- Role-based access control
- Activity logging
- Geofencing for delivery zones

### 🚧 Phase 1: Mobile App Demo (Nov-Dec 2025)

**Mobile App**:
- Customer registration and login
- Address management
- Menu browsing with categories
- Product customization
- Shopping cart
- Order placement (cash only)
- Order history

**Admin Panel**:
- Basic order dashboard
- Order status management
- Simple driver assignment

### ⏳ Phase 2: Full App + Payments (Jan-Mar 2026)

**Mobile App**:
- Card payments (Infile integration)
- Order tracking in real-time
- Push notifications
- Loyalty points display

**Admin Panel**:
- Complete loyalty points system
- Call center order entry
- Advanced analytics

### 📅 Phase 3: Stabilization (April 2026)

- Bug fixes and optimization
- Performance improvements
- User feedback integration

**Target Launch**: April 2026

---

## 🤝 Contributing

### Getting Started

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `php artisan test`
5. Format code: `vendor/bin/pint`
6. Commit: `git commit -m 'Add amazing feature'`
7. Push: `git push origin feature/amazing-feature`
8. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standard (enforced by Pint)
- Write Pest tests for new features
- Document API endpoints with OpenAPI annotations
- Use TypeScript for frontend code
- Follow existing naming conventions

### Commit Messages

```
feat: Add order placement API
fix: Resolve token expiration issue
docs: Update API documentation
test: Add OAuth integration tests
refactor: Simplify promotion calculation logic
```

---

## 📄 License

Proprietary - Subway Guatemala © 2025

All rights reserved. This software and associated documentation files are the exclusive property of Subway Guatemala.

---

## 📞 Support

**Technical Issues**: Report at repository issues
**Email**: admin@subway.gt
**Documentation**: [/docs](/docs)
**API Docs**: http://admin.subwaycardgt.com/api/documentation

---

## 🙏 Acknowledgments

- **Laravel Team** - Excellent framework and documentation
- **React Team** - Modern UI library
- **Inertia.js** - Seamless server-side routing
- **Tailwind CSS** - Utility-first CSS framework
- **Pest PHP** - Elegant testing framework

---

<div align="center">

**Built with ❤️ for Subway Guatemala**

[⬆ Back to Top](#subwayapp---admin-panel--api)

</div>
