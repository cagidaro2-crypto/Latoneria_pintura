# Documentación: database.php

## Descripción general
Archivo de configuración de la conexión a la base de datos MySQL. Define la clase `Database` que encapsula las credenciales y crea la conexión PDO. Es el punto central que todos los controladores y modelos incluyen para conectarse a la base de datos `latoneria_pintura`.

## Dependencias
Ninguna (es el archivo base del sistema).

## Código documentado línea por línea

```php
// Línea 1: Apertura del bloque PHP
<?php

// Línea 2: Definición de la clase Database que gestiona la conexión a MySQL
class Database
{
    // Línea 4: IP del servidor de base de datos (localhost en IPv4)
    private $host     = "127.0.0.1";

    // Línea 5: Puerto estándar de MySQL
    private $port     = "3306";

    // Línea 6: Nombre de la base de datos del proyecto
    private $db_name  = "latoneria_pintura";

    // Línea 7: Usuario de la base de datos (por defecto en Laragon)
    private $username = "root";

    // Línea 8: Contraseña vacía (configuración local de desarrollo)
    private $password = "";

    // Línea 10: Propiedad pública que almacenará el objeto PDO activo
    public $conn;

    // Línea 12-26: Método público que crea y retorna la conexión PDO
    public function conectar()
    {
        // Línea 14: Inicializa la conexión en null antes de crearla
        $this->conn = null;

        try {
            // Línea 17: Construye el DSN (Data Source Name) con host, puerto, db y charset
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            // Línea 18: Crea la instancia PDO con las credenciales configuradas
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Línea 19: Configura PDO para lanzar excepciones en errores (modo estricto)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            // Línea 21: Si falla la conexión, detiene la ejecución y muestra el error
            die("Error de conexión: " . $e->getMessage());
        }

        // Línea 24: Retorna el objeto PDO para que el llamador lo use
        return $this->conn;
    }
}
// Línea 27: Cierre del bloque PHP
?>
```

## Resumen de funciones/métodos

| Método | Parámetros | Retorno | Descripción |
|--------|-----------|---------|-------------|
| `conectar()` | Ninguno | `PDO` | Crea y retorna la conexión PDO a MySQL. Termina la ejecución si falla. |

## Flujo de ejecución
1. El archivo se incluye con `require_once` desde cualquier controlador o modelo.
2. Se instancia la clase: `$db = (new Database())->conectar();`.
3. El método `conectar()` construye el DSN con los parámetros de la clase.
4. Se crea el objeto `PDO` con el DSN y credenciales.
5. Se configura el modo de errores para lanzar excepciones.
6. Se retorna el objeto PDO listo para ejecutar consultas.
7. Si la conexión falla, `die()` detiene el script mostrando el mensaje de error.
