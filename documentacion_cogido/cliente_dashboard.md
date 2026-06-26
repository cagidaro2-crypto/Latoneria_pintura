# Documentación: cliente_dashboard.php

## Descripción general
Panel principal del cliente. Muestra un banner de bienvenida, tarjetas resumen (vehículos, cotizaciones, citas), panel de estructura del sistema por roles, tabla de vehículos recientes y accesos rápidos a los módulos del cliente.

## Dependencias
- `config/database.php` — conexión PDO
- `layouts/header.php` — sidebar y topbar
- `layouts/footer.php` — cierre HTML
- `cliente_styles.php` — estilos CSS compartidos del módulo cliente

## Flujo de ejecución
1. Verifica sesión y rol cliente (3)
2. Busca `id_cliente` en tabla `clientes` por el correo de sesión
3. Consulta vehículos, cotizaciones y citas próximas del cliente
4. Consulta contadores globales de usuarios por rol
5. Incluye header, carga estilos del cliente
6. Renderiza banner, tarjetas, tabla de vehículos y accesos rápidos

## Código documentado por bloques

### Guard de sesión
```php
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}
// rol 3 = Cliente. Si no está logueado o no es cliente, redirige al login
```

### Búsqueda de id_cliente
```php
try {
    $s = $db->prepare("SELECT id_cliente FROM clientes WHERE correo = :c LIMIT 1");
    $s->execute([':c' => $usuario['correo'] ?? '']);
    // Busca el id_cliente en la tabla clientes usando el correo de la sesión
    // LIMIT 1 asegura que solo se retorne un resultado
    $r = $s->fetch(PDO::FETCH_ASSOC);
    $idCliente = $r ? (int)$r['id_cliente'] : null;
    // Si se encontró resultado, extrae el id_cliente; si no, null
} catch (Exception $e) {}
// try/catch evita errores fatales si la tabla no existe o la query falla
```

### Consulta de cotizaciones
```php
$s = $db->prepare("SELECT COUNT(*) AS total,
    SUM(CASE WHEN estado='Pendiente' THEN 1 ELSE 0 END) AS pendientes
    FROM cotizaciones WHERE id_cliente=:id");
// COUNT(*) cuenta todas las cotizaciones del cliente
// SUM(CASE...) cuenta solo las que tienen estado 'Pendiente'
// Esto obtiene ambos datos en una sola query (más eficiente)
```

### Contadores globales de roles
```php
$s = $db->query("SELECT id_rol, COUNT(*) AS cnt FROM usuarios GROUP BY id_rol");
// GROUP BY id_rol agrupa los usuarios por rol y COUNT(*) cuenta cuántos hay en cada grupo
foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ((int)$row['id_rol'] === 1) $totalAdmins    = (int)$row['cnt'];
    // Asigna el conteo a la variable correspondiente según el id_rol
}
```

### Banner de bienvenida
```php
<h4>Bienvenido, <?= htmlspecialchars($usuario['nombres'] ?? 'Cliente') ?> 👋</h4>
// htmlspecialchars() previene XSS al mostrar datos del usuario
// ?? 'Cliente' es el fallback si 'nombres' no está en la sesión
```

### Tarjetas resumen (array de configuración)
```php
$stats = [
    ['icon'=>'fa-car', 'color'=>'#000000', 'bg'=>'#f1f5f9',
     'label'=>'Mis Vehículos', 'value'=>count($vehiculos)],
    // icon: clase de Font Awesome
    // color/bg: color del ícono y fondo del cuadro de ícono
    // label: texto descriptivo debajo del número
    // value: número calculado previamente
    ...
];
foreach ($stats as $st):
    // Itera sobre el array para renderizar una tarjeta por cada elemento
```

### Tabla de vehículos (últimos 6)
```php
foreach (array_slice($vehiculos, 0, 6) as $v):
// array_slice toma máximo 6 elementos del array de vehículos para no mostrar todos
```

### Historial del vehículo en la tabla
```php
$sh = $db->prepare("SELECT tipo_reparacion, fecha_registro FROM historial_vehiculo
     WHERE id_vehiculo=:id ORDER BY fecha_registro DESC LIMIT 1");
// Obtiene solo el registro más reciente del historial para mostrar como "último registro"
// ORDER BY DESC + LIMIT 1 = el más reciente
```

### Accesos rápidos
```php
$accesos = [
    ['href'=>'cliente_vehiculos.php', 'icon'=>'fa-car', 'bg'=>'#f1f5f9',
     'clr'=>'#000000', 'title'=>'Mis Vehículos', 'sub'=>'Registrar o consultar'],
    // Cada elemento define un botón de acceso rápido con su ícono, colores y texto
];
foreach ($accesos as $a):
    // Renderiza un enlace visual por cada acceso rápido
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$idCliente` | int\|null | ID del cliente en tabla clientes |
| `$vehiculos` | array | Vehículos del cliente |
| `$cotTotal` | int | Total de cotizaciones |
| `$cotPendientes` | int | Cotizaciones pendientes |
| `$citasProximas` | int | Citas futuras activas |
| `$totalAdmins/Empleados/Clientes` | int | Contadores por rol |
| `$totalVehiculos` | int | Total de vehículos en el sistema |
| `$totalOrdenes` | int | Total de órdenes de trabajo |
