# 📝 HOJA DE TRUCOS: Operaciones CRUD con la Clase `db`
Puedes usar esta guía rápida para copiar y pegar los fragmentos de código más comunes en tus desarrollos. 
Recuerda que **no necesitas pasar un array**, solo añade las variables separadas por comas en el mismo orden de los signos de interrogación `?`.

## 🔍 1. SELECCIONAR (SELECT) - Con múltiples condiciones
Para buscar registros filtrando por más de un campo a la vez en el `WHERE`.

**Obtener un solo registro (fetchArray):**

```PHP
// Ejemplo: Buscar un usuario activo por su email y rol
$usuario = $db->query(
    "SELECT id, nombre, email FROM usuarios WHERE email = ? AND rol = ? AND activo = ?", 
    'empleado@empresa.com', 'admin', 1
)->fetchArray();

if (!empty($usuario)) {
    echo "Bienvenido, " . $usuario['nombre'];
}
```

**Obtener múltiples registros (fetchAll):**

```PHP
// Ejemplo: Listar productos de una categoría específica y con stock disponible
$productos = $db->query(
    "SELECT * FROM productos WHERE categoria_id = ? AND stock > ? AND estado = ?", 
    5, 0, 'disponible'
)->fetchAll();

foreach ($productos as $producto) {
    echo $producto['nombre'] . " - Precio: " . $producto['precio'] . "<br>";
}
```

-----

# ➕ 2. INSERTAR (INSERT)
Para crear nuevos registros en la base de datos y obtener el ID generado.

```PHP
// 1. Ejecutar la inserción
$db->query(
    "INSERT INTO clientes (nombre, empresa, telefono, pais) VALUES (?, ?, ?, ?)", 
    'Carlos Mendoza', 'Innovación S.A.', '+34 600 000 000', 'España'
);

// 2. Comprobar si se insertó con éxito y obtener su ID
if ($db->affectedRows() > 0) {
    $nuevoId = $db->lastInsertID();
    echo "Cliente registrado correctamente con el ID: " . $nuevoId;
}
```

-----

## 🔄 3. ACTUALIZAR (UPDATE) - Con múltiples condiciones
⚠️ ¡Importante! El orden de los parámetros es vital: primero se pasan los valores que vas a modificar (`SET`) y al final los valores de los filtros (`WHERE`).

```PHP
// Ejemplo: Cambiar el precio y stock de un producto, pero solo si pertenece a un proveedor y categoría específicos
$nuevoPrecio = 49.99;
$nuevoStock = 120;
$proveedorId = 14;
$categoriaId = 3;

$db->query(
    "UPDATE productos SET precio = ?, stock = ? WHERE proveedor_id = ? AND categoria_id = ?", 
    $nuevoPrecio, $nuevoStock, $proveedorId, $categoriaId
);

echo "Productos actualizados: " . $db->affectedRows();
```

-----

## ❌ 4. ELIMINAR (DELETE) - Con múltiples condiciones
Para borrar registros del sistema aplicando varios filtros de seguridad en el `WHERE`.

```PHP
// Ejemplo: Eliminar un token de sesión caducado de un usuario específico
$usuarioId = 452;
$tipoToken = 'recuperacion_pass';
$estadoToken = 'expirado';

$db->query(
    "DELETE FROM tokens_sesion WHERE usuario_id = ? AND tipo = ? AND estado = ?", 
    $usuarioId, $tipoToken, $estadoToken
);

echo "Tokens eliminados: " . $db->affectedRows();
```

-----

## 🛡️ 5. Estructura Estándar de Seguridad (Try/Catch)
Para evitar pantallas en blanco, caídas de la web o que los usuarios de producción vean errores internos del código, **envuelve siempre tus bloques de consultas** de la siguiente manera:

```PHP
try {
    // 1. Conexión configurada para producción (los errores van silenciosamente a /var/log/)
    $db = new db('localhost', 'root', '', 'mi_base', 'utf8mb4', true);

    // 2. Ejecución de la lógica de negocio
    $db->query("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ? AND estado = ?", $usuarioId, 'activo');
    
    echo "Operación realizada con éxito.";

} catch (Exception $e) {
    // 3. Captura del fallo: La web no se rompe y el cliente ve un mensaje amigable
    // Si la opción de logs está en 'true', los detalles técnicos ya se habrán guardado en el servidor
    echo "Lo sentimos, en este momento no es posible procesar tu solicitud. Nuestro equipo técnico ya ha sido notificado.";
}
```

-----

# 💡 Recordatorio rápido de Tipos de Datos (Automático)

La clase detecta el tipo de dato por ti bajo el capó:
- Si pasas un número entero (`10`), enviará una `i` (Integer).
- Si pasas un número decimal (`99.99`), enviará una `dv (Double/Float).
- Para todo lo demás (textos, fechas `2026-07-08`, booleanos, etc.), enviará una `s` (String).
  
