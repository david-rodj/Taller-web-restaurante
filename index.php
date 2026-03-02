<?php
/**
 * Sistema de Gestión de Restaurante
 * Pontificia Universidad Javeriana
 * Bases de Datos - 2025-1
 * Ing. Ricardo Naranjo Faccini, MSc
 */

//------------------------------------------------------------
// Configuración de errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//------------------------------------------------------------
// Incluir librerías
require_once("etc/parametros.php");
require_once("lib/libreria.php");
require_once("lib/restaurante.php");
require_once("lib/funciones_cliente.php");
require_once("lib/funciones_mesero.php");
require_once("lib/funciones_cocinero.php");
require_once("lib/funciones_admin.php");

//------------------------------------------------------------
// Establecer conexión
$conn = pg_conectar($host, $port, $dbname, $user, $password);

//------------------------------------------------------------
// Procesar opción solicitada
$opcion = isset($_REQUEST['opcion']) ? $_REQUEST['opcion'] : '';
$contenido = "";

// Si no hay opción o no está logueado, mostrar login
if ($opcion == '' || $opcion == 'login') {
    $contenido = fn_login($conn);
} 
// Logout
elseif ($opcion == 'logout') {
    fn_logout();
} 
// Verificar sesión para todas las demás opciones
elseif (!validar_sesion()) {
    header("Location: ?opcion=login");
    exit();
}
// Procesar opción
else {
    $funcion = "fn_" . $opcion;
    if (function_exists($funcion)) {
        $contenido = $funcion($conn);
    } else {
        $rol = obtener_rol();
        $contenido = call_user_func("fn_menu_$rol", $conn);
    }
}

//------------------------------------------------------------
// Cargar plantilla HTML
$esqueleto = file_get_contents("esqueleto.html");
$html = sprintf($esqueleto, $contenido);
echo $html;

//------------------------------------------------------------
// Cerrar conexión
pg_close($conn);
?>
