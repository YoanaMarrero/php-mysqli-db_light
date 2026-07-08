<?php

/**
 * Clase para la gestión simplificada y segura de bases de datos MySQL utilizando mysqli.
 * Configurada para escribir fallos en el directorio del sistema /var/log/.
 */
class db {
    // Propiedades de conexión y estado interno
    protected mysqli $connection;
    protected ?mysqli_stmt $query = null;
    protected bool $show_errors = true;
    protected bool $query_closed = true;
    public int $query_count = 0;
    
    /** @var bool Controla si los errores se registran en el archivo de log */
    protected bool $log_errors = false;

    /** @var string Ruta absoluta del archivo de log en el sistema */
    protected string $log_file = '/var/log/db_errors.log';

    /**
     * Constructor de la clase. Establece la conexión con la base de datos.
     */
    public function __construct(
        string $dbhost = 'localhost', 
        string $dbuser = 'root', 
        string $dbpass = '', 
        string $dbname = '', 
        string $charset = 'utf8mb4',
        bool $log_errors = false
    ) {
        $this->log_errors = $log_errors;

        // Activa el modo de excepciones nativo de mysqli
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $this->connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            $this->connection->set_charset($charset);
        } catch (mysqli_sql_exception $e) {
            $this->error('Failed to connect to MySQL - ' . $e->getMessage());
        }
    }

    /**
     * Prepara y ejecuta una consulta SQL de forma segura contra inyecciones SQL.
     */
    public function query(string $query, ...$params): self {
        if (!$this->query_closed && $this->query) {
            $this->query->close();
        }

        try {
            $this->query = $this->connection->prepare($query);
            
            if (!empty($params)) {
                $types = '';
                foreach ($params as $param) {
                    $types .= $this->_gettype($param);
                }
                $this->query->bind_param($types, ...$params);
            }

            $this->query->execute();
            $this->query_closed = false;
            $this->query_count++;
        } catch (mysqli_sql_exception $e) {
            $this->error('MySQL Error: ' . $e->getMessage() . ' | Query: ' . $query);
        }

        return $this;
    }

    /**
     * Devuelve todas las filas de la consulta ejecutada en un array asociativo.
     */
    public function fetchAll(?callable $callback = null): array {
        $result = $this->query->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        $this->query->close();
        $this->query_closed = true;

        if ($callback !== null) {
            $filteredResult = [];
            foreach ($data as $row) {
                $value = $callback($row);
                if ($value === 'break') break;
                $filteredResult[] = $row;
            }
            return $filteredResult;
        }

        return $data;
    }

    /**
     * Devuelve una única fila del resultado (la primera disponible).
     */
    public function fetchArray(): array {
        $result = $this->query->get_result();
        $row = $result->fetch_assoc() ?: [];
        
        $this->query->close();
        $this->query_closed = true;
        
        return $row;
    }

    /**
     * Cierra explícitamente la conexión con el servidor MySQL.
     */
    public function close(): bool {
        return $this->connection->close();
    }

    /**
     * Devuelve el número de filas devueltas por la consulta actual.
     */
    public function numRows(): int {
        if ($this->query) {
            $this->query->store_result();
            return $this->query->num_rows;
        }
        return 0;
    }

    /**
     * Devuelve el número de filas afectadas por la última consulta (INSERT, UPDATE, DELETE).
     */
    public function affectedRows(): int {
        return $this->query ? $this->query->affected_rows : 0;
    }

    /**
     * Obtiene el ID generado automáticamente por la última consulta INSERT.
     */
    public function lastInsertID(): int|string {
        return $this->connection->insert_id;
    }

    /**
     * Centraliza la gestión de errores de la clase.
     * Escribe en /var/log/db_errors.log y lanza excepciones si está permitido.
     *
     * @param string $error Mensaje detallado del error.
     * @throws Exception Lanza una excepción si $show_errors está activo.
     */
    public function error(string $error): void {
        if ($this->log_errors) {
            $timestamp = date('Y-m-d H:i:s');
            $log_message = "[$timestamp] [Database Error] $error" . PHP_EOL;
            
            // Forzamos la escritura en la ruta absoluta /var/log/db_errors.log
            // El operador '@' evita que PHP rompa el código si el servidor web no tiene permisos en esa carpeta
            if (@error_log($log_message, 3, $this->log_file) && !file_exists($this->log_file)) {
                @chmod($this->log_file, 0660); // Asigna permisos de lectura/escritura al propietario y grupo
            }
        }

        if ($this->show_errors) {
            throw new Exception($error);
        }
    }

    /**
     * Determina el tipo de dato de una variable para mysqli.
     */
    private function _gettype($var): string {
        if (is_float($var)) return 'd';
        if (is_int($var)) return 'i';
        return 's';
    }
}
?>
