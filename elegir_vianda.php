<?php
// elegir_vianda.php - corregido para PostgreSQL (PDO)
// Debe SER el primer contenido del archivo: no dejar espacios ni líneas antes de <?php

session_start();

require_once 'db.php';           // Debe definir $pdo (PDO) o $conn (PDO)
// require_once 'utils_viandas.php' debe contener las funciones adaptadas a PDO
require_once 'utils_viandas.php';

// Aceptar $pdo o $conn (compatibilidad)
if (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
} elseif (isset($conn) && $conn instanceof PDO) {
    $db = $conn;
} else {
    // Si no hay PDO disponible, abortar con mensaje claro en logs y redirigir
    error_log('No PDO connection found in db.php');
    $_SESSION['alerta_error'] = 'Error de conexión a la base de datos.';
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['dni'])) {
    header("Location: login.php");
    exit();
}

// Asegurarse de utilidades (crea tabla ultimo_pedido si hace falta)
ensure_vianda_utils($db);

$dni = $_SESSION['dni'];
$viandas = $_POST['vianda'] ?? [];

if (!empty($viandas)) {
    $user_id = get_user_id_by_dni($db, $dni);
    if ($user_id) {

        // Bloqueo por 12 horas si ya pidió recientemente (aunque haya cancelado)
        if (en_cooldown_12h($db, $user_id) || tiene_pedido_ult_12h($db, $user_id)) {
            $_SESSION['alerta_error'] = "Ya realizaste un pedido recientemente. Solo podés hacer otro pasadas 12 horas.";
            header("Location: index.php");
            exit();
        }

        try {
            // Empezar transacción para insertar todas las viandas
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO viandas (user_id, tipo) VALUES (:uid, :tipo)");
            foreach ($viandas as $tipo) {
                $tipo_clean = trim((string)$tipo);
                $stmt->execute([
                    'uid' => $user_id,
                    'tipo' => $tipo_clean
                ]);
            }

            // Registrar cooldown (upsert)
            registrar_ultimo_pedido($db, $user_id);

            $db->commit();

            $_SESSION['alerta_ok'] = "Pedido enviado con éxito ✔️";
            header("Location: index.php?success=1");
            exit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al insertar viandas: ' . $e->getMessage());
            $_SESSION['alerta_error'] = "Error al procesar el pedido. Intentá nuevamente.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['alerta_error'] = "Usuario no encontrado.";
        header("Location: index.php");
        exit();
    }
}

// Si no envió viandas, mostrar la página con el nav (o redirigir)
?>
<nav>
    <ul style="list-style: none; display: flex; justify-content: flex-end; gap: 15px; margin: 0; padding: 10px; background: #333;">
        <li><a href="panel.php" style="color: white; text-decoration: none;">Panel</a></li>
        <li><a href="elegir_vianda.php" style="color: white; text-decoration: none;">Elegir Vianda</a></li>
        <li><a href="cancelar_vianda.php" style="color: white; text-decoration: none;">Cancelar Vianda</a></li>
        <li><a href="logout.php" style="color: white; text-decoration: none; font-weight: bold;">Cerrar Sesión</a></li>
    </ul>
</nav>

<!-- Botón de Cerrar Sesión en la esquina superior derecha -->
<div style="position: absolute; top: 10px; right: 10px;">
    <form action="logout.php" method="post">
        <button type="submit" style="
            background-color: #e74c3c; 
            color: white; 
            border: none; 
            padding: 10px 15px; 
            border-radius: 5px; 
            cursor: pointer;
        ">
            Cerrar Sesión
        </button>
    </form>
</div>