<?php
/**
 * Funciones específicas para el rol Administrador
 */

/*------------------------------------------------------------------*/
function fn_admin_gestion_mesas($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "<h2>Gestión de Mesas</h2>";
    
    // Agregar mesa
    if (isset($_POST['agregar_mesa'])) {
        $sillas = intval($_POST['sillas']);
        $resultado = ejecutar_funcion('fn_insertar_mesa', array($sillas), $conn);
        $retorno .= mensaje_exito("Mesa creada con ID: " . $resultado->datos[0]['fn_insertar_mesa']);
    }
    
    // Listar mesas
    $mesas = ejecutar_funcion('fn_listar_mesas', array(), $conn);
    $cupo_total = ejecutar_funcion('fn_calcular_cupo_total', array(), $conn);
    
    $retorno .= "<div class='alert alert-info'>
        <strong>Cupo Total del Restaurante:</strong> {$cupo_total->datos[0]['fn_calcular_cupo_total']} personas
    </div>";
    
    $retorno .= "<div class='card'>
        <h3>Agregar Nueva Mesa</h3>
        <form method='POST'>
            <div class='form-group'>
                <label>Número de sillas:</label>
                <input type='number' name='sillas' min='1' max='20' required />
            </div>
            <button type='submit' name='agregar_mesa' class='btn btn-success'>Agregar Mesa</button>
        </form>
    </div>";
    
    $retorno .= "<h3>Mesas Registradas</h3>";
    $retorno .= "<table><tr><th>ID</th><th>Sillas</th><th>Acciones</th></tr>";
    
    foreach ($mesas->datos as $mesa) {
        $retorno .= "<tr>
            <td>{$mesa['mesa_id']}</td>
            <td>{$mesa['sillas']}</td>
            <td>
                <a href='?opcion=admin_modificar_mesa&id={$mesa['mesa_id']}' class='btn btn-warning'>Modificar</a>
                <a href='?opcion=admin_eliminar_mesa&id={$mesa['mesa_id']}' class='btn btn-danger' 
                   onclick='return confirm(\"¿Eliminar mesa {$mesa['mesa_id']}?\")'>Eliminar</a>
            </td>
        </tr>";
    }
    
    $retorno .= "</table>";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_admin_gestion_menu($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "<h2>Gestión del Menú</h2>";
    
    // Listar platos
    $platos = ejecutar_funcion('fn_listar_platos_con_especialistas', array(), $conn);
    
    $retorno .= "<h3>Platos Registrados</h3>";
    $retorno .= "<table>
        <tr><th>ID</th><th>Nombre</th><th>Especialistas</th><th>Tiempo</th><th>Precio</th><th>Acciones</th></tr>";
    
    foreach ($platos->datos as $plato) {
        $retorno .= "<tr>
            <td>{$plato['plato_id']}</td>
            <td><strong>{$plato['nombre_plato']}</strong></td>
            <td>" . ($plato['cocineros'] ?: 'Sin especialistas') . "</td>
            <td>{$plato['tiempo']}</td>
            <td>$" . number_format($plato['precio'], 0, ',', '.') . "</td>
            <td>
                <a href='?opcion=admin_modificar_plato&id={$plato['plato_id']}' class='btn btn-warning'>Modificar</a>
            </td>
        </tr>";
    }
    
    $retorno .= "</table>";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_admin_reportes($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "<h2>Reportes y Estadísticas</h2>";
    
    // Estadísticas generales
    $stats = ejecutar_funcion('fn_estadisticas_restaurante', array(), $conn);
    
    $retorno .= "<div class='grid'>";
    foreach ($stats->datos as $stat) {
        $retorno .= "<div class='stat-card'>
            <div class='stat-label'>{$stat['concepto']}</div>
            <div class='stat-value'>{$stat['valor']}</div>
        </div>";
    }
    $retorno .= "</div>";
    
    // Platos más populares
    $populares = ejecutar_funcion('fn_reporte_platos_populares', array(10), $conn);
    
    $retorno .= "<div class='card'>
        <h3>Top 10 Platos Más Vendidos</h3>
        <table>
            <tr><th>#</th><th>Plato</th><th>Veces Pedido</th><th>Total Cantidad</th></tr>";
    
    $pos = 1;
    foreach ($populares->datos as $plato) {
        $retorno .= "<tr>
            <td>$pos</td>
            <td><strong>{$plato['plato_nombre']}</strong></td>
            <td>{$plato['veces_pedido']}</td>
            <td>{$plato['total_cantidad']}</td>
        </tr>";
        $pos++;
    }
    
    $retorno .= "</table></div>";
    
    // Ventas por día (últimos 7 días)
    $ventas = ejecutar_funcion('fn_reporte_ventas_dia', array(null, null), $conn);
    
    $retorno .= "<div class='card'>
        <h3>Ventas por Día</h3>
        <table>
            <tr><th>Fecha</th><th>Total Ventas</th></tr>";
    
    foreach ($ventas->datos as $venta) {
        $retorno .= "<tr>
            <td>" . date('d/m/Y', strtotime($venta['fecha'])) . "</td>
            <td>$" . number_format($venta['total_ventas'], 0, ',', '.') . "</td>
        </tr>";
    }
    
    $retorno .= "</table></div>";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_admin_reservaciones($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "<h2>Todas las Reservaciones</h2>";
    
    $sentencia = "
        SELECT r.id, u.nombre as cliente, h.mesa_id, h.inicio, r.cantidad,
               CASE r.estado WHEN 1 THEN 'Reservada' WHEN 2 THEN 'Ocupada' 
                             WHEN 3 THEN 'Liberada' WHEN 4 THEN 'Cancelada' END as estado_texto
        FROM reservaciones r
        JOIN usuarios u ON r.cliente_id = u.id
        JOIN horarios h ON r.id = h.reservacion_id
        ORDER BY h.inicio DESC
        LIMIT 50
    ";
    
    $resultado = procesar_query($sentencia, $conn);
    
    $retorno .= "<table>
        <tr><th>ID</th><th>Cliente</th><th>Mesa</th><th>Fecha</th><th>Personas</th><th>Estado</th></tr>";
    
    foreach ($resultado->datos as $res) {
        $retorno .= "<tr>
            <td>{$res['id']}</td>
            <td>{$res['cliente']}</td>
            <td>Mesa {$res['mesa_id']}</td>
            <td>" . date('d/m/Y H:i', strtotime($res['inicio'])) . "</td>
            <td>{$res['cantidad']}</td>
            <td>{$res['estado_texto']}</td>
        </tr>";
    }
    
    $retorno .= "</table>";
    
    return $retorno;
}

?>
