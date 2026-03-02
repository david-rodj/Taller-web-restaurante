<?php
/**
 * Funciones específicas para el rol Cliente
 */

/*------------------------------------------------------------------*/
/**
 * @brief Cliente ve el menú del restaurante
 * @param resource $conn Conexión a la base de datos
 * @return string HTML con el menú
 */
function fn_cliente_ver_menu($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal() . "
    <h2>📋 Menú del Restaurante</h2>
    ";
    
    // Obtener tipos de platos
    $tipos = procesar_query("SELECT * FROM tipos ORDER BY id", $conn);
    
    foreach ($tipos->datos as $tipo) {
        $retorno .= "<div class='card'>";
        $retorno .= "<div class='card-header'>" . htmlspecialchars($tipo['nombre']) . "</div>";
        
        // Obtener platos de este tipo
        $platos = procesar_query("SELECT * FROM fn_menu_clientes() 
                                  WHERE tipo_id = {$tipo['id']}", $conn);
        
        if ($platos->cantidad > 0) {
            $retorno .= "<table>
            <tr>
                <th>Plato</th>
                <th>Descripción</th>
                <th>Precio</th>
            </tr>";
            
            foreach ($platos->datos as $plato) {
                $retorno .= "<tr>
                    <td><strong>" . htmlspecialchars($plato['nombre']) . "</strong></td>
                    <td>" . htmlspecialchars($plato['descripcion']) . "</td>
                    <td>$" . number_format($plato['precio'], 0, ',', '.') . "</td>
                </tr>";
            }
            
            $retorno .= "</table>";
        } else {
            $retorno .= "<p style='color:#666; padding:20px;'>No hay platos en esta categoría.</p>";
        }
        
        $retorno .= "</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Cliente ve sus reservaciones
 * @param resource $conn Conexión a la base de datos
 * @return string HTML con las reservaciones
 */
function fn_cliente_mis_reservaciones($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $cliente_id = $_SESSION['usuario_id'];
    
    $retorno = fn_boton_menu_principal() . "
    <h2>📅 Mis Reservaciones</h2>
    ";
    
    $sentencia = "
        SELECT r.id, r.cantidad, r.estado,
               h.mesa_id, h.inicio, h.duracion,
               CASE r.estado
                   WHEN 1 THEN 'Reservada'
                   WHEN 2 THEN 'Ocupada'
                   WHEN 3 THEN 'Liberada'
                   WHEN 4 THEN 'Cancelada'
               END as estado_texto
        FROM reservaciones r
        JOIN horarios h ON r.id = h.reservacion_id
        WHERE r.cliente_id = $cliente_id
        ORDER BY h.inicio DESC
        LIMIT 20
    ";
    
    $resultado = procesar_query($sentencia, $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table>
        <tr>
            <th>ID</th>
            <th>Mesa</th>
            <th>Fecha y Hora</th>
            <th>Duración</th>
            <th>Personas</th>
            <th>Estado</th>
        </tr>";
        
        foreach ($resultado->datos as $reservacion) {
            $clase_estado = '';
            switch($reservacion['estado']) {
                case 1: $clase_estado = 'style="background:#d1ecf1;"'; break;
                case 2: $clase_estado = 'style="background:#fff3cd;"'; break;
                case 3: $clase_estado = 'style="background:#d4edda;"'; break;
                case 4: $clase_estado = 'style="background:#f8d7da;"'; break;
            }
            
            $retorno .= "<tr $clase_estado>
                <td>{$reservacion['id']}</td>
                <td>Mesa {$reservacion['mesa_id']}</td>
                <td>" . date('d/m/Y H:i', strtotime($reservacion['inicio'])) . "</td>
                <td>{$reservacion['duracion']}</td>
                <td>{$reservacion['cantidad']} personas</td>
                <td>{$reservacion['estado_texto']}</td>
            </tr>";
        }
        
        $retorno .= "</table>";
    } else {
        $retorno .= "<div class='alert alert-info'>No tienes reservaciones registradas.</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Cliente ve sus pedidos
 * @param resource $conn Conexión a la base de datos
 * @return string HTML con los pedidos
 */
function fn_cliente_mis_pedidos($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $cliente_id = $_SESSION['usuario_id'];
    
    $retorno = fn_boton_menu_principal() . "
    <h2>🍽️ Mis Pedidos</h2>
    ";
    
    $sentencia = "
        SELECT p.id as pedido_id, 
               COUNT(o.id) as total_ordenes,
               SUM(o.cantidad) as total_platos,
               MIN(o.solicitado) as fecha_pedido,
               SUM(o.cantidad * pl.precio) as total_costo
        FROM pedidos p
        JOIN ordenes o ON p.id = o.pedido_id
        JOIN platos pl ON o.plato_id = pl.id
        WHERE p.cliente_id = $cliente_id
        GROUP BY p.id
        ORDER BY fecha_pedido DESC
        LIMIT 20
    ";
    
    $resultado = procesar_query($sentencia, $conn);
    
    if ($resultado->cantidad > 0) {
        foreach ($resultado->datos as $pedido) {
            $retorno .= "<div class='card'>
                <div class='card-header'>
                    Pedido #{$pedido['pedido_id']} - " . 
                    date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) . "
                </div>
                <p><strong>Total de órdenes:</strong> {$pedido['total_ordenes']}</p>
                <p><strong>Total de platos:</strong> {$pedido['total_platos']}</p>
                <p><strong>Costo total:</strong> $" . number_format($pedido['total_costo'], 0, ',', '.') . "</p>
                
                <a href='?opcion=cliente_detalle_pedido&pedido_id={$pedido['pedido_id']}' 
                   class='btn btn-primary'>Ver Detalle</a>
            </div>";
        }
    } else {
        $retorno .= "<div class='alert alert-info'>No tienes pedidos registrados.</div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Cliente ve detalle de un pedido
 * @param resource $conn Conexión a la base de datos
 * @return string HTML con el detalle
 */
function fn_cliente_detalle_pedido($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
    
    $retorno = fn_boton_menu_principal() . "
    <a href='?opcion=cliente_mis_pedidos' class='btn btn-primary' style='margin:10px;'>
        ← Volver a Mis Pedidos
    </a>
    <h2>🍽️ Detalle del Pedido #$pedido_id</h2>
    ";
    
    $sentencia = "
        SELECT pl.nombre, pl.precio,
               o.cantidad, o.estado,
               o.solicitado,
               CASE o.estado
                   WHEN 1 THEN 'Solicitado'
                   WHEN 2 THEN 'Preparado'
                   WHEN 3 THEN 'Entregado'
               END as estado_texto,
               (o.cantidad * pl.precio) as subtotal
        FROM ordenes o
        JOIN platos pl ON o.plato_id = pl.id
        WHERE o.pedido_id = $pedido_id
        ORDER BY o.id
    ";
    
    $resultado = procesar_query($sentencia, $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table>
        <tr>
            <th>Plato</th>
            <th>Precio Unitario</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
            <th>Estado</th>
            <th>Solicitado</th>
        </tr>";
        
        $total = 0;
        foreach ($resultado->datos as $orden) {
            $clase = '';
            switch($orden['estado']) {
                case 1: $clase = 'style="background:#fff3cd;"'; break;
                case 2: $clase = 'style="background:#d1ecf1;"'; break;
                case 3: $clase = 'style="background:#d4edda;"'; break;
            }
            
            $retorno .= "<tr $clase>
                <td><strong>" . htmlspecialchars($orden['nombre']) . "</strong></td>
                <td>$" . number_format($orden['precio'], 0, ',', '.') . "</td>
                <td>{$orden['cantidad']}</td>
                <td>$" . number_format($orden['subtotal'], 0, ',', '.') . "</td>
                <td>{$orden['estado_texto']}</td>
                <td>" . date('H:i', strtotime($orden['solicitado'])) . "</td>
            </tr>";
            
            $total += $orden['subtotal'];
        }
        
        $retorno .= "<tr style='background:#f8f9fa; font-weight:bold;'>
            <td colspan='3' style='text-align:right;'>TOTAL:</td>
            <td>$" . number_format($total, 0, ',', '.') . "</td>
            <td colspan='2'></td>
        </tr>";
        $retorno .= "</table>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Cliente ve su historial completo
 * @param resource $conn Conexión a la base de datos
 * @return string HTML con el historial
 */
function fn_cliente_mi_historial($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $cliente_id = $_SESSION['usuario_id'];
    
    $retorno = fn_boton_menu_principal() . "
    <h2>📊 Mi Historial Completo</h2>
    ";
    
    $resultado = ejecutar_funcion('fn_historial_cliente', array($cliente_id), $conn);
    
    if ($resultado->cantidad > 0) {
        $retorno .= "<table>
        <tr>
            <th>Tipo</th>
            <th>ID</th>
            <th>Fecha</th>
            <th>Detalles</th>
            <th>Estado</th>
        </tr>";
        
        foreach ($resultado->datos as $item) {
            $icono = $item['tipo'] == 'Reservación' ? '📅' : '🍽️';
            $retorno .= "<tr>
                <td>$icono {$item['tipo']}</td>
                <td>{$item['id']}</td>
                <td>" . date('d/m/Y H:i', strtotime($item['fecha'])) . "</td>
                <td>{$item['detalles']}</td>
                <td>{$item['estado']}</td>
            </tr>";
        }
        
        $retorno .= "</table>";
    } else {
        $retorno .= "<div class='alert alert-info'>No tienes historial registrado aún.</div>";
    }
    
    return $retorno;
}

?>
