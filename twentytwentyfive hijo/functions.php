<?php

function enqueue_styles_child_theme() {

	$parent_style = 'parent-style';
	$child_style  = 'child-style';

	wp_enqueue_style( $parent_style,
				get_template_directory_uri() . '/style.css' );

	wp_enqueue_style( $child_style,
				get_stylesheet_directory_uri() . '/style.css',
				array( $parent_style ),
				wp_get_theme()->get('Version')
				);
}
add_action( 'wp_enqueue_scripts', 'enqueue_styles_child_theme' );



function guardar_datos_registro() {
    if (isset($_POST['registrar'])) {
        $nombre = sanitize_text_field($_POST['nombre']);
        $genero = sanitize_text_field($_POST['genero']);
        $edad = intval($_POST['edad']);
        $email = sanitize_email($_POST['email']);
        $telefono = sanitize_text_field($_POST['telefono']);

        $db_host = 'localhost';
        $db_usuario = 'root'; 
        $db_contrasena = ''; 
        $db_nombre = 'usuarios_wordpress';

        $conexion = mysqli_connect($db_host, $db_usuario, $db_contrasena, $db_nombre);

        if (!$conexion) {
            die("Error al conectar a la base de datos: " . mysqli_connect_error());
        }

        $consulta_existente = "SELECT mail FROM usuarios WHERE mail = ?";
        $stmt_existente = mysqli_prepare($conexion, $consulta_existente);

        if ($stmt_existente) {
            mysqli_stmt_bind_param($stmt_existente, "s", $email);
            mysqli_stmt_execute($stmt_existente);
            mysqli_stmt_store_result($stmt_existente);

            if (mysqli_stmt_num_rows($stmt_existente) > 0) {
                echo '<p class="error">Este correo electrónico ya está registrado.</p>';
                mysqli_stmt_close($stmt_existente);
                mysqli_close($conexion);
                return;
            }
            mysqli_stmt_close($stmt_existente);
        } else {
            mysqli_close($conexion);
            return;
        }

        $consulta = "INSERT INTO usuarios (nombre, genero, edad, mail, telefono) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $consulta);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssiss", $nombre, $genero, $edad, $email, $telefono);

            if (mysqli_stmt_execute($stmt)) {
                echo '<p class="exito">Registro exitoso.</p>'; 
            } else {
                echo '<p class="error">Error al registrar los datos: ' . mysqli_stmt_error($stmt) . '</p>';
            }

            mysqli_stmt_close($stmt);
        } else {
            echo '<p class="error">Error al preparar la consulta de inserción: ' . mysqli_error($conexion) . '</p>';
        }

        mysqli_close($conexion);
    }
}
add_action('wp_head', 'guardar_datos_registro');



function listar_usuarios_shortcode() {
    ob_start();

    $otro_db = new wpdb("root", "", 'usuarios_wordpress', "localhost");

    if (!empty($otro_db->error)) {
        echo '<div class="error-message">Error de conexión a la base de datos: ' . esc_html($otro_db->error) . '</div>';
    } else {
        $usuarios = $otro_db->get_results("SELECT nombre, genero, edad, mail, telefono FROM usuarios");

        if (!empty($usuarios)) {
            echo '<h3 class="user-list-title">Lista de usuarios registrados:</h3>';
            
            // Tabla para mostrar los datos
            echo '<table class="user-table">';
            echo '<thead><tr><th>Nombre</th><th>Género</th><th>Edad</th><th>Email</th><th>Teléfono</th></tr></thead>';
            echo '<tbody>';
            foreach ($usuarios as $usuario) {
                echo '<tr>';
                echo '<td>' . esc_html($usuario->nombre) . '</td>';
                echo '<td>' . esc_html($usuario->genero) . '</td>';
                echo '<td>' . esc_html($usuario->edad) . '</td>';
                echo '<td>' . esc_html($usuario->mail) . '</td>';
                echo '<td>' . esc_html($usuario->telefono) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="no-users-message">No hay usuarios registrados aún.</div>';
        }

        echo '<a href="http://localhost/wordpress/registro/" class="registro-button">Ir al registro</a>';
    }

    return ob_get_clean();
}
add_shortcode('listar_usuarios', 'listar_usuarios_shortcode');


