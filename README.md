# API - Gestión de Usuarios y Transferencias

API REST desarrollada con Laravel 11 para la gestión de usuarios y transferencias bancarias con validaciones de seguridad avanzadas.

## Características Principales

- **CRUD completo de usuarios** con autenticación
- **Sistema de transferencias** entre usuarios
- **Validaciones robustas**: saldo suficiente, límites diarios, prevención de duplicados
- **Autenticación** con Laravel Sanctum (Bearer Token)
- **Exportación de datos** a CSV
- **Reportes y estadísticas** con consultas optimizadas
- **Testing completo** (12 tests unitarios)
- **Documentación interactiva** con Scribe
- **CHECK constraints** a nivel de base de datos
- **Transacciones atómicas** con rollback automático

---

## Tecnologías Utilizadas

- **Framework:** Laravel 11
- **Base de datos:** MySQL 8.0+
- **Autenticación:** Laravel Sanctum
- **Testing:** PHPUnit
- **Documentación:** Laravel Scribe
- **PHP:** 8.2+

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/FabianUrrutiaA/gestion-usuarios.git
cd gestion-usuarios
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` con tus credenciales de base de datos:

**Si usas XAMPP:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_usuarios
DB_USERNAME=root
DB_PASSWORD=
```

**Si usas otro servidor:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_usuarios
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Generar clave de aplicación

```bash
php artisan key:generate
```

### 5. Crear base de datos

Abre phpMyAdmin (http://localhost/phpmyadmin) o tu cliente MySQL y ejecuta:

```sql
CREATE DATABASE gestion_usuarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE gestion_usuarios_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Ejecutar migraciones

```bash
php artisan migrate
```

### 7. Iniciar el servidor

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000`

---

## Documentación de la API

### Acceso a la documentación

Visita: **http://localhost:8000/docs**


### Archivos adicionales

- **Colección Postman:** Importa `postman_collection.json`

---

## Autenticación

### 1. Login

```bash
POST /api/login
Content-Type: application/json

{
  "email": "usuario@example.com",
  "password": "tu_contraseña"
}
```

**Respuesta:**
```json
{
  "access_token": "1|abc123xyz...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "saldo": "1000.00"
  }
}
```

### 2. Usar el token

Incluye el token en el header de tus peticiones:

```bash
Authorization: Bearer 1|abc123xyz...
```

---

## 📍 Endpoints Principales

### **Usuarios**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/login` | Login de usuario |
| GET | `/api/obtenerUsuarios` | Listar todos los usuarios |
| GET | `/api/obtenerUsuario/{id}` | Obtener usuario por ID |
| POST | `/api/crearUsuario` | Crear nuevo usuario |
| PUT | `/api/editarUsuario/{id}` | Actualizar usuario |
| DELETE | `/api/eliminarUsuario/{id}` | Eliminar usuario |

### **Transferencias**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/crearTransferencia` | Crear transferencia |
| GET | `/api/exportarTransferenciasCSV` | Exportar a CSV |
| GET | `/api/totalTransferidoPorUsuario` | Total transferido por cada usuario |
| GET | `/api/promedioMontoPorUsuario` | Promedio de monto por usuario |
---

## Validaciones de Seguridad

### Transferencias

- **Saldo suficiente:** Valida que el emisor tenga fondos
- **Límite diario:** Máximo 5,000 USD por día por usuario
- **Límite por transacción:** Máximo 5,000 USD por transferencia
- **No auto-transferencias:** No se permite transferir a sí mismo
- **Prevención de duplicados:** Hash único con ventana de 5 minutos
- **Transacciones atómicas:** Rollback automático en caso de error

---

## 🧪 Testing

### Ejecutar todos los tests

```bash
php artisan test
```

### Tests implementados (12 tests)

**Usuarios:**
- Puede crear usuario
- No puede crear usuario con email duplicado
- Puede hacer login
- Falla login con credenciales incorrectas

**Transferencias:**
- Puede crear transferencia válida
- No puede transferir sin saldo suficiente
- No puede exceder límite diario de 5,000 USD
- No puede transferir monto mayor a 5,000 USD
- No puede transferir a sí mismo
- Detecta transferencias duplicadas

### Configuración de testing

Los tests usan MySQL (no SQLite). Base de datos de testing: `gestion_usuarios_test`

```bash
# Crear base de datos de testing
CREATE DATABASE gestion_usuarios_test;
```

---

## Exportación CSV

### Formato del archivo

- **Delimitador:** Punto y coma (`;`)
- **Codificación:** UTF-8 con BOM
- **Columnas:**
  - ID
  - Emisor ID
  - Emisor Nombre
  - Receptor ID
  - Receptor Nombre
  - Monto
  - Fecha de Creación
  - Hash Único

### Ejemplo

```csv
ID;"Emisor ID";"Emisor Nombre";"Receptor ID";"Receptor Nombre";Monto;"Fecha de Creación";"Hash Único"
1;1;"Juan Pérez";2;"María López";100.50;"2026-01-07 12:00:00";abc123...
```

---

## Optimización de Consultas

### Consultas implementadas

1. **Total transferido por usuario:**
   - Usa `SUM()` y `GROUP BY`
   - Eager loading con `with()`

2. **Promedio de monto por usuario:**
   - Usa `AVG()` y `COUNT()`
   - Optimizado para grandes volúmenes

3. **Estadísticas generales:**
   - Una sola query con agregaciones múltiples
   - `SUM()`, `AVG()`, `MAX()`, `MIN()`, `COUNT()`

---

## Estructura del Proyecto

```
gestion-usuarios/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── UserController.php
│   │       └── TransferenciaController.php
│   └── Models/
│       ├── User.php
│       └── Transferencia.php
├── database/
│   └── migrations/
│       ├── 0001_01_01_000000_create_users_table.php
│       └── 2026_01_07_011653_add_hash_unico_to_transferencia_table.php
├── routes/
│   └── api.php
├── tests/
│   └── Feature/
│       ├── UserTest.php
│       └── TransferenciaTest.php
├── config/
│   └── scribe.php
├── public/
│   └── docs/
│       └── index.html
└── README.md
```

---

### Error: "Base table or view not found"

```bash
php artisan migrate:fresh
```

### Tests fallan

Verifica que existe la base de datos de testing:
```sql
CREATE DATABASE gestion_usuarios_test;
```

---

## Autor

**Fabián Alejandro Urrutia Avendaño**
- Email: fabian.urrutia.aven@gmail.com
- GitHub: @FabianUrrutiaA

---

## Licencia

Este proyecto fue desarrollado como parte de una prueba técnica.

---

## Futuras Mejoras del proyecto

- [ ] Implementar paginación en listados
- [ ] Agregar filtros de búsqueda
- [ ] Notificaciones por email de transferencias
- [ ] Dashboard con gráficos estadísticos

---

**Fecha de creación:** Enero 2026  
**Versión:** 1.0.0  
**Laravel:** 11.x  
**PHP:** 8.2+