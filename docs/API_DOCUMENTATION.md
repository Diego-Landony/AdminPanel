# SubwayApp API Documentation

**Version**: 1.0.0
**Base URL**: `https://admin.subwaycardgt.com/api/v1`
**Updated**: November 2025

---

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [Architecture](#architecture)
4. [API Endpoints](#api-endpoints)
5. [Rate Limiting](#rate-limiting)
6. [Error Handling](#error-handling)
7. [Data Models](#data-models)
8. [Common Workflows](#common-workflows)
9. [Security Best Practices](#security-best-practices)
10. [Testing & Development](#testing--development)

---

## Introduction

The SubwayApp API provides a RESTful interface for the Subway Guatemala mobile application. This API enables customers to:

- Authenticate using email/password or social login (Google)
- Manage their profile, addresses, and billing information (NITs)
- Register devices for push notifications
- Browse menu, combos, and promotions
- Place orders (upcoming feature)
- Track loyalty points and customer tier status

### Key Features

- **Multi-device support**: Customers can use multiple devices simultaneously
- **OAuth integration**: Google Sign-In
- **Push notifications**: Firebase Cloud Messaging (FCM)
- **Customer loyalty program**: Bronze, Silver, Gold, Platinum tiers
- **Secure authentication**: Laravel Sanctum with long-lived tokens
- **Comprehensive documentation**: Interactive Swagger UI

### Tech Stack

- **Framework**: Laravel 12 (PHP 8.4)
- **Database**: MariaDB
- **Authentication**: Laravel Sanctum
- **OAuth**: Laravel Socialite
- **Push Notifications**: Firebase Cloud Messaging
- **Documentation**: L5-Swagger (OpenAPI 3.0)

---

## Authentication

### Overview

The API uses **Laravel Sanctum** for token-based authentication. All protected endpoints require a Bearer token in the `Authorization` header.

### Authentication Methods

1. **Email & Password** (Traditional)
2. **Google OAuth** (Social Login)

### Obtaining a Token

#### Traditional Login

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "customer@example.com",
  "password": "password",
  "device_name": "iPhone 15 Pro"  // optional
}
```

**Response**:
```json
{
  "access_token": "1|abc123...",
  "token_type": "Bearer",
  "expires_in": 525600,
  "customer": {
    "id": 1,
    "name": "Carlos López",
    "email": "customer@example.com",
    "subway_card": "1234567890",
    "points": 450,
    "customer_type": {
      "name": "Silver",
      "multiplier": 1.5
    }
  }
}
```

#### Google OAuth

```bash
POST /api/v1/auth/oauth/google
Content-Type: application/json

{
  "id_token": "<google_id_token_from_mobile_sdk>",
  "device_name": "iPhone 15 Pro"
}
```

### Using the Token

Include the token in the `Authorization` header for all protected requests:

```bash
GET /api/v1/profile
Authorization: Bearer 1|abc123...
Accept: application/json
```

### Token Lifecycle

- **Expiration**: 365 days (525,600 minutes)
- **Revocation**: Tokens can be revoked individually or all at once
- **Refresh**: Not currently implemented (long-lived tokens)

### Revoking Tokens

**Logout (current device)**:
```bash
POST /api/v1/auth/logout
Authorization: Bearer <token>
```

**Logout all devices**:
```bash
POST /api/v1/auth/logout-all
Authorization: Bearer <token>
```

---

## Architecture

### System Architecture

```
┌─────────────────┐
│  Mobile App     │
│  (React Native) │
└────────┬────────┘
         │ HTTPS
         │ Bearer Token
         ▼
┌─────────────────────────────┐
│   Laravel API Gateway       │
│   /api/v1/*                 │
├─────────────────────────────┤
│  • ForceJsonResponse        │
│  • Rate Limiting            │
│  • Sanctum Auth             │
│  • CORS                     │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│   Controllers Layer         │
├─────────────────────────────┤
│  • AuthController           │
│  • OAuthController          │
│  • ProfileController        │
│  • DeviceController         │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│   Services Layer            │
├─────────────────────────────┤
│  • SocialAuthService        │
│  • FCMService               │
└────────┬────────────────────┘
         │
         ▼
┌──────────────────┬──────────────────┐
│   MariaDB        │   Firebase       │
│   • customers    │   • FCM Tokens   │
│   • devices      │   • Push Notify  │
│   • tokens       │                  │
└──────────────────┴──────────────────┘
```

### Authentication Flow

```
┌──────────┐           ┌─────────┐           ┌──────────┐
│  Mobile  │           │   API   │           │ Database │
│   App    │           │ Server  │           │          │
└────┬─────┘           └────┬────┘           └────┬─────┘
     │                      │                     │
     │  POST /auth/login    │                     │
     │ (email, password)    │                     │
     │─────────────────────>│                     │
     │                      │ Verify credentials  │
     │                      │────────────────────>│
     │                      │<────────────────────│
     │                      │  Generate token     │
     │                      │────────────────────>│
     │  <token + customer>  │                     │
     │<─────────────────────│                     │
     │                      │                     │
     │  GET /profile        │                     │
     │  Authorization:      │                     │
     │  Bearer <token>      │                     │
     │─────────────────────>│                     │
     │                      │  Validate token     │
     │                      │────────────────────>│
     │                      │<────────────────────│
     │  <customer data>     │                     │
     │<─────────────────────│                     │
```

### OAuth Flow (Google)

```
┌──────────┐     ┌─────────┐     ┌──────────┐     ┌────────────┐
│  Mobile  │     │   API   │     │ Database │     │   Google   │
│   App    │     │ Server  │     │          │     │    OAuth   │
└────┬─────┘     └────┬────┘     └────┬─────┘     └──────┬─────┘
     │                │               │                   │
     │ User taps "Sign in with Google"                   │
     │────────────────────────────────────────────────────>│
     │                │               │  <Google SDK>     │
     │                │               │                   │
     │                │               │  <id_token>       │
     │<────────────────────────────────────────────────────│
     │                │               │                   │
     │ POST /auth/oauth/google        │                   │
     │ { id_token }   │               │                   │
     │───────────────>│               │                   │
     │                │ Verify token  │                   │
     │                │───────────────────────────────────>│
     │                │<───────────────────────────────────│
     │                │ Find/Create   │                   │
     │                │ customer      │                   │
     │                │──────────────>│                   │
     │                │<──────────────│                   │
     │                │ Generate      │                   │
     │                │ Sanctum token │                   │
     │                │──────────────>│                   │
     │ <token+data>   │               │                   │
     │<───────────────│               │                   │
```

### Database Schema (Core Tables)

**customers**:
- `id`, `name`, `email`, `password` (nullable for OAuth)
- `google_id`, `oauth_provider`
- `subway_card`, `birth_date`, `gender`, `phone`
- `customer_type_id`, `points`
- `email_verified_at`, `last_login_at`

**personal_access_tokens** (Sanctum):
- `id`, `tokenable_type`, `tokenable_id`
- `name` (device name), `token` (hashed)
- `abilities`, `last_used_at`, `expires_at`

**customer_devices**:
- `id`, `customer_id`, `sanctum_token_id`
- `fcm_token`, `device_identifier`
- `device_type`, `device_name`, `device_model`
- `app_version`, `os_version`
- `is_active`, `last_used_at`

**customer_addresses**:
- `id`, `customer_id`, `label`
- `address_line`, `latitude`, `longitude`
- `delivery_notes`, `is_default`

**customer_nits**:
- `id`, `customer_id`, `nit`
- `nit_type`, `business_name`
- `is_default`

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | No | Register new customer |
| POST | `/auth/login` | No | Login with email/password |
| POST | `/auth/oauth/google` | No | Login with Google |
| POST | `/auth/logout` | Yes | Logout current device |
| POST | `/auth/logout-all` | Yes | Logout all devices |
| POST | `/auth/forgot-password` | No | Request password reset |
| POST | `/auth/reset-password` | No | Reset password with token |
| POST | `/auth/email/verify/{id}/{hash}` | No | Verify email |
| POST | `/auth/email/resend` | Yes | Resend verification email |
| POST | `/auth/refresh` | Yes | Refresh token |

### Profile Management

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/profile` | Yes | Get customer profile |
| PUT | `/profile` | Yes | Update profile |
| DELETE | `/profile` | Yes | Delete account |
| POST | `/profile/avatar` | Yes | Upload avatar |
| DELETE | `/profile/avatar` | Yes | Delete avatar |
| PUT | `/profile/password` | Yes | Change password |

### Device Management

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/devices` | Yes | List customer devices |
| POST | `/devices/register` | Yes | Register device for push |
| DELETE | `/devices/{device}` | Yes | Remove device |

---

## Rate Limiting

### Rate Limit Groups

| Group | Limit | Applies To |
|-------|-------|------------|
| **auth** | 5 requests/minute | Login, Register, Forgot Password |
| **oauth** | 10 requests/minute | Google OAuth |
| **api** | 120 requests/minute | All authenticated endpoints |

### Rate Limit Headers

Response includes rate limit information:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1699564800
```

### Handling Rate Limits

**Status Code**: `429 Too Many Requests`

```json
{
  "message": "Too many requests. Please try again in 60 seconds.",
  "retry_after": 60
}
```

---

## Error Handling

### Standard Error Response

All errors follow this structure:

```json
{
  "message": "Human-readable error message",
  "errors": {
    "field_name": [
      "Specific validation error"
    ]
  }
}
```

### HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| **200** | OK | Successful request |
| **201** | Created | Resource created |
| **204** | No Content | Successful deletion |
| **400** | Bad Request | Malformed request |
| **401** | Unauthorized | Missing or invalid token |
| **403** | Forbidden | Valid token but no permission |
| **404** | Not Found | Resource doesn't exist |
| **422** | Unprocessable Entity | Validation failed |
| **429** | Too Many Requests | Rate limit exceeded |
| **500** | Internal Server Error | Server error |

### Common Error Examples

**Validation Error (422)**:
```json
{
  "message": "The given data was invalid",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 6 characters."
    ]
  }
}
```

**Authentication Error (401)**:
```json
{
  "message": "Unauthenticated."
}
```

**Authorization Error (403)**:
```json
{
  "message": "This action is unauthorized."
}
```

---

## Data Models

### Customer Resource

```json
{
  "id": 1,
  "name": "Carlos López",
  "email": "carlos@example.com",
  "subway_card": "1234567890",
  "birth_date": "1990-05-15",
  "gender": "male",
  "phone": "+502 5555-1234",
  "timezone": "America/Guatemala",
  "avatar": "https://example.com/avatar.jpg",
  "oauth_provider": "local",
  "email_verified_at": "2025-01-15T10:30:00Z",
  "last_login_at": "2025-11-07T14:20:00Z",
  "last_activity_at": "2025-11-07T14:25:00Z",
  "last_purchase_at": "2025-11-05T12:00:00Z",
  "points": 450,
  "points_updated_at": "2025-11-05T12:05:00Z",
  "is_online": true,
  "status": "active",
  "created_at": "2025-01-15T10:30:00Z",
  "customer_type": {
    "id": 2,
    "name": "Silver",
    "points_required": 500,
    "multiplier": 1.5,
    "color": "#C0C0C0"
  },
  "addresses_count": 2,
  "nits_count": 1,
  "devices_count": 2
}
```

### Device Resource

```json
{
  "id": 1,
  "device_name": "iPhone 15 Pro",
  "device_type": "ios",
  "device_model": "iPhone15,2",
  "app_version": "1.0.0",
  "os_version": "17.0",
  "last_used_at": "2025-11-07T14:25:00Z",
  "is_active": true,
  "is_current_device": true,
  "created_at": "2025-10-01T08:00:00Z"
}
```

### Address Resource

```json
{
  "id": 1,
  "label": "Casa",
  "address_line": "Avenida Reforma 10-00, Zona 9, Guatemala",
  "latitude": 14.6000,
  "longitude": -90.5000,
  "delivery_notes": "Casa blanca con portón negro",
  "is_default": true,
  "created_at": "2025-01-15T10:35:00Z",
  "updated_at": "2025-01-15T10:35:00Z"
}
```

### NIT Resource

```json
{
  "id": 1,
  "nit": "12345678",
  "nit_type": "personal",
  "business_name": null,
  "is_default": true,
  "created_at": "2025-01-15T10:35:00Z",
  "updated_at": "2025-01-15T10:35:00Z"
}
```

---

## Common Workflows

### 1. Register New Customer

```bash
# 1. Register
POST /api/v1/auth/register
{
  "name": "María García",
  "email": "maria@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "device_name": "iPhone 15"
}

# Response includes token
{
  "access_token": "1|xyz789...",
  "token_type": "Bearer",
  "customer": { ... }
}

# 2. Use token for subsequent requests
GET /api/v1/profile
Authorization: Bearer 1|xyz789...
```

### 2. Login with Google

```javascript
// Mobile app (React Native)
import { GoogleSignin } from '@react-native-google-signin/google-signin';

// 1. Get Google ID token
const { idToken } = await GoogleSignin.signIn();

// 2. Send to API
const response = await fetch('https://api.subway.gt/api/v1/auth/oauth/google', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    id_token: idToken,
    device_name: 'iPhone 15 Pro'
  })
});

// 3. Store token
const { access_token } = await response.json();
await AsyncStorage.setItem('token', access_token);
```

### 3. Register Device for Push Notifications

```javascript
// Mobile app - Get FCM token
import messaging from '@react-native-firebase/messaging';

const fcmToken = await messaging().getToken();

// Register with API
await fetch('https://api.subway.gt/api/v1/devices/register', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    fcm_token: fcmToken,
    device_name: 'iPhone 15 Pro',
    device_type: 'ios',
    device_model: 'iPhone15,2',
    app_version: '1.0.0',
    os_version: '17.0'
  })
});
```

### 4. Manage Multiple Devices

```bash
# List all devices
GET /api/v1/devices
Authorization: Bearer <token>

# Response
{
  "data": [
    {
      "id": 1,
      "device_name": "iPhone 15 Pro",
      "device_type": "ios",
      "is_current_device": true,
      "last_used_at": "2025-11-07T14:25:00Z"
    },
    {
      "id": 2,
      "device_name": "iPad Air",
      "device_type": "ios",
      "is_current_device": false,
      "last_used_at": "2025-11-06T20:00:00Z"
    }
  ]
}

# Remove a device
DELETE /api/v1/devices/2
Authorization: Bearer <token>
```

### 5. Update Profile

```bash
PUT /api/v1/profile
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Carlos Alberto López",
  "phone": "+502 5555-9999",
  "birth_date": "1990-05-15",
  "gender": "male"
}
```

---

## Security Best Practices

### For Mobile App Developers

1. **Store tokens securely**:
   - Use Keychain (iOS) or Keystore (Android)
   - Never store in plain text or UserDefaults/SharedPreferences

2. **Always use HTTPS**:
   - All API calls must use HTTPS
   - Implement certificate pinning for production

3. **Validate server certificates**:
   ```javascript
   // React Native example
   const response = await fetch(url, {
     method: 'GET',
     headers: { 'Authorization': `Bearer ${token}` },
     // Certificate pinning configuration
   });
   ```

4. **Handle token expiration**:
   - Tokens expire after 365 days
   - Implement auto-logout after expiration
   - Prompt user to re-authenticate

5. **Protect OAuth tokens**:
   - Never log or expose id_tokens
   - Don't store OAuth provider tokens

6. **Implement timeout**:
   - Set reasonable request timeouts (30s)
   - Handle network failures gracefully

### For Backend

- Rate limiting prevents brute force attacks
- OAuth tokens verified with Google servers
- Passwords hashed with bcrypt
- Soft deletes for data recovery
- CORS configured for production domains

---

## Testing & Development

### Interactive API Testing

Access Swagger UI for interactive testing:

**URL**: `https://admin.subwaycardgt.com/api/documentation`

1. Open Swagger UI
2. Click "Authorize" button
3. Enter: `Bearer <your_token>`
4. Test endpoints with "Try it out"

### Test Credentials

For development/testing purposes:

| Email | Password | Type | Points | Tier |
|-------|----------|------|--------|------|
| `customer.bronze@subway.gt` | `password` | Local | 150 | Bronze |
| `customer.silver@subway.gt` | `password` | Local | 750 | Silver |
| `customer.gold.google@subway.gt` | N/A | Google | 1500 | Gold |
| `api@subway.gt` | `password` | Local | 500 | Silver |

### Using cURL

```bash
# Login
curl -X POST https://admin.subwaycardgt.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "customer.bronze@subway.gt",
    "password": "password"
  }'

# Get Profile
curl -X GET https://admin.subwaycardgt.com/api/v1/profile \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Accept: application/json"
```

### Using Postman

1. Import OpenAPI spec: `/docs/api-docs.json`
2. Set environment variable `{{token}}`
3. Add header: `Authorization: Bearer {{token}}`
4. Run collection

### Environment Variables

Required for development:

```env
# App
APP_URL=http://localhost:8000
API_URL=http://localhost:8000/api/v1

# Google OAuth
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret

# Firebase
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
```

---

## Changelog

### v1.0.0 (November 2025)

**Initial Release**:
- ✅ Authentication API (email/password)
- ✅ OAuth integration (Google)
- ✅ Profile management
- ✅ Device registration
- ✅ Firebase Cloud Messaging
- ✅ Swagger documentation
- ✅ Rate limiting
- ✅ Multi-device support

**Pending Features**:
- ⏳ Menu browsing API
- ⏳ Order placement
- ⏳ Loyalty points tracking
- ⏳ Promotions API

---

## Support


**Email**: dlima@subwayguatemala.com
**Swagger UI**: https://admin.subwaycardgt.com/api/documentation

---

## License

Proprietary - Subway Guatemala © 2025