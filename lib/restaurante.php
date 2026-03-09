<?php
/**
 * Funciones específicas del restaurante
 * Manejo de la lógica de negocio
 */

/*------------------------------------------------------------------*/
/**
 * @brief Genera botón para volver al menú principal
 * @return string HTML del botón
 */
function fn_boton_menu_principal()
/*--------------------------------------------------------------------*/
{
    $rol = obtener_rol();
    $script = "window.location.href='?opcion=menu_$rol';";
    $retorno = "<button onclick=\"$script\" style='margin:10px; padding:10px 20px; "
             . "background:#007bff; color:white; border:none; border-radius:5px; "
             . "cursor:pointer;'> Menú Principal</button>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Genera botón para cerrar sesión
 * @return string HTML del botón
 */
function fn_boton_cerrar_sesion()
/*--------------------------------------------------------------------*/
{
    $script = "window.location.href='?opcion=logout';";
    $retorno = "<button onclick=\"$script\" style='margin:10px; padding:10px 20px; "
             . "background:#dc3545; color:white; border:none; border-radius:5px; "
             . "cursor:pointer;'> Cerrar Sesión</button>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Pantalla de login
 * @param resource $conn Conexión a la base de datos
 * @return string HTML del formulario de login
 */
function fn_login($conn)
/*--------------------------------------------------------------------*/
{
    $mensaje = "";
    
    if (isset($_POST['login'])) {
        $usuario_nombre = pg_escape_string($conn, $_POST['usuario']);
        $clave = $_POST['clave'];
        
        // Buscar usuario
        $sentencia = "SELECT u.id, u.nombre, u.clave, r.nombre as rol
                     FROM usuarios u
                     JOIN actuaciones a ON u.id = a.usuario_id
                     JOIN roles r ON a.rol_id = r.id
                     WHERE u.nombre = '$usuario_nombre'
                     LIMIT 1";
        
        $resultado = procesar_query($sentencia, $conn);
        
        if ($resultado->cantidad > 0) {
            $usuario = $resultado->datos[0];
            
            // Verificar contraseña con bcrypt
            $sentencia_verify = "SELECT (crypt('$clave', clave) = clave) as valido 
                                FROM usuarios WHERE id = {$usuario['id']}";
            $verify = procesar_query($sentencia_verify, $conn);
            
            if ($verify->datos[0]['valido'] == 't') {
                // Login exitoso
                session_start();
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['rol'];
                
                header("Location: ?opcion=menu_" . $usuario['rol']);
                exit();
            } else {
                $mensaje = mensaje_error("Contraseña incorrecta");
            }
        } else {
            $mensaje = mensaje_error("Usuario no encontrado");
        }
    }
    
    $retorno = "
    <div style='max-width:450px; margin:50px auto; padding:30px; background:white; 
                border-radius:10px; box-shadow:0 2px 10px rgba(93,64,55,0.1);
                border-top:3px solid var(--color-primary);'>
        <h1 style='text-align:center; color:var(--color-dark); font-family:Georgia,serif;'>
             Restaurante Javeriano
        </h1>
        <p style='text-align:center; color:#666; margin-bottom:30px;'>
            Sistema de Gestión - Universidad Javeriana
        </p>
        
        $mensaje
        
        <form method='POST' action=''>
            <div class='form-group'>
                <label>Usuario:</label>
                <input type='text' name='usuario' required 
                       placeholder='Nombre completo' />
            </div>
            
            <div class='form-group'>
                <label>Contraseña:</label>
                <input type='password' name='clave' required 
                       placeholder='Contraseña' />
            </div>
            
            <button type='submit' name='login' class='btn btn-success' 
                    style='width:100%; margin-bottom:15px;'>
                Ingresar
            </button>
        </form>
        
        <div style='text-align:center; margin-top:20px;'>
            <p style='color:#666;'>¿No tienes cuenta?</p>
            <a href='?opcion=registro' class='btn btn-primary'>Crear Cuenta Nueva</a>
        </div>
        
        <div style='margin-top:30px; padding:15px; background:#f8f9fa; 
                    border-radius:5px; font-size:14px; border-left:3px solid var(--color-primary);'>
            <strong>Usuarios de prueba:</strong><br/>
            • Contraseña: <strong>Colombia2024</strong><br/><br/>
            <strong>Clientes:</strong> Carlos Andrés Rodríguez, María Fernanda López<br/>
            <strong>Meseros:</strong> Andrés Felipe Castillo, Laura Daniela Martínez<br/>
            <strong>Cocineros:</strong> Pedro Antonio García, Rosa María Rodríguez<br/>
            <strong>Admin:</strong> Ricardo Naranjo Faccini
        </div>
    </div>
    ";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Pantalla de registro de nuevo usuario
 * @param resource $conn Conexión a la base de datos
 * @return string HTML del formulario de registro
 */
function fn_registro($conn)
/*--------------------------------------------------------------------*/
{
    $mensaje = "";
    
    if (isset($_POST['registrar'])) {
        $nombre = pg_escape_string($conn, trim($_POST['nombre']));
        $clave = $_POST['clave'];
        $clave_confirm = $_POST['clave_confirm'];
        $rol_id = 1; // Solo clientes por defecto
        
        // Validaciones
        if (strlen($nombre) < 3) {
            $mensaje = mensaje_error("El nombre debe tener al menos 3 caracteres");
        } elseif (strlen($clave) < 6) {
            $mensaje = mensaje_error("La contraseña debe tener al menos 6 caracteres");
        } elseif ($clave !== $clave_confirm) {
            $mensaje = mensaje_error("Las contraseñas no coinciden");
        } else {
            // Verificar si el usuario ya existe
            $check = procesar_query("SELECT id FROM usuarios WHERE nombre = '$nombre'", $conn);
            
            if ($check->cantidad > 0) {
                $mensaje = mensaje_error("Ya existe un usuario con ese nombre");
            } else {
                // Crear usuario con contraseña encriptada
                $sentencia = "INSERT INTO usuarios (nombre, clave, fecha_clave) 
                             VALUES ('$nombre', crypt('$clave', gen_salt('bf')), NOW()) 
                             RETURNING id";
                
                $resultado = procesar_query($sentencia, $conn);
                
                if ($resultado->cantidad > 0) {
                    $usuario_id = $resultado->datos[0]['id'];
                    
                    // Asignar rol Cliente
                    $sentencia_rol = "INSERT INTO actuaciones (rol_id, usuario_id) 
                                     VALUES ($rol_id, $usuario_id)";
                    procesar_query($sentencia_rol, $conn);
                    
                    $mensaje = mensaje_exito("¡Cuenta creada exitosamente! Redirigiendo al login...");
                    $mensaje .= "<script>setTimeout(function(){ window.location.href='?opcion=login'; }, 2000);</script>";
                } else {
                    $mensaje = mensaje_error("Error al crear la cuenta");
                }
            }
        }
    }
    
    $retorno = "
    <div style='max-width:500px; margin:50px auto; padding:30px; background:white; 
                border-radius:10px; box-shadow:0 2px 10px rgba(93,64,55,0.1);
                border-top:3px solid var(--color-primary);'>
        <h1 style='text-align:center; color:var(--color-dark); font-family:Georgia,serif;'>
            Crear Cuenta Nueva
        </h1>
        <p style='text-align:center; color:#666; margin-bottom:30px;'>
            Regístrate para acceder al sistema
        </p>
        
        $mensaje
        
        <form method='POST' action=''>
            <div class='form-group'>
                <label>Nombre Completo:</label>
                <input type='text' name='nombre' required 
                       placeholder='Ej: Juan Pérez González'
                       minlength='3' />
                <small style='color:#666;'>Mínimo 3 caracteres</small>
            </div>
            
            <div class='form-group'>
                <label>Contraseña:</label>
                <input type='password' name='clave' required 
                       placeholder='Mínimo 6 caracteres'
                       minlength='6' />
            </div>
            
            <div class='form-group'>
                <label>Confirmar Contraseña:</label>
                <input type='password' name='clave_confirm' required 
                       placeholder='Repite la contraseña' />
            </div>
            
            <button type='submit' name='registrar' class='btn btn-success' 
                    style='width:100%; margin-bottom:15px;'>
                Crear Cuenta
            </button>
        </form>
        
        <div style='text-align:center; margin-top:20px;'>
            <p style='color:#666;'>¿Ya tienes cuenta?</p>
            <a href='?opcion=login' class='btn btn-primary'>Iniciar Sesión</a>
        </div>
        
        <div style='margin-top:30px; padding:15px; background:#fff3cd; 
                    border-radius:5px; font-size:14px; border-left:3px solid #ffc107;'>
            <strong> Información:</strong><br/>
            • Usa tu nombre completo como aparece en tu documento<br/>
            • Elige una contraseña segura (mínimo 6 caracteres)<br/>
            • Se creará automáticamente una cuenta de Cliente<br/>
            • Las cuentas de empleados deben ser creadas por el administrador
        </div>
    </div>
    ";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Cierra la sesión del usuario
 */
function fn_logout()
/*--------------------------------------------------------------------*/
{
    session_start();
    session_destroy();
    header("Location: index.php");
    exit();
}

/*------------------------------------------------------------------*/
/**
 * @brief Menú principal para Clientes
 * @param resource $conn Conexión a la base de datos
 * @return string HTML del menú
 */
function fn_menu_Cliente($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $usuario_nombre = $_SESSION['usuario_nombre'];
    
    $retorno = "
    <div style='text-align:center;'>
        <h1> Menú del Cliente</h1>
        <p>Bienvenido, <strong>$usuario_nombre</strong></p>
        
        <div style='margin:30px; display:inline-block;'>
            <a href='?opcion=cliente_ver_menu' class='boton-menu'>
                 Ver Menú del Restaurante
            </a>
            <a href='?opcion=cliente_mis_reservaciones' class='boton-menu'>
                 Mis Reservaciones
            </a>
            <a href='?opcion=cliente_mis_pedidos' class='boton-menu'>
                 Mis Pedidos
            </a>
            <a href='?opcion=cliente_mi_historial' class='boton-menu'>
                 Mi Historial
            </a>
        </div>
        
        <div style='margin-top:20px;'>
            " . fn_boton_cerrar_sesion() . "
        </div>
    </div>
    
    <style>
        .boton-menu {
            display: inline-block;
            margin: 15px;
            padding: 30px 40px;
            background: white;
            color: #333;
            text-decoration: none;
            border: 2px solid #007bff;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s;
            min-width: 250px;
            text-align: center;
        }
        .boton-menu:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
    ";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Menú principal para Meseros
 * @param resource $conn Conexión a la base de datos
 * @return string HTML del menú
 */
function fn_menu_Mesero($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $usuario_nombre = $_SESSION['usuario_nombre'];
    
    $retorno = "
    <div style='text-align:center;'>
        <h1> Menú del Mesero</h1>
        <p>Bienvenido, <strong>$usuario_nombre</strong></p>
        
        <div style='margin:30px; display:inline-block;'>
            <a href='?opcion=mesero_tomar_pedido' class='boton-menu'>
                 Tomar Pedido
            </a>
            <a href='?opcion=mesero_mis_pedidos' class='boton-menu'>
                 Mis Pedidos Activos
            </a>
            <a href='?opcion=mesero_pedidos_listos' class='boton-menu'>
                 Pedidos Listos para Entregar
            </a>
            <a href='?opcion=mesero_entregar_pedido' class='boton-menu'>
                 Entregar Pedido
            </a>
            <a href='?opcion=mesero_ver_menu' class='boton-menu'>
                 Consultar Menú
            </a>
        </div>
        
        <div style='margin-top:20px;'>
            " . fn_boton_cerrar_sesion() . "
        </div>
    </div>
    
    <style>
        .boton-menu {
            display: inline-block;
            margin: 15px;
            padding: 30px 40px;
            background: white;
            color: #333;
            text-decoration: none;
            border: 2px solid #28a745;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s;
            min-width: 250px;
            text-align: center;
        }
        .boton-menu:hover {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
    ";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Menú principal para Cocineros
 * @param resource $conn Conexión a la base de datos
 * @return string HTML del menú
 */
function fn_menu_Cocinero($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $usuario_nombre = $_SESSION['usuario_nombre'];
    
    $retorno = "
    <div style='text-align:center;'>
        <h1> Menú del Cocinero</h1>
        <p>Bienvenido, <strong>$usuario_nombre</strong></p>
        
        <div style='margin:30px; display:inline-block;'>
            <a href='?opcion=cocinero_pedidos_pendientes' class='boton-menu'>
                 Pedidos Pendientes
            </a>
            <a href='?opcion=cocinero_mis_especialidades' class='boton-menu'>
                 Mis Especialidades
            </a>
            <a href='?opcion=cocinero_marcar_listo' class='boton-menu'>
                 Marcar Orden Lista
            </a>
            <a href='?opcion=cocinero_ver_tiempos' class='boton-menu'>
                 Ver Tiempos de Preparación
            </a>
        </div>
        
        <div style='margin-top:20px;'>
            " . fn_boton_cerrar_sesion() . "
        </div>
    </div>
    
    <style>
        .boton-menu {
            display: inline-block;
            margin: 15px;
            padding: 30px 40px;
            background: white;
            color: #333;
            text-decoration: none;
            border: 2px solid #fd7e14;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s;
            min-width: 250px;
            text-align: center;
        }
        .boton-menu:hover {
            background: #fd7e14;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
    ";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Menú principal para Administradores
 * @param resource $conn Conexión a la base de datos
 * @return string HTML del menú
 */
function fn_menu_Administrador($conn)
/*--------------------------------------------------------------------*/
{
    session_start();
    $usuario_nombre = $_SESSION['usuario_nombre'];
    
    $retorno = "
    <div style='text-align:center;'>
        <h1>⚙️ Menú del Administrador</h1>
        <p>Bienvenido, <strong>$usuario_nombre</strong></p>
        
        <div style='margin:30px; display:inline-block;'>
            <a href='?opcion=admin_gestion_mesas' class='boton-menu'>
                 Gestión de Mesas
            </a>
            <a href='?opcion=admin_gestion_menu' class='boton-menu'>
                 Gestión del Menú
            </a>
            <a href='?opcion=admin_gestion_usuarios' class='boton-menu'>
                 Gestión de Usuarios
            </a>
            <a href='?opcion=admin_reportes' class='boton-menu'>
                 Reportes y Estadísticas
            </a>
            <a href='?opcion=admin_reservaciones' class='boton-menu'>
                 Ver Reservaciones
            </a>
            <a href='?opcion=admin_pedidos' class='boton-menu'>
                 Ver Todos los Pedidos
            </a>
        </div>
        
        <div style='margin-top:20px;'>
            " . fn_boton_cerrar_sesion() . "
        </div>
    </div>
    
    <style>
        .boton-menu {
            display: inline-block;
            margin: 15px;
            padding: 30px 40px;
            background: white;
            color: #333;
            text-decoration: none;
            border: 2px solid #6c757d;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s;
            min-width: 250px;
            text-align: center;
        }
        .boton-menu:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
    ";
    
    return $retorno;
}
?>