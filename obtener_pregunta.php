<?php
require_once 'config.php';

$nivel = isset($_GET['nivel']) ? (int)$_GET['nivel'] : 1;

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    // obtener_pregunta.php
    $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE tipo = ? ORDER BY RAND() LIMIT 1");
    $stmt->execute([$_GET['nivel']]);
    $pregunta = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($pregunta);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
