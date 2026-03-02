<?php
/**
 * Librería de funciones generales
 * Manejo de conexiones y consultas PostgreSQL
 */

/*------------------------------------------------------------------*/
/**
 * @brief Establece una conexión con PostgreSQL
 * @param string $host Dirección del servidor
 * @param string $port Puerto del servidor
 * @param string $dbname Nombre de la base de datos
 * @param string $user Usuario de la base de datos
 * @param string $password Contraseña del usuario
 * @return resource Recurso de conexión establecida
 * @pre PostgreSQL debe estar activo y accesible
 * @post Si la conexión falla, el script termina
 */
function pg_conectar($host, $port, $dbname, $user, $password)
/*--------------------------------------------------------------------*/
{
    $conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
    $conn = pg_connect($conn_string);
    
    if (!$conn) {
        die("<div style='color:red; padding:20px; border:2px solid red;'>"
           ."<h2>Error de Conexión a la Base de Datos</h2>"
           ."<p>No se pudo conectar a PostgreSQL.</p>"
           ."<p>Verifique que:</p>"
           ."<ul>"
           ."<li>PostgreSQL esté ejecutándose</li>"
           ."<li>La base de datos 'restaurante' exista</li>"
           ."<li>Las credenciales en etc/parametros.php sean correctas</li>"
           ."</ul>"
           ."</div>");
    }

    return $conn;
}

/*------------------------------------------------------------------*/
/**
 * @brief Ejecuta una sentencia SQL y devuelve los resultados
 * @param string $sentencia Consulta SQL a ejecutar
 * @param resource $conexion Recurso de conexión activa
 * @return object Objeto con 'cantidad' de registros y 'datos'
 * @pre La conexión debe estar abierta y la sentencia ser válida
 * @post Retorna los datos en formato de objeto
 */
function procesar_query($sentencia, $conexion)
/*--------------------------------------------------------------------*/
{
    $retorno = array();
    $respuesta = pg_query($conexion, $sentencia);
    
    if (!$respuesta) {
        $error = pg_last_error($conexion);
        die("<div style='color:red; padding:20px; border:2px solid red;'>"
           ."<h2>Error en la Consulta SQL</h2>"
           ."<p><strong>Error:</strong> $error</p>"
           ."<p><strong>Consulta:</strong></p><pre>$sentencia</pre>"
           ."</div>");
    }
    
    $Qregistros = pg_num_rows($respuesta);
    for ($i = 0; $i < $Qregistros; $i++) {
        $retorno[] = pg_fetch_array($respuesta, $i, PGSQL_ASSOC);
    }
    
    return (object) array('cantidad' => $Qregistros, 'datos' => $retorno);
}

/*------------------------------------------------------------------*/
/**
 * @brief Ejecuta una función de PostgreSQL
 * @param string $nombre_funcion Nombre de la función a ejecutar
 * @param array $parametros Array de parámetros
 * @param resource $conexion Recurso de conexión
 * @return mixed Resultado de la función
 */
function ejecutar_funcion($nombre_funcion, $parametros, $conexion)
/*--------------------------------------------------------------------*/
{
    $params_sql = array();
    foreach ($parametros as $param) {
        if (is_null($param)) {
            $params_sql[] = 'NULL';
        } elseif (is_numeric($param)) {
            $params_sql[] = $param;
        } else {
            $params_sql[] = "'" . pg_escape_string($conexion, $param) . "'";
        }
    }
    
    $sentencia = "SELECT * FROM $nombre_funcion(" . implode(', ', $params_sql) . ")";
    return procesar_query($sentencia, $conexion);
}

/*------------------------------------------------------------------*/
/**
 * @brief Formatea una variable para depuración
 * @param mixed $variable Variable a inspeccionar
 * @return string HTML con la estructura de la variable
 */
function Mostrar($variable)
/*--------------------------------------------------------------------*/
{
    $retorno = "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ddd;'>"
             . var_export($variable, true)
             . "</pre>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Genera HTML para un mensaje de éxito
 * @param string $mensaje Mensaje a mostrar
 * @return string HTML del mensaje
 */
function mensaje_exito($mensaje)
/*--------------------------------------------------------------------*/
{
    return "<div style='background:#d4edda; color:#155724; padding:15px; "
         . "border:1px solid #c3e6cb; border-radius:5px; margin:10px 0;'>"
         . "✓ $mensaje"
         . "</div>";
}

/*------------------------------------------------------------------*/
/**
 * @brief Genera HTML para un mensaje de error
 * @param string $mensaje Mensaje a mostrar
 * @return string HTML del mensaje
 */
function mensaje_error($mensaje)
/*--------------------------------------------------------------------*/
{
    return "<div style='background:#f8d7da; color:#721c24; padding:15px; "
         . "border:1px solid #f5c6cb; border-radius:5px; margin:10px 0;'>"
         . "✗ $mensaje"
         . "</div>";
}

/*------------------------------------------------------------------*/
/**
 * @brief Valida la sesión de usuario
 * @return bool True si la sesión es válida
 */
function validar_sesion()
/*--------------------------------------------------------------------*/
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['usuario_id']) && isset($_SESSION['rol']);
}

/*------------------------------------------------------------------*/
/**
 * @brief Obtiene el rol del usuario actual
 * @return string|null Rol del usuario o null si no está logueado
 */
function obtener_rol()
/*--------------------------------------------------------------------*/
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
}

?>
