<?php
/**
 * Funciones específicas para el rol Cocinero
 */

/*------------------------------------------------------------------*/
function fn_cocinero_pedidos_pendientes($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "<h2>Pedidos Pendientes</h2>";
    
    $sentencia = "
        SELECT o.id, o.pedido_id, pl.nombre, pl.tiempo, o.cantidad, o.solicitado,
               CASE o.estado WHEN 1 THEN 'Solicitado' WHEN 2 THEN 'Preparado' END as estado_texto
        FROM ordenes o
        JOIN platos pl ON o.plato_id = pl.id
        WHERE o.estado IN (1, 2)
        ORDER BY pl.tiempo DESC, o.solicitado
        LIMIT 50
    ";
    
    $resultado = procesar_query($sentencia, $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table><tr><th>Orden</th><th>Plato</th><th>Tiempo Prep.</th><th>Cantidad</th><th>Solicitado</th><th>Estado</th><th>Acción</th></tr>";
        foreach ($resultado->datos as $orden) {
            $clase = $orden['estado_texto'] == 'Solicitado' ? 'style="background:#fff3cd;"' : 'style="background:#d1ecf1;"';
            $retorno .= "<tr $clase>
                <td>#{$orden['id']}</td>
                <td>{$orden['nombre']}</td>
                <td>{$orden['tiempo']}</td>
                <td>{$orden['cantidad']}</td>
                <td>" . date('H:i', strtotime($orden['solicitado'])) . "</td>
                <td>{$orden['estado_texto']}</td>
                <td>";
            
            if ($orden['estado_texto'] == 'Solicitado') {
                $retorno .= "<a href='?opcion=cocinero_marcar_listo_accion&orden_id={$orden['id']}' class='btn btn-success'>Marcar Listo</a>";
            } else {
                $retorno .= "✓ Listo";
            }
            
            $retorno .= "</td></tr>";
        }
        $retorno .= "</table>";
    } else {
        $retorno .= "<div class='alert alert-info'>No hay pedidos pendientes.</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_cocinero_mis_especialidades($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $cocinero_id = $_SESSION['usuario_id'];
    
    $retorno = fn_boton_menu_principal() . "<h2>Mis Especialidades</h2>";
    
    $resultado = ejecutar_funcion('fn_menu_cocinero', array($cocinero_id), $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table><tr><th>ID</th><th>Plato</th><th>Descripción</th><th>Tiempo</th></tr>";
        foreach ($resultado->datos as $plato) {
            $retorno .= "<tr>
                <td>{$plato['plato_id']}</td>
                <td><strong>{$plato['nombre']}</strong></td>
                <td>{$plato['descripcion']}</td>
                <td>{$plato['tiempo']}</td>
            </tr>";
        }
        $retorno .= "</table>";
    } else {
        $retorno .= "<div class='alert alert-info'>No tienes especialidades asignadas.</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_cocinero_marcar_listo_accion($conn)
/*--------------------------------------------------------------------*/
{
    $orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
    
    if ($orden_id > 0) {
        $resultado = ejecutar_funcion('fn_actualizar_estado_orden', array($orden_id, 2), $conn);
        $retorno = mensaje_exito("Orden #$orden_id marcada como lista/preparada");
        $retorno .= "<br/><a href='?opcion=cocinero_pedidos_pendientes' class='btn btn-primary'>Volver</a>";
    } else {
        $retorno = mensaje_error("ID de orden inválido");
    }
    
    return $retorno;
}

?>
