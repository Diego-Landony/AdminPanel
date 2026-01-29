Apple Wallet & Google Wallet Integration - Backend Implementation Plan
Veredicto: El plan de Flutter tiene buenas bases pero requiere correcciones importantes
Correcciones al plan de Flutter
Aspecto	Flutter propone	Corrección
Librería Apple	chiiya/passes	pkpass/pkpass — chiiya solo soporta hasta Laravel 11 (illuminate/contracts ^9.0|^10.0|^11.0). pkpass/pkpass tiene 967 stars, release Jan 2026, battle-tested
Google Pass Type	Generic Pass	LoyaltyClass/LoyaltyObject — Google recomienda explícitamente usar tipos especializados para loyalty cards
Google JWT approach	Embedding full object in JWT	Skinny JWT — JWT tiene límite 1800 chars. Pre-crear objeto via REST API, solo referenciar ID en JWT
Storage .pkpass	Subir a storage temporal + URL temporal	Generar on-the-fly via signed URL — Sin archivos en disco, sin cleanup
Certificados path	/var/secrets/apple_wallet/	storage/app/wallet/ — Más idiomático en Laravel
Paquetes	chiiya/passes, google/auth, firebase/php-jwt	pkpass/pkpass + google/apiclient (2 paquetes, firebase/php-jwt ya instalado)
Lo que Flutter tiene correcto
Endpoints y rutas propuestas
Body vacío, customer_id del token Sanctum
Response format { data: { url } } y { data: { save_url } }
Barcode CODE128 con subway_card
Apple pass type storeCard
Rate limiting 1/5min
Assets son bloqueante (usaremos placeholders)
Scope: Ambas plataformas, código primero, placeholders para imágenes
Archivos a crear
app/Services/Wallet/AppleWalletService.php
app/Services/Wallet/GoogleWalletService.php
app/Http/Controllers/Api/V1/WalletController.php
app/Console/Commands/CreateGoogleWalletClass.php
tests/Feature/Api/V1/WalletControllerTest.php
storage/app/wallet/apple/images/ — Placeholders PNG generados
storage/app/wallet/apple/certificates/.gitkeep
storage/app/wallet/google/.gitkeep
Archivos a modificar
config/services.php — Agregar apple_wallet y google_wallet
routes/api.php — 3 rutas nuevas
app/Providers/AppServiceProvider.php — Rate limiter wallet
.gitignore — Excluir certificados/credenciales
Pasos de implementación
1. Instalar paquetes

composer require pkpass/pkpass google/apiclient
2. Config config/services.php
Agregar apple_wallet y google_wallet keys con env vars para:

Certificate paths, password, pass type ID, team ID, org name, images path
Service account path, issuer ID, class ID, program name
3. Crear AppleWalletService
generatePass(Customer): string — retorna binary .pkpass
Usa PKPass\PKPass library
Pass type storeCard con headerFields (puntos), primaryFields (nombre), secondaryFields (tier), auxiliaryFields (tarjeta)
Barcode PKBarcodeFormatCode128 con subway_card
Colores dinámicos del customerType.color
Carga imágenes de storage/app/wallet/apple/images/
4. Crear GoogleWalletService
generateSaveUrl(Customer): string — retorna Google save URL
createOrUpdateClass(): LoyaltyClass — setup one-time
Usa Google\Service\Walletobjects + Firebase\JWT\JWT
Skinny JWT: pre-crea LoyaltyObject via REST, JWT solo referencia ID
Object ID: {issuerId}.subway_loyalty_{customer_id}
LoyaltyPoints con subway_card como barcode CODE_128
5. Crear WalletController
applePass(Request): JsonResponse — genera signed URL → { data: { url } }
applePassDownload(Request, int, AppleWalletService): Response — stream .pkpass con Content-Type: application/vnd.apple.pkpass
googlePass(Request, GoogleWalletService): JsonResponse — genera save URL → { data: { save_url } }
Validación: customer debe tener subway_card (422 si no)
Swagger OA annotations
6. Rutas en routes/api.php
Protegidas (auth:sanctum + throttle:wallet):

POST /api/v1/wallet/apple/pass
POST /api/v1/wallet/google/pass
Pública con signed middleware:

GET /api/v1/wallet/apple/download/{customer} (signed, throttle:60,1)
7. Rate limiter en AppServiceProvider
wallet: 1 request per 5 minutes by user ID

8. Comando Artisan wallet:create-google-class
One-time para crear LoyaltyClass en Google API

9. Crear placeholders de imágenes
Generar PNGs simples con colores Subway (#008244) en los tamaños requeridos:

icon@2x.png (58x58), icon@3x.png (87x87)
logo@2x.png (320x100), logo@3x.png (480x150)
strip@2x.png (640x246), strip@3x.png (960x369)
10. Tests (Pest)
Con mocks de los services (no requieren certificados reales):

Apple POST: auth → URL, sin subway_card → 422, sin auth → 401
Apple Download: signed URL → pkpass con Content-Type, URL inválida → 403, expirada → 403
Google POST: auth → save_url, sin subway_card → 422, error service → 500, sin auth → 401
11. Formateo y docs
vendor/bin/pint --dirty
Swagger annotations ya incluidas en controller
Verificación
php artisan test --filter=WalletController — Tests pasan
php artisan test — Suite completa sigue pasando
vendor/bin/pint --dirty — Sin errores de formato