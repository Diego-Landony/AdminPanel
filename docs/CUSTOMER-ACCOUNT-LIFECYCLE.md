# Customer Account Lifecycle

## Eliminación y Reactivación de Cuentas

### Flujo de eliminación

```
Usuario elimina cuenta (DELETE /api/v1/profile)
              ↓
      Soft Delete (30 días)
              ↓
┌─────────────────────────────────────────┐
│ Durante los 30 días:                    │
│ - Puede reactivar su cuenta             │
│ - Conserva sus puntos acumulados        │
│ - Si intenta registrarse/login,         │
│   se le ofrece reactivar                │
└─────────────────────────────────────────┘
              ↓
    Después de 30 días
              ↓
      Hard Delete (automático)
      - Datos eliminados permanentemente
      - Puede crear cuenta nueva con mismo email
```

---

## Comandos de Artisan

### Purgar cuentas eliminadas

```bash
# Ver qué se eliminaría (dry-run)
php artisan customers:purge-deleted --dry-run

# Ejecutar eliminación (default 30 días)
php artisan customers:purge-deleted

# Con días personalizados
php artisan customers:purge-deleted --days=60

# Sin confirmación (para cron/scripts)
php artisan customers:purge-deleted --no-interaction
```

### Schedule automático

El comando se ejecuta **diariamente a medianoche** (configurado en `routes/console.php`):

```php
Schedule::command('customers:purge-deleted --days=30')->daily();
```

---

## Endpoints del API

### 1. Reactivar cuenta eliminada

```
POST /api/v1/auth/reactivate
```

**Request (cuenta local):**
```json
{
  "email": "usuario@example.com",
  "password": "MiContraseña123",
  "device_identifier": "uuid-del-dispositivo"
}
```

**Request (cuenta OAuth - sin password):**
```json
{
  "email": "usuario@example.com",
  "device_identifier": "uuid-del-dispositivo"
}
```

**Response 200:**
```json
{
  "message": "Cuenta reactivada exitosamente. Bienvenido de nuevo.",
  "data": {
    "access_token": "1|abc123...",
    "token_type": "Bearer",
    "customer": { ... },
    "points": 150,
    "deleted_at": "2025-11-20T10:30:00Z"
  }
}
```

**Errores posibles:**
- `422` - `account_not_found_deleted`: No existe cuenta eliminada con este email
- `422` - `reactivation_period_expired`: Han pasado más de 30 días
- `422` - `incorrect_password`: Contraseña incorrecta (solo cuentas locales)

---

### 2. Registro con cuenta eliminada detectada

```
POST /api/v1/auth/register
```

**Response 409 (si existe cuenta eliminada < 30 días):**
```json
{
  "message": "Encontramos una cuenta eliminada con este correo.",
  "code": "account_deleted_recoverable",
  "data": {
    "deleted_at": "2025-11-20T10:30:00Z",
    "days_until_permanent_deletion": 15,
    "points": 150,
    "can_reactivate": true
  }
}
```

---

### 3. OAuth con cuenta eliminada

Si el usuario intenta login con Google/Apple y su cuenta fue eliminada:

**Móvil (deep link):**
```
subwayapp://oauth/callback?error=account_deleted_recoverable&points=150&days_left=15&email=user@example.com&message=Tu cuenta fue eliminada...
```

**Web:**
```
/oauth/success?error=account_deleted_recoverable&points=150&days_left=15&email=user@example.com
```

---

## Archivos relacionados

| Archivo | Descripción |
|---------|-------------|
| `app/Console/Commands/PurgeDeletedCustomersCommand.php` | Comando de limpieza |
| `app/Exceptions/AccountDeletedException.php` | Excepción para cuentas recuperables |
| `app/Http/Requests/Api/V1/Auth/ReactivateAccountRequest.php` | Validación de reactivación |
| `app/Http/Controllers/Api/V1/Auth/AuthController.php` | Método `reactivateAccount()` |
| `app/Services/SocialAuthService.php` | Detección en OAuth |
| `routes/console.php` | Schedule del comando |
