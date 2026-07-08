# php-mysqli-db_light
Clase ligera de abstracción para MySQLi adaptada a PHP 8+. Incluye sentencias preparadas automáticas y registro de errores en /var/log/.

## Mejoras clave en esta versión

* Tipado estricto de PHP 8.x: Todas las propiedades y argumentos de los métodos están tipados para evitar errores en tiempo de ejecución.
* Desempaquetado de argumentos moderno: Olvídate de envolver los parámetros en arrays manuales; ahora puedes pasarlos de forma natural como argumentos separados.
* Excepciones nativas: Control absoluto mediante bloques try/catch aprovechando el sistema de reporte de errores nativo de PHP.
* Registro de Logs para Producción: Sistema opcional de registro de fallos en segundo plano directamente en `/var/log/db_errors.log`.

------------------------------

# 📝 Guía de Uso

## 1. Conectar a la Base de Datos
Por defecto, la clase utiliza el juego de caracteres utf8mb4 para soportar correctamente emojis y caracteres especiales.

```PHP
include 'db.php';

$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'mi_base_datos';

// Conexión estándar (los errores lanzarán excepciones visibles en pantalla)
$db = new db($dbhost, $dbuser, $dbpass, $dbname);

// Conexión para Producción (activa el registro de errores silencioso en /var/log/db_errors.log)
$db = new db($dbhost, $dbuser, $dbpass, $dbname, 'utf8mb4', true);
```

## 2. Recuperar un Solo Registro
Ya no necesitas pasar las variables dentro de un array. Simplemente lístalas como argumentos justo después de tu consulta SQL.

```PHP
$account = $db->query('SELECT * FROM accounts WHERE username = ? AND password = ?', 'test', 'secret123')->fetchArray();

if (!empty($account)) {
    echo $account['name'];
}
```

## 3. Recuperar Múltiples Registros
Obtén todas las filas instantáneamente en un array asociativo:

```PHP
$accounts = $db->query('SELECT * FROM accounts WHERE status = ?', 'active')->fetchAll();

foreach ($accounts as $account) {
    echo $account['name'] . '<br>';
}
```

## Procesamiento eficiente con Callbacks (Ahorro de Memoria)
Para consultas pesadas con miles de filas, puedes pasar una función anónima (callback) para procesar los registros uno a uno sin sobrecargar la memoria RAM del servidor:

```PHP
$db->query('SELECT * FROM accounts')->fetchAll(function($account) {
    echo $account['name'];
    
    // Opcional: Detener el bucle si se cumple una condición
    if ($account['role'] === 'admin') {
        return 'break';
    }
});
```

## 4. Contar Filas (Consultas SELECT)
Devuelve la cantidad exacta de filas que ha devuelto una consulta:

```PHP
$accounts = $db->query('SELECT * FROM accounts WHERE role = ?', 'subscriber');
echo $accounts->numRows();
```

## 5. Filas Afectadas (INSERT, UPDATE, DELETE)
Comprueba cuántas filas se han modificado, eliminado o creado en la última consulta:

```PHP
$insert = $db->query('INSERT INTO accounts (username, email, name) VALUES (?, ?, ?)', 'johndoe', 'john@example.com', 'John');
echo $insert->affectedRows();
```

## 6. Obtener el último ID Insertado
Recupera el ID autoincremental generado por la última consulta INSERT:

```PHP
$db->query('INSERT INTO products (title, price) VALUES (?, ?)', 'Portátil', 999.99);
$productId = $db->lastInsertID();
```

## 7. Contador Global de Consultas
Lleva el control de cuántas consultas en total se han ejecutado durante el ciclo de vida del script actual:

```PHP
echo "Total de consultas ejecutadas: " . $db->query_count;
```

## 8. Cerrar la Conexión
La conexión se cierra sola automáticamente al terminar el script, pero puedes forzar el cierre manual en cualquier momento:

```PHP
$db->close();
```

------------------------------

# 🛡️ Gestión de Errores y Entornos (Try/Catch)
Al estar adaptada a PHP 8, la clase lanza excepciones nativas cuando algo falla (fallo de conexión, error de sintaxis SQL, etc.). En tu entorno de desarrollo local, esto te ayudará a ver el fallo inmediatamente.
Para evitar que los usuarios finales vean errores de código en producción, envuelve tus consultas en bloques try / catch:

```PHP
try {
    // Intentamos hacer una consulta
    $user = $db->query('SELECT * FROM usuarios WHERE id = ?', $userId)->fetchArray();
} catch (Exception $e) {
    // El error se captura de forma segura sin romper la web
    // Si activaste los logs en el constructor, el fallo ya estará guardado en /var/log/
    echo "Lo sentimos, ha ocurrido un problema técnico. Por favor, inténtelo más tarde.";
}
```

------------------------------

# ⚠️ Configuración del Archivo de Logs en Servidores Linux

Si activas el parámetro de logs (`true` en el constructor), la clase intentará escribir los errores en `/var/log/db_errors.log`.

Por seguridad, los servidores Linux restringen la escritura en la carpeta `/var/log/`. El usuario que ejecuta el servidor web (habitualmente `www-data`, `apache` o `nginx`) necesitará permisos. Si los errores no se guardan, un administrador del servidor debe ejecutar estos comandos en la terminal **una sola vez**:

```SH
# 1. Crear el archivo de registro vacío
sudo touch /var/log/db_errors.log

# 2. Hacer al servidor web (ej: www-data) dueño del archivo
sudo chown www-data:www-data /var/log/db_errors.log

# 3. Asignar permisos de lectura y escritura correctos
sudo chmod 660 /var/log/db_errors.log
```
------------------------------
