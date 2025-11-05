<?php
function ensure_vianda_utils(PDO $pdo)
{
    // Crea la tabla 'ultimo_pedido' si no existe
    $sql = "
        CREATE TABLE IF NOT EXISTS ultimo_pedido (
            user_id INTEGER PRIMARY KEY,
            ultimo TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ultimo_pedido_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ";
    $pdo->exec($sql);
}

function get_user_id_by_dni(PDO $pdo, string $dni): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE dni = :dni LIMIT 1");
    $stmt->execute(['dni' => $dni]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

function tiene_pedido_ult_12h(PDO $pdo, int $user_id): bool
{
    $stmt = $pdo->prepare("
        SELECT 1 FROM viandas
        WHERE user_id = :uid AND fecha >= NOW() - INTERVAL '12 hours'
        LIMIT 1
    ");
    $stmt->execute(['uid' => $user_id]);
    return (bool)$stmt->fetchColumn();
}

function en_cooldown_12h(PDO $pdo, int $user_id): bool
{
    $stmt = $pdo->prepare("
        SELECT ultimo FROM ultimo_pedido
        WHERE user_id = :uid LIMIT 1
    ");
    $stmt->execute(['uid' => $user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $ultimo = strtotime($row['ultimo']);
        $ahora = time();
        return ($ahora < $ultimo + 12 * 3600); // 12 horas en segundos
    }
    return false;
}

function registrar_ultimo_pedido(PDO $pdo, int $user_id): void
{
    // PostgreSQL no tiene ON DUPLICATE KEY UPDATE, usamos UPSERT con ON CONFLICT
    $stmt = $pdo->prepare("
        INSERT INTO ultimo_pedido (user_id, ultimo)
        VALUES (:uid, NOW())
        ON CONFLICT (user_id) DO UPDATE SET ultimo = NOW()
    ");
    $stmt->execute(['uid' => $user_id]);
}
?>
