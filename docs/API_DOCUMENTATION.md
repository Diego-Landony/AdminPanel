# SubwayApp API Documentation

**Version**: 1.0.2
**Base URL**: `https://admin.subwaycardgt.com/api/v1`
**Updated**: November 10, 2025

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
  "os": "ios",                                    // optional: "ios", "android", "web"
  "device_identifier": "ABC123-DEF456-GHI789",    // RECOMMENDED: unique device UUID
  "device_fingerprint": "sha256_hash..."          // optional: SHA256 hash of device characteristics
}
```

**Device Tracking (Recommended)**:
- `device_identifier`: Unique UUID for this device (generated once and stored locally)
  - **If provided**: System tracks device, increments login count, updates trust score
  - **If omitted**: Only authentication token is created (no device tracking)
- `device_fingerprint`: SHA256 hash of device characteristics (hardware ID, OS version, etc.)
  - Used for security and fraud detection
  - Helps detect if device was rooted/jailbroken or tampered

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
  "os": "ios",                                    // optional: "ios", "android", "web"
  "device_identifier": "ABC123-DEF456-GHI789",    // RECOMMENDED: unique device UUID
  "device_fingerprint": "sha256_hash..."          // optional: SHA256 hash
}
```

> **Note**: Google OAuth also supports device tracking. Include `device_identifier` and `device_fingerprint` for the same benefits as traditional login.

### Using the Token

Include the token in the `Authorization` header for all protected requests:

```bash
GET /api/v1/profile
Authorization: Bearer 1|abc123...
Accept: application/json
```

### Token Lifecycle

- **Expiration**: 365 days (525,600 minutes)
- **Token Limit**: Maximum 5 active tokens per user
- **Revocation**: Tokens can be revoked individually or all at once
- **Refresh**: Not currently implemented (long-lived tokens)
- **Auto-cleanup**: Expired tokens are automatically deleted after 7 days

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

### Token Management & Scalability

The API implements automatic token management to ensure database scalability and prevent token accumulation:

#### Token Limits

- **Maximum active tokens per user**: 5 tokens
- When a user has 5 active tokens and creates a new one (login/register), the oldest token (by `last_used_at`) is automatically deleted
- This allows users to maintain sessions on multiple devices (e.g., 2 phones, 1 tablet, 1 web browser) while preventing unlimited token growth

**Example Scenario**:
```
User has 5 active tokens:
├─ Token 1: iPhone (last used 30 days ago)
├─ Token 2: iPad (last used 5 days ago)
├─ Token 3: Android (last used 2 days ago)
├─ Token 4: Web (last used 1 day ago)
└─ Token 5: Android Tablet (last used 3 hours ago)

User logs in from new device:
→ Token 1 (oldest by activity) is automatically deleted
→ New token is created
→ User now has 5 tokens again
```

#### Automatic Token Cleanup

**Daily Scheduled Task**:
- Runs every day at midnight
- Deletes tokens that expired more than 7 days ago
- Keeps database clean without manual intervention

**Manual Cleanup Command**:
```bash
# Preview tokens to be deleted (dry run)
php artisan tokens:cleanup --dry-run

# Delete expired tokens (default: 7+ days old)
php artisan tokens:cleanup

# Delete expired tokens older than 30 days
php artisan tokens:cleanup --days=30
```

#### Database Impact

With this implementation:
- **Before**: A user logging in daily for 2 years = 730 tokens
- **After**: Same user = Maximum 5 tokens at any time
- **Result**: ~99% reduction in token table growth

#### Best Practices for Mobile Apps

1. **Store tokens securely**: Use secure storage (Keychain/Keystore)
2. **Don't create new tokens unnecessarily**: Only login when token is invalid or expired
3. **Handle 401 errors gracefully**: When token is revoked/expired, prompt user to login
4. **Use meaningful token names**: Pass `os` parameter (ios/android) for better device identification
5. **Implement device tracking**: Send `device_identifier` and `device_fingerprint` for enhanced security

**Device Tracking Implementation**:

```javascript
// Generate device_identifier once and store it permanently
const getOrCreateDeviceIdentifier = async () => {
  let deviceId = await SecureStore.getItemAsync('device_identifier');
  if (!deviceId) {
    deviceId = uuid.v4(); // Generate UUID: "550e8400-e29b-41d4-a716-446655440000"
    await SecureStore.setItemAsync('device_identifier', deviceId);
  }
  return deviceId;
};

// Generate device_fingerprint (optional but recommended)
const generateDeviceFingerprint = async () => {
  const deviceInfo = {
    brand: Device.brand,           // "Apple", "Samsung"
    modelName: Device.modelName,   // "iPhone 14 Pro"
    osVersion: Device.osVersion,   // "17.2"
    platform: Device.osName,       // "iOS", "Android"
  };

  const fingerprintString = JSON.stringify(deviceInfo);
  const hash = await Crypto.digestStringAsync(
    Crypto.CryptoDigestAlgorithm.SHA256,
    fingerprintString
  );

  return hash; // SHA256 hash
};

// Login with device tracking
const login = async (email, password) => {
  const deviceIdentifier = await getOrCreateDeviceIdentifier();
  const deviceFingerprint = await generateDeviceFingerprint();

  const response = await fetch('https://api.example.com/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email,
      password,
      os: Platform.OS,              // "ios" or "android"
      device_identifier: deviceIdentifier,
      device_fingerprint: deviceFingerprint
    })
  });

  return response.json();
};
```

**Benefits of Device Tracking**:
- Detects logins from new/unknown devices
- Tracks login history per device
- Calculates trust scores based on device behavior
- Enables fraud detection and suspicious activity alerts
- Allows customers to manage and revoke devices

### ⚠️ CRITICAL: Required Implementation for Mobile Team

**The mobile development team MUST implement the following**:

1. **Generate a unique UUID** the first time the app is installed
2. **Store the UUID in secure storage** (never regenerate it)
3. **Send it in ALL login/register requests** as `device_identifier`
4. **(Optional but Recommended)** Generate SHA256 hash of device characteristics as `device_fingerprint`

**Why This Is Critical**:
- Without `device_identifier`, the API cannot track devices or detect suspicious logins
- Without device tracking, security features (trust scoring, fraud detection) will not work
- Without proper implementation, users will appear to login from "new devices" every time

**Implementation Checklist**:
- [ ] Generate UUID on first app launch
- [ ] Store UUID in secure storage (Keychain/Keystore)
- [ ] Include `device_identifier` in all auth requests
- [ ] Include `os` parameter (ios/android/web)
- [ ] (Optional) Include `device_fingerprint` for enhanced security

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
     │ { id_token, device_identifier, device_fingerprint }
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
- `fcm_token` (nullable), `device_identifier` (UUID, unique)
- `device_fingerprint` (SHA256 hash, nullable)
- `device_type` (ios/android/web), `device_name`, `device_model`
- `app_version`, `os_version`
- `is_active`, `login_count`, `trust_score` (0-100)
- `last_used_at`, `created_at`, `updated_at`, `deleted_at`

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
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000",
  "device_fingerprint": "a1b2c3d4e5f6...",
  "device_name": "iPhone 15 Pro",
  "device_type": "ios",
  "device_model": "iPhone15,2",
  "app_version": "1.0.0",
  "os_version": "17.0",
  "last_used_at": "2025-11-07T14:25:00Z",
  "login_count": 47,
  "trust_score": 92,
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
# 1. Register with device tracking
POST /api/v1/auth/register
{
  "name": "María García",
  "email": "maria@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "os": "ios",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000",  // REQUIRED
  "device_fingerprint": "a1b2c3d4e5f6..."                       // Recommended
}

# Response includes token
{
  "access_token": "1|xyz789...",
  "token_type": "Bearer",
  "customer": {
    "id": 1,
    "name": "María García",
    "email": "maria@example.com",
    ...
  }
}

# 2. Use token for subsequent requests
GET /api/v1/profile
Authorization: Bearer 1|xyz789...
```

### 2. Login with Google

```javascript
// Mobile app (React Native)
import { GoogleSignin } from '@react-native-google-signin/google-signin';
import * as SecureStore from 'expo-secure-store';
import * as Crypto from 'expo-crypto';
import * as Device from 'expo-device';
import { v4 as uuidv4 } from 'uuid';

// 1. Get or create device identifier
const getDeviceIdentifier = async () => {
  let deviceId = await SecureStore.getItemAsync('device_identifier');
  if (!deviceId) {
    deviceId = uuidv4(); // "550e8400-e29b-41d4-a716-446655440000"
    await SecureStore.setItemAsync('device_identifier', deviceId);
  }
  return deviceId;
};

// 2. Generate device fingerprint
const generateFingerprint = async () => {
  const info = JSON.stringify({
    brand: Device.brand,
    modelName: Device.modelName,
    osVersion: Device.osVersion,
    platform: Device.osName,
  });
  return await Crypto.digestStringAsync(Crypto.CryptoDigestAlgorithm.SHA256, info);
};

// 3. Get Google ID token
const { idToken } = await GoogleSignin.signIn();

// 4. Send to API with device tracking
const response = await fetch('https://api.subway.gt/api/v1/auth/oauth/google', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    id_token: idToken,
    os: Platform.OS, // "ios" or "android"
    device_identifier: await getDeviceIdentifier(),
    device_fingerprint: await generateFingerprint()
  })
});

// 5. Store token
const { access_token } = await response.json();
await SecureStore.setItemAsync('token', access_token);
```

### 3. Register Device for Push Notifications

```javascript
// Mobile app - Get FCM token and register device
import messaging from '@react-native-firebase/messaging';
import * as Device from 'expo-device';

const fcmToken = await messaging().getToken();

// Register or update device with API
await fetch('https://api.subway.gt/api/v1/devices/register', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    fcm_token: fcmToken,
    device_identifier: await getDeviceIdentifier(),  // Same UUID from login
    device_fingerprint: await generateFingerprint(), // Same fingerprint
    device_name: Device.deviceName || 'Unknown Device',
    device_type: Platform.OS, // "ios" or "android"
    device_model: Device.modelName,
    app_version: '1.0.0',
    os_version: Device.osVersion
  })
});
```

**Important Notes**:
- Use the **same** `device_identifier` from login/register
- This enriches the device record created during authentication
- FCM token is optional - device tracking works without it

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

### v1.0.1 (November 2025)

**Token Management & Scalability**:
- ✅ Automatic token limit (5 per user) to prevent database bloat
- ✅ Daily scheduled cleanup of expired tokens
- ✅ Manual cleanup command (`tokens:cleanup`)
- ✅ Token expiration set to 1 year (365 days)
- ✅ Oldest tokens auto-deleted when limit reached
- ✅ ~99% reduction in token table growth

### v1.0.2 (November 10, 2025)

**Device Tracking & Trust Scoring** (Sprint 1 & 2):
- ✅ Device tracking with unique identifiers (`device_identifier`)
- ✅ Device fingerprinting for security (`device_fingerprint`)
- ✅ Login count tracking per device
- ✅ Trust score calculation (0-100) based on device behavior
- ✅ Auto-sync devices with authentication tokens
- ✅ Observer pattern for token-device lifecycle
- ✅ Scheduled cleanup of inactive devices
- ✅ Commands: `devices:cleanup`, `devices:recalculate-trust-scores`
- ✅ Updated API documentation with device tracking guide
- ✅ Swagger/OpenAPI documentation updated
- ✅ Frontend implementation guide with code examples

**Breaking Changes**:
- None (all new fields are optional/nullable)

**New Request Parameters** (Optional but Recommended):
- `device_identifier`: UUID for device tracking
- `device_fingerprint`: SHA256 hash for security
- `os`: Operating system (ios/android/web)

**Endpoints Updated**:
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/oauth/google`
- `POST /api/v1/auth/oauth/google/register`

**Database Changes**:
- Added `device_identifier`, `device_fingerprint`, `login_count`, `trust_score` to `customer_devices`
- Made `fcm_token` nullable

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