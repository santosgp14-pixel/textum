# Textum — Sistema de Gestión Textil

**Textum** es una aplicación web responsive para empresas textiles, especializada en la venta de telas por metro y por peso. Incluye control de stock en tiempo real, pedidos con escáner de código de barras y balance automático diario.

> Diseñado como producto **SaaS multiempresa** (multi-tenant), listo para operar desde mostrador en desktop o mobile.

---

## Demo rápido

| Email | Contraseña | Rol |
|---|---|---|
| `admin@textilesdelsur.com` | `password` | Admin |
| `vendedor@textilesdelsur.com` | `password` | Vendedor |

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8+ con PDO, arquitectura MVC |
| Base de datos | MySQL 8 / MariaDB 10.6+ |
| Frontend | HTML5 + CSS3 + JavaScript Vanilla |
| Diseño | Mobile-first, paleta azul + gris |

No se usan frameworks pesados (ni React, ni Vue, ni Laravel, ni Symfony).

---

## Cómo correrlo localmente

### Requisitos

- PHP 8.1 o superior
- MySQL 8.0 / MariaDB 10.6
- Apache con `mod_rewrite` habilitado (o Nginx con config equivalente)

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/tu-usuario/textum.git
cd textum

# 2. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE textum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p textum < sql/textum_schema.sql

# 3. Configurar la aplicación
cp config/config.php config/config.local.php
# Editar config/config.local.php con tus credenciales DB y BASE_URL

# 4. Apuntar el DocumentRoot de Apache a: /ruta/al/proyecto/textum/public
# O usar PHP built-in server (solo desarrollo):
cd public && php -S localhost:8000
```

> Para PHP built-in server: abrir `http://localhost:8000/index.php?page=login`

---

## Estructura del proyecto

```
textum/
├── config/
│   └── config.php              # Configuración central (DB, rutas, sesión)
├── public/                     # DocumentRoot - único directorio público
│   ├── index.php               # Front Controller (único punto de entrada)
│   ├── css/
│   │   └── app.css             # Estilos mobile-first
│   ├── js/
│   │   └── app.js              # JS vanilla: barcode, pedido, anulación
│   └── assets/                 # Imágenes, iconos
├── sql/
│   └── textum_schema.sql       # Schema + datos de demo
├── src/
│   ├── core/
│   │   ├── Auth.php            # Autenticación y sesión
│   │   ├── Database.php        # Conexión PDO singleton
│   │   └── Router.php          # Router simple
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── StockController.php
│   │   ├── PedidosController.php
│   │   └── BalanceController.php
│   └── views/
│       ├── layout/             # Header y footer compartidos
│       ├── auth/               # Login
│       ├── dashboard/
│       ├── stock/              # Telas y variantes
│       ├── pedidos/            # Listado, abierto, detalle
│       ├── balance/
│       └── errors/
└── README.md
```

---

## Fase 1 — Funcionalidades incluidas

### Autenticación
- Login con email + contraseña (bcrypt)
- Sesiones con aislamiento por empresa (multi-tenant)
- Roles: `admin` y `vendedor`

### Gestión de stock
- Crear y editar telas (producto padre)
- Variantes ilimitadas por tela con:
  - Descripción, código de barras único, unidad (`metro` o `kilo`)
  - Mínimo de venta, precio, stock
- Alerta de stock bajo en dashboard

### Pedidos (flujo de mostrador)
- Crear pedido (estado: `abierto`)
- Agregar productos escaneando código de barras (el escáner actúa como teclado)
- Editar cantidades inline
- El pedido **NO** impacta stock ni balance hasta confirmar
- **Confirmar pedido**: descuenta stock + registra ingreso (transacción atómica)
- **Anular pedido**: solo admin, solo confirmados — repone stock y revierte ingreso, guarda motivo
- Nada se borra físicamente (registro permanente)

### Balance
- Ingresos del día (ventas confirmadas)
- Anulaciones del día
- Gastos manuales
- Resultado neto
- Selector de fecha para consultar días anteriores

---

## Modelo de datos clave

```
empresas ──┬── usuarios
           ├── telas ──── variantes
           ├── pedidos ──── pedido_items
           ├── movimientos_stock  (auditoría inmutable)
           ├── balance_movimientos
           └── gastos
```

**Regla fundamental:** `pedidos` no toca `stock` ni `balance_movimientos` hasta la confirmación. La transacción de confirmación es atómica: si falta stock en cualquier ítem, se hace rollback completo.

---

## Multi-tenant (SaaS)

Cada empresa tiene su propio espacio de datos. El aislamiento se garantiza mediante:
- `empresa_id` en todas las tablas principales
- Todos los queries filtran por `empresa_id = Auth::empresaId()`
- Un usuario solo ve los datos de su empresa

---

## Fase 2 — Planificada (no implementada)

El código está preparado para extenderse sin romper la Fase 1:

- **Catálogo público**: vista de productos para clientes externos
- **Clientes**: tabla `clientes` lista para relacionarse con pedidos
- **Reserva de stock**: campo `stock_reservado` en `variantes`, lógica de reserva por pedido
- **Solicitudes de pedido**: flujo de pre-pedido para clientes con aprobación
- **Reportes avanzados**: ventas por período, por variante, por vendedor
- **Más roles de usuario**: supervisor, depósito
- **Gestión de gastos categorizada**: con proveedores y comprobantes
- **API REST**: para integración con apps mobile nativas

---

## Decisiones de diseño

- **Simplicidad operativa primero**: la pantalla de pedido abierto está optimizada para el mostrador, no para la oficina.
- **Transacciones atómicas**: confirmar y anular pedidos usan `BEGIN / COMMIT / ROLLBACK` para garantizar consistencia.
- **Sin borrado físico**: pedidos, movimientos de stock y balance nunca se eliminan. Solo cambian de estado.
- **JS vanilla**: sin frameworks. La interactividad del pedido abierto pesa menos de 5KB.
- **CSS variables**: toda la paleta está en `:root`, fácil de tematizar por empresa en Fase 2.

---

## Licencia

MIT — libre para uso comercial y demo.
