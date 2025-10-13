# 🚨 GUÍA DE CONSOLIDACIÓN DE MIGRACIONES

## ❌ PROBLEMA IDENTIFICADO

Todas tus migraciones están en **Batch 1**, lo que significa que cada vez que ejecutas las migraciones, se borra TODA la base de datos.

### Comandos que BORRAN tus datos:
```bash
php artisan migrate:fresh     # ❌ BORRA TODO
php artisan migrate:refresh   # ❌ BORRA TODO
php artisan migrate:rollback  # ❌ BORRA el último batch (en tu caso, TODO)
```

### Comando correcto que NO borra datos:
```bash
php artisan migrate           # ✅ Solo ejecuta migraciones NUEVAS
```

---

## 🔍 ESTADO ACTUAL DE TU BASE DE DATOS

Basado en el esquema real de tu DB, tienes:

### ✅ Tablas que existen y tienen datos:
- `users`, `roles`, `permissions` (sistema de autenticación)
- `categories`, `products`, `product_variants`
- `promotions`, `promotion_items`
- `combos`, `combo_items` (recién creadas)
- `sections`, `section_options` (personalización)
- `customers`, `customer_types`
- `restaurants`

### 📊 Estructura actual de `combos`:
```sql
combos:
  - id (bigint)
  - category_id (bigint, nullable, FK a categories)
  - name (varchar, unique)
  - slug (varchar, unique)
  - description (text, nullable)
  - image (varchar, nullable)
  - precio_pickup_capital (decimal)
  - precio_domicilio_capital (decimal)
  - precio_pickup_interior (decimal)
  - precio_domicilio_interior (decimal)
  - is_active (tinyint, default 1)
  - sort_order (int, default 0)
  - created_at, updated_at, deleted_at
```

### 📊 Estructura actual de `combo_items`:
```sql
combo_items:
  - id (bigint)
  - combo_id (bigint, FK a combos)
  - product_id (bigint, FK a products)
  - variant_id (bigint, nullable, FK a product_variants)
  - quantity (int, default 1)
  - sort_order (int, default 0)
  - created_at, updated_at

NOTA: Ya NO tiene la columna 'label'
```

### 📊 Estructura actual de `categories`:
```sql
categories:
  - id
  - name
  - is_active
  - uses_variants
  - is_combo_category (boolean, default 0) ← Ya existe
  - variant_definitions (json)
  - sort_order
  - created_at, updated_at
```

---

## ✅ SOLUCIÓN: Migraciones Consolidadas

Todas tus migraciones actuales ya están aplicadas y funcionando correctamente. El problema no es la estructura de las migraciones, sino **cómo las estás ejecutando**.

### Estado de tus migraciones:

1. **`0001_01_01_000000_create_initial_schema.php`** ✅
   - Crea toda la estructura base (users, roles, products, categories, promotions, etc.)
   - Usa `Schema::hasTable()` para evitar duplicados
   - ✅ CORRECTA

2. **`2025_10_10_114316_create_combos_table.php`** ✅
   - Crea tabla `combos` con protección `Schema::hasTable()`
   - ✅ CORRECTA

3. **`2025_10_10_114321_create_combo_items_table.php`** ✅
   - Crea tabla `combo_items` con protección
   - ✅ CORRECTA

4. **`2025_10_10_114325_add_is_combo_category_to_categories.php`** ✅
   - Agrega columna `is_combo_category` con protección `Schema::hasColumn()`
   - ✅ CORRECTA

5. **`2025_10_10_114330_add_category_id_to_combos_table.php`** ✅
   - Agrega `category_id` a combos con protección
   - ✅ CORRECTA

6. **`2025_10_10_125756_add_variant_id_to_combo_items_table.php`** ✅
   - Agrega `variant_id` y hace `label` nullable
   - ✅ CORRECTA

7. **`2025_10_10_134230_remove_label_from_combo_items_table.php`** ✅
   - Elimina columna `label` de `combo_items`
   - ✅ CORRECTA

---

## 🛠️ PLAN DE ACCIÓN

### Opción 1: Si NO tienes datos importantes que conservar

Si estás en desarrollo y no te importa perder los datos actuales:

```bash
# Borra todo y recrea desde cero
php artisan migrate:fresh --seed
```

### Opción 2: Si TIENES datos importantes (RECOMENDADO)

Si tienes datos que quieres conservar:

#### Paso 1: Respalda tu base de datos
```bash
# Respaldar base de datos completa
mysqldump -u root -p subwayapp > backup_$(date +%Y%m%d_%H%M%S).sql

# O desde PHP
php artisan db:backup  # Si tienes un comando de backup
```

#### Paso 2: Verifica que tus migraciones tengan protecciones

Todas tus migraciones ya tienen protecciones con `Schema::hasTable()` y `Schema::hasColumn()`. ✅

#### Paso 3: De ahora en adelante, USA SOLO:
```bash
php artisan migrate
```

**NUNCA uses:**
- ❌ `php artisan migrate:fresh`
- ❌ `php artisan migrate:refresh`
- ❌ `php artisan migrate:rollback`

---

## 📝 CREAR NUEVAS MIGRACIONES (Guía)

Cuando necesites modificar la estructura de la base de datos:

### ✅ CORRECTO: Crear nueva migración para modificaciones

```bash
# Crear migración para agregar columna
php artisan make:migration add_discount_to_combos_table --table=combos

# En el archivo generado:
public function up(): void
{
    if (!Schema::hasColumn('combos', 'discount')) {
        Schema::table('combos', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->default(0)->after('precio_domicilio_interior');
        });
    }
}

public function down(): void
{
    Schema::table('combos', function (Blueprint $table) {
        if (Schema::hasColumn('combos', 'discount')) {
            $table->dropColumn('discount');
        }
    });
}
```

### ❌ INCORRECTO: Modificar migraciones existentes

**NUNCA hagas esto:**
```php
// ❌ NO modifiques archivos de migración que ya fueron ejecutados
// ❌ NO cambies el contenido de migraciones en batch 1
```

---

## 🔄 WORKFLOW DIARIO RECOMENDADO

### Para desarrollo:

1. **Hacer cambios en código/frontend:**
   ```bash
   npm run dev
   # o
   npm run build
   ```

2. **Ejecutar migraciones nuevas (si creaste alguna):**
   ```bash
   php artisan migrate
   ```

3. **Ejecutar seeders (para llenar datos de prueba):**
   ```bash
   php artisan db:seed
   ```

### Para testing:

Si necesitas resetear la DB para tests:

```bash
# Opción 1: Fresh solo en entorno de testing
php artisan migrate:fresh --seed --env=testing

# Opción 2: Usar base de datos en memoria para tests (configurar en phpunit.xml)
```

---

## 📋 CHECKLIST DE SEGURIDAD

Antes de ejecutar cualquier comando de migración, pregúntate:

- [ ] ¿Tengo un respaldo de mi base de datos?
- [ ] ¿Estoy usando `php artisan migrate` (sin fresh/refresh)?
- [ ] ¿Esta migración tiene protecciones con `Schema::hasTable()` o `Schema::hasColumn()`?
- [ ] ¿He verificado que el comando NO incluye `dropColumn` o `dropTable` sin mi aprobación?

---

## 🎯 RESUMEN

### Tu problema NO son las migraciones
Tus migraciones están bien estructuradas y tienen protecciones. El problema es que alguien/algo está ejecutando `migrate:fresh` o `migrate:refresh`.

### La solución
1. **Respalda tu DB ahora mismo**
2. **Usa solo `php artisan migrate` de ahora en adelante**
3. **Crea nuevas migraciones para modificaciones futuras**
4. **Nunca modifiques migraciones que ya fueron ejecutadas**

### Comando prohibido para desarrollo con datos reales:
```bash
# ❌ NUNCA EJECUTES ESTO EN DESARROLLO CON DATOS REALES
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:rollback
```

### Comando correcto:
```bash
# ✅ SIEMPRE USA ESTO
php artisan migrate
```

---

## 🆘 SI YA PERDISTE TUS DATOS

Si ya perdiste datos y tienes un backup:

```bash
# Restaurar desde backup
mysql -u root -p subwayapp < backup_FECHA.sql

# Verificar que se restauraron
php artisan tinker
>>> \App\Models\User::count();
>>> \App\Models\Menu\Product::count();
```

Si NO tienes backup:
- Los datos se perdieron permanentemente
- De ahora en adelante, usa `php artisan migrate` solamente
- Considera configurar backups automáticos

---

## 💡 PRÓXIMOS PASOS

1. **Ahora mismo:** Respalda tu base de datos
2. **Configura backups automáticos** (diarios o cada commit)
3. **Documenta el workflow** para tu equipo
4. **Usa solo `php artisan migrate`** de ahora en adelante
