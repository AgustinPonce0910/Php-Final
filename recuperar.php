<?php
session_start();
require_once 'db.php'; // debe definir $pdo (PDO)

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');

    if ($nombre !== '' && $apellido !== '') {
        try {
            // Consulta compatible con PostgreSQL; usamos ILIKE para evitar LOWER(...) si preferís
            $sql = "SELECT dni, nombre, apellido FROM users WHERE LOWER(nombre) = LOWER(:nombre) AND LOWER(apellido) = LOWER(:apellido) LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $mensaje = "Usuario encontrado: " . htmlspecialchars($user['nombre']) . " " . htmlspecialchars($user['apellido']) . " - DNI: " . htmlspecialchars($user['dni']);
            } else {
                $error = "No se encontró ningún usuario con ese nombre y apellido.";
            }
        } catch (Exception $e) {
            error_log("Error en recuperar.php: " . $e->getMessage());
            $error = "Ocurrió un error al buscar el usuario. Intentá nuevamente.";
        }
    } else {
        $error = "Por favor, complete todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Usuario - Compañía de Ingenieros QBN 601</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-soldier">
    <header>
        <a href="index.php">
            <img src="img/fondurri.png" class="military-header">
        </a>
        <div class="header-content">
            <div class="unit-info">


            </div>
            <div class="nav-buttons">
    </header>

    <main class="main-content">
        <div class="auth-container">
            <div class="card">
                <div class="card-header">
                    <h3>RECUPERAR DATOS DE USUARIO</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-success">
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="nombre" class="form-label">NOMBRE:</label>
                            <input type="text" id="nombre" name="nombre" class="form-input"
                                placeholder="Ingrese su nombre" required>
                        </div>

                        <div class="form-group">
                            <label for="apellido" class="form-label">APELLIDO:</label>
                            <input type="text" id="apellido" name="apellido" class="form-input"
                                placeholder="Ingrese su apellido" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">BUSCAR USUARIO</button>
                    </form>

                    <div class="auth-links">
                        <a href="login.php" class="btn btn-link">Volver al login</a>
                        <a href="register.php" class="btn btn-link">Registrarse</a>
                        <a href="index.php" class="btn btn-link">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>