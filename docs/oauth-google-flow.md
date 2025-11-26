# Flujo de Autenticación OAuth2 con Google

## Visión General

Sistema de autenticación OAuth2 que permite a los usuarios iniciar sesión o registrarse usando su cuenta de Google. Soporta tanto plataformas web como móviles mediante un flujo unificado que utiliza el parámetro `state` de OAuth 2.0 para mantener contexto a través del proceso de redirección.

### Responsabilidades del Sistema

**Backend centraliza toda la lógica OAuth**: El backend maneja completamente el flujo de autenticación con Google. No se requiere que el cliente implemente SDKs nativos de Google ni gestione tokens de Google directamente.

**Flujo basado en Web OAuth**: Se utiliza exclusivamente el flujo web de Google OAuth, no los SDKs nativos de Google. Esto significa que tanto aplicaciones web como móviles usan el mismo flujo de redirección basado en navegador.

**Aplicaciones móviles usan navegador embebido**: Las aplicaciones móviles (React Native, Flutter, etc.) abren un navegador embebido (WebView, Chrome Custom Tabs, Safari View Controller) para completar el flujo OAuth. No se utiliza el SDK nativo de Google Sign-In.

**Cliente responsable del Device ID**: El cliente (web o móvil) es 100% responsable de generar y persistir el identificador único del dispositivo (UUID). El backend solo lo acepta, valida y rastrea en la base de datos.

## Arquitectura de Base de Datos

### Tabla Customers

Almacena información de usuarios del sistema con soporte para múltiples métodos de autenticación:

- Identificación única de Google para vincular cuentas
- Avatar del usuario obtenido de Google
- Proveedor OAuth que indica el método de registro (local, google)
- Email único que sirve como identificador principal
- Contraseña hasheada (aleatoria para usuarios OAuth)
- Verificación de email automática para usuarios OAuth
- Timestamps de última actividad y último login

### Tabla Personal Access Tokens (Sanctum)

Gestiona los tokens de autenticación de la API:

- Relación polimórfica con el modelo Customer
- Token hasheado para seguridad
- Nombre descriptivo del token (identificador de dispositivo)
- Fecha de expiración para tokens temporales
- Habilidades/permisos asociados al token

### Tabla Customer Devices

Rastrea dispositivos asociados a cada usuario:

- Vinculación con el customer y su token de Sanctum
- Identificador único del dispositivo
- Token FCM para notificaciones push
- Timestamp de último uso
- Estado activo/inactivo del dispositivo
- Contador de inicios de sesión

## Flujo Completo de Autenticación

### 1. Inicio del Flujo (Redirect)

El cliente inicia el proceso solicitando una redirección a Google OAuth. Debe especificar:

**Acción**: Define si el usuario quiere iniciar sesión en una cuenta existente o crear una nueva cuenta.

**Plataforma**: Indica si la solicitud viene desde una aplicación web o móvil, lo cual determina cómo se entregará el token al final del proceso.

**Device ID**: Identificador único del dispositivo (obligatorio para móvil, opcional para web) usado para rastrear sesiones. Este UUID debe ser generado por el cliente en la primera ejecución, persistido localmente (localStorage, AsyncStorage, SharedPreferences), y enviado en cada autenticación para identificar el mismo dispositivo a través del tiempo.

**Redirect URL**: URL personalizada para desarrollo local donde redirigir después de la autenticación.

El sistema genera un parámetro `state` codificado que contiene toda esta información junto con:
- Un nonce aleatorio para protección CSRF
- Un timestamp para validar expiración (10 minutos)

Este `state` se envía a Google junto con la redirección OAuth, y Google lo retornará sin modificar en el callback.

### 2. Autorización en Google

El usuario es redirigido a la página de consentimiento de Google donde:
- Selecciona la cuenta de Google a usar
- Revisa los permisos solicitados
- Autoriza o rechaza el acceso

Google está configurado para siempre mostrar el selector de cuenta, permitiendo al usuario elegir diferentes cuentas si lo desea.

**En aplicaciones móviles**: La redirección abre un navegador embebido (in-app browser) que muestra la página de Google. El usuario completa el flujo OAuth dentro de este navegador embebido, no sale de la aplicación. Una vez autorizado, Google redirige de vuelta a la URL de callback del backend, que procesa la autenticación y finalmente redirige a un deep link que la aplicación móvil captura para obtener el token.

### 3. Callback de Google

Después de la autorización, Google redirige al callback del sistema con:
- Un código de autorización temporal
- El parámetro `state` original sin modificar

El sistema:
- Decodifica y valida el parámetro `state`
- Verifica que el nonce existe (protección CSRF)
- Valida que no hayan pasado más de 10 minutos (protección contra replay)
- Intercambia el código de autorización por los datos del usuario de Google

Los datos obtenidos de Google incluyen:
- ID único del usuario en Google
- Email verificado
- Nombre completo
- URL del avatar

### 4. Procesamiento de Autenticación/Registro

Según la acción especificada en el `state`:

#### Modo Login

Busca un usuario existente en el sistema por:

**Primera búsqueda por Google ID**: Si encuentra un usuario con ese Google ID vinculado, lo autentica directamente actualizando su último login y avatar.

**Segunda búsqueda por Email**: Si no encuentra por Google ID pero el email existe en el sistema:
- Valida que no haya conflicto de proveedores (que no esté registrado con otro método OAuth)
- Vincula automáticamente el Google ID a esa cuenta existente
- Actualiza la cuenta como autenticada vía Google
- Marca como login exitoso

**Email no encontrado**: Si el email no existe en el sistema, rechaza la operación porque en modo login no se crean cuentas nuevas.

#### Modo Register

Busca si el email ya existe:

**Email existente con mismo Google ID**: El usuario ya está registrado, simplemente hace login.

**Email existente sin Google ID o con diferente proveedor**: Rechaza la operación porque el email ya pertenece a otra cuenta.

**Email nuevo**: Crea una nueva cuenta de usuario con:
- Nombre y apellido separados del nombre completo de Google
- Email de Google
- Google ID para futuras autenticaciones
- Avatar de Google
- Proveedor marcado como Google
- Email automáticamente verificado (Google lo verificó)
- Contraseña aleatoria hasheada (el usuario nunca la usará)

Dispara el evento de usuario registrado para cualquier procesamiento adicional (emails de bienvenida, etc).

### 5. Generación de Token

Una vez autenticado o registrado el usuario:

**Limpieza de tokens viejos**: El sistema aplica un límite de 5 tokens activos por usuario, eliminando los más antiguos si se excede.

**Creación del token**: Genera un nuevo token de acceso Sanctum con un nombre descriptivo basado en el device ID o un identificador único.

**Sincronización de dispositivo**: Si se proporcionó un device ID:
- Crea o actualiza el registro del dispositivo
- Vincula el dispositivo con el token de Sanctum
- Registra información del dispositivo
- Actualiza timestamp de último uso

El token plano solo está disponible en este momento y nunca se puede recuperar después.

### 6. Entrega del Token

El sistema bifurca según la plataforma especificada:

#### Plataforma Móvil

Redirige a la aplicación móvil mediante un deep link con el esquema registrado. El deep link incluye como parámetros:
- Token de autenticación
- ID del usuario
- Flag indicando si es usuario nuevo
- Mensaje de estado

La aplicación móvil captura este deep link y procesa los datos.

#### Plataforma Web

Si hay una URL de redirección personalizada (para desarrollo local):
- Redirige a esa URL con los datos como query parameters
- El frontend los captura y procesa según su implementación

Si no hay URL personalizada:
- Redirige a una ruta interna de éxito OAuth
- Muestra página HTML con los datos del token
- JavaScript procesa los datos automáticamente

### 7. Procesamiento en Cliente Web

La página de éxito OAuth:

**Almacenamiento local**: Guarda el token y datos del usuario en localStorage del navegador para persistencia entre sesiones.

**Eventos personalizados**: Dispara eventos JavaScript que pueden ser capturados por frameworks frontend como Livewire para sincronizar estado.

**Redirección final**: Después de guardar los datos, redirige al usuario a la página principal de la aplicación.

**Manejo de errores**: Si no hay token, muestra mensaje de error y no permite continuar.

## Diferencias entre Login y Register

### Login (Para usuarios existentes)

**Propósito**: Autenticar usuarios que ya tienen cuenta en el sistema.

**Email no existe**: Falla con error indicando que deben registrarse primero.

**Email existe sin Google vinculado**: Vincula automáticamente Google a su cuenta existente para futuros logins.

**Email existe con Google vinculado**: Autentica directamente.

**Conflicto de proveedores**: Si la cuenta usa otro proveedor OAuth diferente, falla con error.

### Register (Para usuarios nuevos)

**Propósito**: Crear nuevas cuentas usando credenciales de Google.

**Email no existe**: Crea cuenta nueva con datos de Google.

**Email existe**: Falla con error indicando que el email ya está registrado, deben usar login.

**Cuenta ya creada previamente con Google**: Hace login en lugar de fallar.

**Conflicto de proveedores**: Falla si el email está asociado a otro método de autenticación.

## Seguridad

### Protección CSRF

Utiliza el parámetro `state` de OAuth 2.0 con un nonce aleatorio de 32 caracteres que se valida en el callback. Esto previene ataques de falsificación de peticiones entre sitios.

### Expiración de State

El parámetro `state` incluye un timestamp que se valida en el callback. Si han pasado más de 10 minutos, se rechaza la autenticación para prevenir ataques de replay.

### Operación Stateless

No depende de sesiones PHP del servidor. Todo el contexto necesario viaja en el parámetro `state`, permitiendo operación distribuida y sin estado.

### Límite de Tokens

Cada usuario puede tener máximo 5 tokens activos simultáneamente. Los tokens más antiguos se eliminan automáticamente al crear nuevos, previniendo acumulación infinita.

### Verificación Automática de Email

Los usuarios que se registran vía OAuth tienen su email automáticamente verificado, confiando en que Google ya validó la propiedad del email.

### Contraseña Aleatoria

Usuarios OAuth reciben una contraseña aleatoria hasheada que nunca conocerán ni usarán, cumpliendo requisitos de base de datos pero sin crear riesgo de seguridad.

### Rate Limiting

Los endpoints OAuth tienen throttling específico separado de otros endpoints, controlando la cantidad de intentos de autenticación.

## Configuración del Sistema

### Variables de Entorno

El sistema requiere credenciales de la consola de Google Cloud:
- Client ID de la aplicación OAuth
- Client Secret de la aplicación OAuth
- URL de callback registrada en Google

### Esquema Deep Link Móvil

Para plataforma móvil se configura un esquema de URL personalizado que la aplicación puede interceptar.

### Rutas y Middleware

Los endpoints OAuth están agrupados bajo un prefijo específico y usan middleware web para habilitar cookies y sesiones necesarias para el flujo de redirección, aunque el flujo es stateless mediante el parámetro `state`.

## Rastreo de Dispositivos

### Propósito

Permite al sistema identificar desde qué dispositivos específicos se conecta cada usuario, útil para:
- Análisis de uso por dispositivo
- Gestión de sesiones activas
- Envío de notificaciones push
- Seguridad (detección de dispositivos desconocidos)

### Responsabilidad del Cliente en Device ID

**Generación inicial**: La primera vez que el usuario instala la aplicación o accede desde un nuevo navegador, el cliente debe generar un UUID v4 aleatorio.

**Persistencia local**: El cliente debe almacenar este UUID de forma persistente:
- Web: localStorage o cookie de larga duración
- React Native: AsyncStorage
- iOS nativo: UserDefaults o Keychain
- Android nativo: SharedPreferences

**Envío en cada autenticación**: Cada vez que el usuario hace login, el cliente debe recuperar el UUID almacenado y enviarlo en el parámetro `device_id` del redirect OAuth.

**No regenerar**: El UUID debe mantenerse constante a menos que el usuario desinstale la app o borre completamente el caché/datos. Regenerarlo haría que el backend lo trate como un dispositivo nuevo.

**Backend solo acepta y rastrea**: El backend no genera UUIDs para dispositivos. Solo recibe el UUID del cliente, lo valida, y lo registra en la base de datos asociado al usuario y su token.

### Funcionamiento del Backend

Cuando se proporciona un device ID durante OAuth:
- Se busca si ya existe un dispositivo con ese ID en la tabla customer_devices
- Si existe, se actualiza con el nuevo token y timestamp de last_used_at
- Si no existe, se crea nuevo registro de dispositivo
- Se vincula el dispositivo con el token de Sanctum correspondiente
- Se incrementa contador de logins para ese dispositivo

El constraint unique en device_identifier garantiza que cada UUID solo existe una vez en el sistema, permitiendo identificar el mismo dispositivo a través de múltiples sesiones.

### Tokens FCM

Los dispositivos pueden actualizar su token FCM (Firebase Cloud Messaging) posteriormente para recibir notificaciones push, aunque esto es independiente del flujo OAuth inicial.

## Manejo de Errores

### Errores de Validación

Durante el redirect si faltan parámetros requeridos o tienen valores inválidos, se retorna error 422 con detalles específicos.

### Errores en Callback

Si ocurre error durante el procesamiento del callback:
- Se extrae la plataforma del state si es posible
- Para móvil: redirige al deep link con parámetros de error
- Para web: redirige a página de login con mensaje de error en sesión

### Errores de State

Si el state es inválido, falta, o expiró, se trata como error de autenticación y se maneja según la plataforma.

### Errores de Google

Si Google retorna error o el intercambio de código falla, se captura la excepción y se maneja como error de autenticación.

## Casos de Uso Comunes

### Usuario Nuevo desde Móvil

Usuario descarga app, toca "Registrarse con Google", selecciona cuenta, es redirigido a app con token, app guarda token y muestra onboarding.

### Usuario Existente desde Web

Usuario visita sitio web, toca "Iniciar sesión con Google", autoriza, es redirigido a página de éxito que guarda token en localStorage y redirige a home.

### Usuario que Registró Local y Luego Usa Google

Usuario creó cuenta con email/password, luego intenta login con Google usando mismo email, sistema vincula Google automáticamente, próximos logins puede usar cualquier método.

### Desarrollo Local con Frontend Separado

Frontend en localhost:3000, backend en localhost:8000, frontend pasa redirect_url en OAuth, después de autenticación Google redirige de vuelta al frontend con token en URL.

### Usuario con Múltiples Dispositivos

Usuario usa app en teléfono y tablet, cada dispositivo tiene su device_id único, sistema rastrea ambos dispositivos por separado, cada uno con su token.

## Arquitectura de Autenticación

### Flujo Completamente Manejado por Backend

A diferencia de implementaciones que requieren SDKs nativos de Google en cada plataforma, este sistema centraliza toda la lógica OAuth en el backend:

**Sin SDKs nativos requeridos**: Las aplicaciones cliente (web o móvil) no necesitan implementar Google Sign-In SDK, Firebase Auth, ni ninguna librería específica de Google más allá de un navegador embebido.

**Backend como proxy OAuth**: El backend actúa como intermediario entre el cliente y Google, manejando todo el intercambio de códigos de autorización, validación de tokens, y gestión de cuentas.

**Clientes solo manejan navegación**: Los clientes únicamente necesitan:
- Abrir una URL de redirect en un navegador (embebido en móvil)
- Capturar el resultado (deep link en móvil, redirect en web)
- Almacenar el token de Sanctum recibido
- Generar y persistir el device_id

**Ventajas de este enfoque**:
- Lógica de autenticación centralizada y fácil de mantener
- Mismo flujo para todas las plataformas (web, iOS, Android)
- No depende de actualizaciones de SDKs nativos
- Backend controla completamente reglas de negocio (login vs register)
- Fácil agregar otros proveedores OAuth siguiendo el mismo patrón

**Consideraciones móviles**:
- React Native: Usar Expo AuthSession o react-native-inappbrowser-reborn
- Flutter: Usar flutter_inappwebview o url_launcher
- Nativo iOS: SFSafariViewController o ASWebAuthenticationSession
- Nativo Android: Chrome Custom Tabs

Todas estas soluciones abren el flujo OAuth en un navegador embebido que redirige de vuelta a la app mediante deep links.
