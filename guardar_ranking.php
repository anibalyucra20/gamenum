<?php
require_once 'config.php';
// Solo procesar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : 'Anónimo';
    $puntuacion = isset($_POST['puntos']) ? (int)$_POST['puntos'] : 0;
    $tiempo = isset($_POST['tiempo']) ? (int)$_POST['tiempo'] : 0;

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO ranking (nickname, puntuacion, tiempo_segundos) VALUES (:nick, :pts, :time)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':nick' => $nickname,
            ':pts'  => $puntuacion,
            ':time' => $tiempo
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Ranking actualizado']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}