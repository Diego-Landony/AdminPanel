# Scripts de Servidor Laravel

Este directorio contiene scripts útiles para gestionar el servidor de desarrollo de Laravel.

## 📁 Scripts Disponibles

### 🚀 `start-server.sh` - Iniciar Servidor
Inicia el servidor de Laravel con configuración optimizada.

**Características:**
- ✅ Limpia caché automáticamente
- ✅ Verifica dependencias (PHP, Composer)
- ✅ Detecta y libera puerto si está ocupado
- ✅ Muestra URLs disponibles
- ✅ Logs en tiempo real
- ✅ Manejo seguro de señales (Ctrl+C)

**Uso:**
```bash
./start-server.sh
```

### 🛑 `stop-server.sh` - Detener Servidor
Detiene el servidor de Laravel de forma segura.

**Características:**
- ✅ Detiene procesos de forma segura
- ✅ Libera puerto 8000
- ✅ Verifica que el servidor se haya detenido

**Uso:**
```bash
./stop-server.sh
```

### 📊 `server-status.sh` - Estado del Servidor
Muestra información detallada del estado del servidor.

**Características:**
- ✅ Estado del servidor (activo/inactivo)
- ✅ Información de procesos
- ✅ Prueba de conectividad
- ✅ URLs disponibles
- ✅ Información del sistema
- ✅ Espacio en disco y memoria

**Uso:**
```bash
./server-status.sh
```

## 🎯 URLs de Acceso

Una vez iniciado el servidor, puedes acceder desde:

- **🌐 Local:** `http://localhost:8000`
- **🌐 Red:** `http://[IP-DEL-SERVIDOR]:8000`
- **🌐 Dominio:** `https://dashboard.subwaycardgt.com`

## 🔧 Configuración

### Requisitos Previos
- PHP instalado y en PATH
- Composer instalado (recomendado)
- Estar en el directorio raíz del proyecto Laravel

### Permisos
Los scripts ya tienen permisos de ejecución. Si necesitas dar permisos manualmente:
```bash
chmod +x *.sh
```

## 📋 Comandos Útiles

### Iniciar servidor en background
```bash
nohup ./start-server.sh > server.log 2>&1 &
```

### Ver logs del servidor
```bash
tail -f server.log
```

### Verificar estado rápidamente
```bash
./server-status.sh
```

### Detener servidor desde otro terminal
```bash
./stop-server.sh
```

## 🚨 Solución de Problemas

### Puerto 8000 ocupado
```bash
# Ver qué proceso usa el puerto
lsof -i :8000

# Detener proceso específico
kill -9 [PID]
```

### Error de permisos
```bash
# Dar permisos de ejecución
chmod +x *.sh
```

### Servidor no responde
```bash
# Verificar estado
./server-status.sh

# Reiniciar servidor
./stop-server.sh
./start-server.sh
```

## 📝 Notas

- Los scripts están configurados para el puerto 8000
- El servidor se ejecuta en `0.0.0.0` para acceso desde red
- Los logs se muestran en tiempo real
- Ctrl+C detiene el servidor de forma segura

## 🔄 Flujo de Trabajo Recomendado

1. **Iniciar desarrollo:**
   ```bash
   ./start-server.sh
   ```

2. **Verificar estado:**
   ```bash
   ./server-status.sh
   ```

3. **Detener al terminar:**
   ```bash
   ./stop-server.sh
   ```

---

**Desarrollado para:** Videra - Subway Guatemala  
**Fecha:** $(date)  
**Versión:** 1.0 