<?php
session_start();

if (!isset($_SESSION['acceso_panel']) || $_SESSION['acceso_panel'] !== true) {
    header("Location: clave_panel.php");
    exit();
}

require 'db.php';

$busqueda_nombre = $_GET['nombre'] ?? '';
$busqueda_fecha = $_GET['fecha'] ?? '';

$params = [];
$sql = "SELECT u.nombre, u.dni, v.fecha, v.tipo, COUNT(*) as cantidad
        FROM viandas v
        JOIN users u ON v.user_id = u.id
        WHERE 1=1";

if (!empty($busqueda_nombre)) {
    $sql .= " AND u.nombre ILIKE :nombre"; // ILIKE para búsqueda case-insensitive en PostgreSQL
    $params['nombre'] = "%$busqueda_nombre%";
}

if (!empty($busqueda_fecha)) {
    $sql .= " AND DATE(v.fecha) = :fecha";
    $params['fecha'] = $busqueda_fecha;
}

$sql .= " GROUP BY u.id, DATE(v.fecha), v.tipo
          ORDER BY v.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resumen_diario = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $fecha = date('Y-m-d', strtotime($row['fecha']));
    $resumen_diario[$fecha][] = [
        'nombre' => $row['nombre'],
        'dni' => $row['dni'],
        'tipo' => ucfirst($row['tipo']),
        'cantidad' => $row['cantidad'],
        'costo' => $row['cantidad'] * 1500
    ];
}

// Resumen mensual
$sql_resumen_mensual = "
    SELECT u.nombre, u.dni, EXTRACT(MONTH FROM v.fecha) AS mes, EXTRACT(YEAR FROM v.fecha) AS anio, COUNT(*) AS total_viandas
    FROM viandas v
    JOIN users u ON v.user_id = u.id
    GROUP BY u.id, anio, mes
    ORDER BY anio DESC, mes DESC
";

$stmt_mensual = $pdo->query($sql_resumen_mensual);
$resumen_mensual = [];

while ($row = $stmt_mensual->fetch(PDO::FETCH_ASSOC)) {
    $resumen_mensual[] = [
        'nombre' => $row['nombre'],
        'dni' => $row['dni'],
        'mes' => $row['mes'],
        'anio' => $row['anio'],
        'cantidad' => $row['total_viandas'],
        'total' => $row['total_viandas'] * 1500
    ];
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Encargado - Compañía de Ingenieros QBN 601</title>
    <link rel="stylesheet" href="style.css">
</head>
<!-- Added bg-helicopters class for military helicopter background -->

<body class="bg-soldier">
    <!-- Updated header with military styling -->

    <header>
        <a href="index.php">
            <img src="img/fondurri.png" class="military-header">
        </a>
        <div class="header-content">
            <div class="unit-info">
                <h1 class="titulo-principal">

                    <div class="nav-buttons">
                        <a href="index.php" class="btn btn-primary">VOLVER AL INICIO</a>
                        <a href="logout.php" class="btn btn-destructive">CERRAR SESIÓN</a>
                    </div>
                </h1>
            </div>
            <div class="nav-buttons">
    </header>


    <main class="main-content">
        <!-- Updated filters with military card styling -->
        <div class="card">
            <div class="card-header">
                <h3>FILTROS DE BÚSQUEDA</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre" class="form-label">Buscar por nombre:</label>
                            <input type="text" id="nombre" name="nombre" class="form-input"
                                value="<?= htmlspecialchars($busqueda_nombre) ?>" placeholder="Nombre del usuario">
                        </div>

                        <div class="form-group">
                            <label for="fecha" class="form-label">Filtrar por fecha:</label>
                            <input type="date" id="fecha" name="fecha" class="form-input"
                                value="<?= htmlspecialchars($busqueda_fecha) ?>">
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">BUSCAR</button>
                        <a href="panel.php" class="btn btn-secondary">REINICIAR</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Updated daily reports with military table styling -->
        <?php if (empty($resumen_diario)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <p class="no-data">No se encontraron viandas para los filtros aplicados.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($resumen_diario as $fecha => $viandas): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>REPORTE DIARIO - <?= strtoupper(date('d/m/Y', strtotime($fecha))) ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="military-table">
                                <thead>
                                    <tr>
                                        <th>NOMBRE</th>
                                        <th>DNI</th>
                                        <th>TIPO DE VIANDA</th>
                                        <th>CANTIDAD</th>
                                        <th>TOTAL A DESCONTAR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_del_dia = 0;
                                    foreach ($viandas as $v):
                                        $total_del_dia += $v['costo'];
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($v['nombre']) ?></td>
                                            <td><?= htmlspecialchars($v['dni']) ?></td>
                                            <td><?= $v['tipo'] ?></td>
                                            <td><?= $v['cantidad'] ?></td>
                                            <td class="amount">$<?= number_format($v['costo'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="4"><strong>TOTAL DEL DÍA:</strong></td>
                                        <td class="amount"><strong>$<?= number_format($total_del_dia, 2, ',', '.') ?></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Updated monthly summary with military table styling -->
        <div class="card">
            <div class="card-header">
                <h3>RESUMEN MENSUAL POR USUARIO</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="military-table">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>DNI</th>
                                <th>MES</th>
                                <th>AÑO</th>
                                <th>CANTIDAD DE VIANDAS</th>
                                <th>TOTAL DESCONTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen_mensual as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><?= htmlspecialchars($item['dni']) ?></td>
                                    <td><?= $item['mes'] ?></td>
                                    <td><?= $item['anio'] ?></td>
                                    <td><?= $item['cantidad'] ?></td>
                                    <td class="amount">$<?= number_format($item['total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>