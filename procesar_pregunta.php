<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        
        // Preparar nombres de imágenes actuales (para edición)
        $nombre_imagen_enunciado = null;
        $nombre_imagen_ayuda = null;

        if ($id) {
            $stmt = $pdo->prepare("SELECT imagen_enunciado, imagen_ayuda FROM preguntas WHERE id = ?");
            $stmt->execute([$id]);
            $current_imgs = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_imagen_enunciado = $current_imgs['imagen_enunciado'];
            $nombre_imagen_ayuda = $current_imgs['imagen_ayuda'];
        }

        if (!file_exists('uploads')) mkdir('uploads', 0777, true);

        // --- MANEJO IMAGEN ENUNCIADO ---
        if (isset($_FILES['imagen_enunciado']) && $_FILES['imagen_enunciado']['error'] === 0) {
            // Borrar antigua si existe
            if ($id && $nombre_imagen_enunciado && file_exists("uploads/" . $nombre_imagen_enunciado)) {
                unlink("uploads/" . $nombre_imagen_enunciado);
            }
            $ext = pathinfo($_FILES['imagen_enunciado']['name'], PATHINFO_EXTENSION);
            $nombre_imagen_enunciado = "q_" . time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES['imagen_enunciado']['tmp_name'], "uploads/" . $nombre_imagen_enunciado);
        }

        // --- MANEJO IMAGEN AYUDA ---
        if (isset($_FILES['imagen_ayuda']) && $_FILES['imagen_ayuda']['error'] === 0) {
            // Borrar antigua si existe
            if ($id && $nombre_imagen_ayuda && file_exists("uploads/" . $nombre_imagen_ayuda)) {
                unlink("uploads/" . $nombre_imagen_ayuda);
            }
            $ext = pathinfo($_FILES['imagen_ayuda']['name'], PATHINFO_EXTENSION);
            $nombre_imagen_ayuda = "h_" . time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES['imagen_ayuda']['tmp_name'], "uploads/" . $nombre_imagen_ayuda);
        }

        // Lógica de SQL
        if ($id) {
            // ACTUALIZAR (UPDATE)
            $sql = "UPDATE preguntas SET 
                    tipo = ?, formato = ?, enunciado = ?, imagen_enunciado = ?, respuesta_correcta = ?, 
                    opcion_b = ?, opcion_c = ?, opcion_d = ?, opcion_e = ?, 
                    formula_ayuda = ?, url_youtube = ?, imagen_ayuda = ? 
                    WHERE id = ?";
            
            $params = [
                $_POST['tipo'], $_POST['formato'], $_POST['enunciado'], $nombre_imagen_enunciado, $_POST['respuesta_correcta'],
                $_POST['opcion_b'] ?? null, $_POST['opcion_c'] ?? null, $_POST['opcion_d'] ?? null, $_POST['opcion_e'] ?? null,
                $_POST['formula_ayuda'], $_POST['url_youtube'], $nombre_imagen_ayuda, $id
            ];
        } else {
            // INSERTAR (NUEVO)
            $sql = "INSERT INTO preguntas (tipo, formato, enunciado, imagen_enunciado, respuesta_correcta, opcion_b, opcion_c, opcion_d, opcion_e, formula_ayuda, url_youtube, imagen_ayuda) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $_POST['tipo'], $_POST['formato'], $_POST['enunciado'], $nombre_imagen_enunciado, $_POST['respuesta_correcta'],
                $_POST['opcion_b'] ?? null, $_POST['opcion_c'] ?? null, $_POST['opcion_d'] ?? null, $_POST['opcion_e'] ?? null,
                $_POST['formula_ayuda'], $_POST['url_youtube'], $nombre_imagen_ayuda
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: admin.php?msg=exito");
        exit;

    } catch (PDOException $e) {
        die("Error procesando la solicitud: " . $e->getMessage());
    }
}