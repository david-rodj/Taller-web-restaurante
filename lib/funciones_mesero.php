<?php
/**
 * Funciones específicas para el rol Mesero
 */

/*------------------------------------------------------------------*/
function fn_mesero_mis_pedidos($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $mesero_id = $_SESSION['usuario_id'];
    
    $retorno = fn_boton_menu_principal() . "<h2>📋 Mis Pedidos Activos</h2>";
    
    $resultado = ejecutar_funcion('fn_pedidos_listos_entregar', array($mesero_id), $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table><tr><th>Pedido ID</th><th>Órdenes Listas</th><th>Acción</th></tr>";
        foreach ($resultado->datos as $pedido) {
            $retorno .= "<tr>
                <td>#{$pedido['pedido_id']}</td>
                <td>{$pedido['ordenes_listas']} órdenes</td>
                <td><a href='?opcion=mesero_ver_detalle&pedido_id={$pedido['pedido_id']}' class='btn btn-primary'>Ver</a></td>
            </tr>";
        }
        $retorno .= "</table>";
    } else {
        $retorno .= "<div class='alert alert-info'>No tienes pedidos pendientes.</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_mesero_pedidos_listos($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $mesero_id = $_SESSION['usuario_id'];
    
    $retorno = fn_boton_menu_principal() . "<h2>✅ Pedidos Listos para Entregar</h2>";
    
    $sentencia = "
        SELECT o.id, o.pedido_id, pl.nombre, o.cantidad
        FROM ordenes o
        JOIN platos pl ON o.plato_id = pl.id
        JOIN pedidos p ON o.pedido_id = p.id
        WHERE p.mesero_id = $mesero_id AND o.estado = 2
        ORDER BY o.solicitado
    ";
    
    $resultado = procesar_query($sentencia, $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table><tr><th>Orden ID</th><th>Pedido</th><th>Plato</th><th>Cantidad</th><th>Acción</th></tr>";
        foreach ($resultado->datos as $orden) {
            $retorno .= "<tr>
                <td>{$orden['id']}</td>
                <td>#{$orden['pedido_id']}</td>
                <td>{$orden['nombre']}</td>
                <td>{$orden['cantidad']}</td>
                <td><a href='?opcion=mesero_entregar&orden_id={$orden['id']}' class='btn btn-success'>Entregar</a></td>
            </tr>";
        }
        $retorno .= "</table>";
    } else {
        $retorno .= "<div class='alert alert-info'>No hay pedidos listos.</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_mesero_entregar($conn)
/*--------------------------------------------------------------------*/
{
    $orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
    
    if ($orden_id > 0) {
        $resultado = ejecutar_funcion('fn_marcar_orden_entregada', array($orden_id), $conn);
        $retorno = mensaje_exito("Orden #$orden_id marcada como entregada");
        $retorno .= "<br/><a href='?opcion=mesero_pedidos_listos' class='btn btn-primary'>Volver</a>";
    } else {
        $retorno = mensaje_error("ID de orden inválido");
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
function fn_mesero_ver_menu($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "<h2>📖 Menú (con tiempos)</h2>";
    
    $resultado = ejecutar_funcion('fn_menu_meseros', array(), $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table><tr><th>Plato</th><th>Tiempo de Preparación</th></tr>";
        foreach ($resultado->datos as $plato) {
            $retorno .= "<tr><td>{$plato['nombre']}</td><td>{$plato['tiempo_preparacion']}</td></tr>";
        }
        $retorno .= "</table>";
    }
    
    return $retorno;
}

?>
