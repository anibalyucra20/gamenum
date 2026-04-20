<?php
require_once 'config.php';
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- LÓGICA DE ELIMINACIÓN ---
    if (isset($_GET['eliminar'])) {
        $stmt_imgs = $pdo->prepare("SELECT imagen_enunciado, imagen_ayuda FROM preguntas WHERE id = ?");
        $stmt_imgs->execute([$_GET['eliminar']]);
        $imgs = $stmt_imgs->fetch(PDO::FETCH_ASSOC);
        
        // Borrar imagen del enunciado si existe
        if ($imgs['imagen_enunciado'] && file_exists("uploads/" . $imgs['imagen_enunciado'])) {
            unlink("uploads/" . $imgs['imagen_enunciado']);
        }
        // Borrar imagen de ayuda si existe
        if ($imgs['imagen_ayuda'] && file_exists("uploads/" . $imgs['imagen_ayuda'])) {
            unlink("uploads/" . $imgs['imagen_ayuda']);
        }

        $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id = ?");
        $stmt->execute([$_GET['eliminar']]);
        header("Location: admin.php?msg=Eliminado");
        exit;
    }

    // --- LÓGICA DE CARGA PARA EDICIÓN ---
    $edit_data = null;
    if (isset($_GET['editar'])) {
        $stmt_edit = $pdo->prepare("SELECT * FROM preguntas WHERE id = ?");
        $stmt_edit->execute([$_GET['editar']]);
        $edit_data = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    }

    // --- FILTRO Y LISTADO ---
    $nivel_filtro = isset($_GET['filtro_nivel']) ? (int)$_GET['filtro_nivel'] : 0;
    $query = "SELECT * FROM preguntas";
    if ($nivel_filtro > 0) $query .= " WHERE tipo = $nivel_filtro";
    $query .= " ORDER BY tipo ASC, id DESC";
    $preguntas = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo - Desafío Matemático</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-4 md:p-10">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-800 border-l-4 border-blue-600 pl-4">Gestión de Preguntas</h1>
            <div class="flex gap-2">
                <?php if($edit_data): ?>
                    <a href="admin.php" class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition shadow-md font-bold text-sm">NUEVA PREGUNTA</a>
                <?php endif; ?>
                <a href="index.php" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition shadow-md font-bold text-sm">← Volver al Juego</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 h-fit sticky top-4">
                <h2 class="text-xl font-bold mb-6 text-blue-600 flex items-center gap-2">
                    <span class="bg-blue-100 p-2 rounded-lg"><?= $edit_data ? '📝' : '➕' ?></span> 
                    <?= $edit_data ? 'Editar Pregunta' : 'Nueva Pregunta' ?>
                </h2>
                
                <form action="procesar_pregunta.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Configuración</label>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="tipo" class="w-full border-2 p-2 rounded-lg focus:border-blue-500 outline-none transition" required>
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <option value="<?=$i?>" <?= ($edit_data && $edit_data['tipo'] == $i) ? 'selected' : '' ?>>Nivel <?=$i?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="formato" id="formato" onchange="toggleOpciones()" class="w-full border-2 p-2 rounded-lg focus:border-blue-500 outline-none transition">
                                <option value="multiple" <?= ($edit_data && $edit_data['formato'] == 'multiple') ? 'selected' : '' ?>>Múltiple</option>
                                <option value="abierta" <?= ($edit_data && $edit_data['formato'] == 'abierta') ? 'selected' : '' ?>>Abierta</option>
                                <option value="relacionar" <?= ($edit_data && $edit_data['formato'] == 'relacionar') ? 'selected' : '' ?>>Relacionar</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Enunciado (Texto Opcional)</label>
                        <textarea name="enunciado" class="w-full border-2 p-2 rounded-lg focus:border-blue-500 outline-none transition" rows="2" placeholder="Instrucciones de la pregunta..."><?= $edit_data ? htmlspecialchars($edit_data['enunciado']) : '' ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-blue-600 mb-1">Imagen del Enunciado</label>
                        <?php if($edit_data && $edit_data['imagen_enunciado']): ?>
                            <div class="mb-2 p-2 border rounded bg-gray-50 text-center">
                                <img src="uploads/<?= $edit_data['imagen_enunciado'] ?>" class="h-20 mx-auto rounded">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagen_enunciado" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 transition-colors">
                    </div>

                    <div id="seccion-respuestas" class="space-y-3 pt-2 border-t">
                        <p id="hint-relacionar" class="hidden text-[10px] text-blue-500 font-bold bg-blue-50 p-2 rounded">
                            ⚠️ MODO RELACIONAR: Escribe en cada campo "Concepto | Respuesta". <br>Ejemplo: "2 + 2 | 4"
                        </p>

                        <div>
                            <label id="label-correcta" class="block text-xs font-bold uppercase text-green-600 mb-1">Respuesta Correcta</label>
                            <input type="text" name="respuesta_correcta" value="<?= $edit_data ? htmlspecialchars($edit_data['respuesta_correcta']) : '' ?>" class="w-full border-2 border-green-200 p-2 rounded-lg focus:border-green-500 outline-none transition" required>
                        </div>
                        
                        <div class="opciones-multiples space-y-3" style="<?= ($edit_data && $edit_data['formato'] == 'abierta') ? 'display:none' : '' ?>">
                            <label id="label-incorrectas" class="block text-xs font-bold uppercase text-red-400 mb-1">Opciones Incorrectas</label>
                            <?php 
                                $opts = ['b', 'c', 'd', 'e'];
                                foreach($opts as $opt): 
                                    $val = $edit_data ? htmlspecialchars($edit_data['opcion_'.$opt]) : '';
                            ?>
                                <input type="text" name="opcion_<?=$opt?>" value="<?=$val?>" placeholder="Opción <?=strtoupper($opt)?>" class="w-full border-2 p-2 rounded-lg focus:border-blue-400 outline-none transition req-mult">
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-2 border-t">
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Imagen de Ayuda (Fórmula)</label>
                        <?php if($edit_data && $edit_data['imagen_ayuda']): ?>
                            <div class="mb-2 p-2 border rounded bg-gray-50 text-center">
                                <img src="uploads/<?= $edit_data['imagen_ayuda'] ?>" class="h-16 mx-auto rounded">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagen_ayuda" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Pista en texto</label>
                        <input type="text" name="formula_ayuda" value="<?= $edit_data ? htmlspecialchars($edit_data['formula_ayuda']) : '' ?>" class="w-full border-2 p-2 rounded-lg focus:border-blue-500 outline-none" placeholder="Ej: Es un número natural">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">ID YouTube</label>
                        <input type="text" name="url_youtube" value="<?= $edit_data ? htmlspecialchars($edit_data['url_youtube']) : '' ?>" class="w-full border-2 p-2 rounded-lg focus:border-blue-500 outline-none" placeholder="Ej: 7m9-7iT3u-Y">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg transition transform hover:-translate-y-0.5">
                            <?= $edit_data ? 'ACTUALIZAR DATOS' : 'GUARDAR PREGUNTA' ?>
                        </button>
                        <?php if($edit_data): ?>
                            <a href="admin.php" class="bg-gray-200 text-gray-700 px-5 py-3 rounded-xl font-bold hover:bg-gray-300 transition text-center flex items-center justify-center">X</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-xl border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase tracking-wider">
                                <th class="px-4 py-2">Nivel</th>
                                <th class="px-4 py-2">Formato</th>
                                <th class="px-4 py-2">Enunciado</th>
                                <th class="px-4 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preguntas as $p): ?>
                                <tr class="bg-gray-50 hover:bg-blue-50 transition-colors shadow-sm">
                                    <td class="px-4 py-3 rounded-l-xl border-y border-l">
                                        <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase">N<?= $p['tipo'] ?></span>
                                    </td>
                                    <td class="px-4 py-3 border-y text-[10px] font-bold uppercase text-gray-500">
                                        <?= $p['formato'] ?>
                                    </td>
                                    <td class="px-4 py-3 border-y text-sm">
                                        <div class="flex items-center gap-2">
                                            <?php if($p['imagen_enunciado']): ?>
                                                <img src="uploads/<?= $p['imagen_enunciado'] ?>" class="h-10 w-14 object-cover rounded border bg-white shadow-xs">
                                            <?php endif; ?>
                                            <p class="font-medium text-gray-800 truncate max-w-[180px]"><?= htmlspecialchars($p['enunciado'] ?: 'Pregunta con imagen') ?></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 border-y border-r rounded-r-xl text-center space-x-1">
                                        <a href="?editar=<?= $p['id'] ?>" class="bg-blue-100 text-blue-600 p-2 rounded-lg hover:bg-blue-600 hover:text-white transition inline-block shadow-sm">✏️</a>
                                        <a href="?eliminar=<?= $p['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar esta pregunta?')" class="bg-red-100 text-red-600 p-2 rounded-lg hover:bg-red-600 hover:text-white transition inline-block shadow-sm">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(empty($preguntas)): ?>
                        <p class="text-center text-gray-400 py-10 italic">No hay preguntas registradas todavía.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleOpciones() {
            const formato = document.getElementById('formato').value;
            const seccionMulti = document.querySelector('.opciones-multiples');
            const inputsMulti = document.querySelectorAll('.req-mult');
            const labelCorrecta = document.getElementById('label-correcta');
            const labelIncorrectas = document.getElementById('label-incorrectas');
            const hintRelacionar = document.getElementById('hint-relacionar');

            // Reset de etiquetas y placeholders
            labelCorrecta.innerText = "Respuesta Correcta";
            labelIncorrectas.innerText = "Opciones Incorrectas";
            hintRelacionar.classList.add('hidden');

            if(formato === 'abierta') {
                seccionMulti.style.display = 'none';
                inputsMulti.forEach(i => {
                    i.required = false;
                    i.placeholder = "Opción";
                });
            } 
            else if(formato === 'relacionar') {
                seccionMulti.style.display = 'block';
                labelCorrecta.innerText = "Pareja 1 (Concepto | Respuesta)";
                labelIncorrectas.innerText = "Parejas Adicionales";
                hintRelacionar.classList.remove('hidden');
                
                inputsMulti.forEach((i, index) => {
                    i.required = false; // No todas las parejas son obligatorias
                    i.placeholder = `Pareja ${index + 2} (Concepto | Respuesta)`;
                });
            }
            else { // Múltiple
                seccionMulti.style.display = 'block';
                inputsMulti.forEach(i => {
                    i.required = true;
                    i.placeholder = "Opción Incorrecta";
                });
            }
        }
        window.onload = toggleOpciones;
    </script>
</body>
</html>
<?php } catch (PDOException $e) { die("Error de conexión: " . $e->getMessage()); } ?>