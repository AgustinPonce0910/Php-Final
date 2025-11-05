<?php
$host = getenv('DB_HOST') ?: 'dpg-d42ih2er433s73dmgpb0-a.oregon-postgres.render.com';
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_NAME') ?: 'login_system_ujfj';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'fMVJXDa6hDB3mFkbJe3GIXHW63eQxJru';

$dsn = "pgsql:host={$host};port={$port};dbname={$db};";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}
?>
