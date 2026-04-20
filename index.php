<?php
require_once 'config.php';
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Desafío Matemático</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            .modal {
                display: none;
                position: fixed;
                z-index: 50;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
            }
            .btn-relacionar {
                transition: all 0.3s ease;
            }
            .btn-relacionar.seleccionado {
                border-color: #2563eb;
                background-color: #dbeafe;
                transform: scale(1.05);
            }
            .btn-relacionar.completado {
                background-color: #d1fae5;
                border-color: #10b981;
                color: #065f46;
                cursor: default;
                pointer-events: none;
            }
        </style>
    </head>

    <body class="bg-gray-100 min-h-screen font-sans">

        <div id="pantalla-inicio" class="container mx-auto max-w-xl py-10 px-4">
            <h1 class="text-4xl font-bold text-center text-blue-600 mb-8 text-shadow">Desafío Matemático</h1>

            <div class="bg-white p-6 rounded-lg shadow-lg mb-8 border-b-4 border-blue-500">
                <input type="text" id="nickname" placeholder="Tu Nombre" class="w-full border p-3 rounded mb-4 focus:ring-2 focus:ring-blue-400 outline-none transition-all">
                <button onclick="empezarJuego()" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition shadow-lg transform hover:scale-[1.01]">INICIAR JUEGO</button>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4 text-gray-700 border-b pb-2 text-center">🏆 Top 10 Mejores Puntuaciones</h2>
                <div id="ranking-container" class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-500 text-sm border-b">
                                <th class="py-2">Pos</th>
                                <th>Usuario</th>
                                <th>Pts</th>
                                <th>Tiempo</th>
                            </tr>
                        </thead>
                        <tbody id="lista-ranking">
                            <?php
                            $stmt = $pdo->query("SELECT nickname, puntuacion, tiempo_segundos FROM ranking ORDER BY puntuacion DESC, tiempo_segundos ASC LIMIT 10");
                            $pos = 1;
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $estiloPos = ($pos <= 3) ? "font-bold text-blue-600" : "text-gray-600";
                                echo "<tr class='border-b hover:bg-gray-50 transition-colors'>
                                <td class='py-2 {$estiloPos}'>{$pos}</td>
                                <td class='font-medium text-gray-800'>" . htmlspecialchars($row['nickname']) . "</td>
                                <td class='text-blue-600 font-bold'>{$row['puntuacion']}</td>
                                <td class='text-gray-400 text-sm'>{$row['tiempo_segundos']}s</td>
                            </tr>";
                                $pos++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="pantalla-juego" class="hidden container mx-auto max-w-2xl py-10 px-4">
            <div class="flex justify-between items-center mb-6 bg-blue-600 text-white p-4 rounded-lg shadow-md border-b-4 border-blue-800">
                <div>Nombre: <span id="display-nick" class="font-bold text-yellow-300"></span></div>
                <div class="text-2xl font-mono">⏳ <span id="cronometro">00:00</span></div>
                <div>Puntos: <span id="display-puntos" class="font-bold">0</span></div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-2xl border-t-4 border-blue-500">
                <div id="pregunta-header" class="text-sm font-bold text-blue-500 mb-2 uppercase tracking-widest text-center">Cargando...</div>

                <div id="contenedor-enunciado" class="mb-8 text-center space-y-4">
                    <p id="enunciado-texto" class="text-2xl text-gray-800 font-medium leading-relaxed hidden"></p>
                    <img id="enunciado-imagen" src="" alt="Gráfico de la pregunta" class="hidden max-w-full h-auto mx-auto rounded border shadow-inner bg-white p-2">
                </div>

                <div id="contenedor-respuestas" class="grid grid-cols-1 gap-4 mb-6 transition-all duration-300">
                </div>

                <div id="seccion-ayuda" class="hidden mt-6 p-5 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <p class="text-red-700 font-bold mb-2 flex items-center gap-2">
                        <span>❌</span> ¡Respuesta Incorrecta!
                    </p>
                    <p id="formula-texto" class="text-gray-700 italic mb-4"></p>
                    <button onclick="mostrarVideo()" class="w-full bg-red-600 text-white px-4 py-3 rounded-lg text-sm flex items-center justify-center gap-2 hover:bg-red-700 transition font-bold shadow-md mb-4">
                        Ver Tutorial en YouTube 🎥
                    </button>
                    <div id="contenedor-imagen-ayuda" class="hidden bg-white p-2 rounded border shadow-inner">
                        <p class="text-xs text-gray-400 mb-1 font-bold">RECURSO VISUAL:</p>
                        <img id="formula-imagen" src="" alt="Imagen de ayuda" class="max-w-full h-auto mx-auto rounded">
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-video" class="modal flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-lg p-2 max-w-3xl w-full relative shadow-2xl border-2 border-gray-300">
                <button onclick="cerrarModal()" class="absolute -top-10 right-0 text-white text-3xl font-bold hover:text-red-400 transition">&times; Cerrar</button>
                <div class="aspect-video">
                    <iframe id="iframe-video" class="w-full h-full rounded" src="" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
        </div>

        <script>
            let segundos = 0;
            let cronoInterval;
            let puntos = 0;
            let preguntaActual = 1;
            let preguntaActualData = null;
            let haFalladoActual = false;

            let itemSeleccionadoIzq = null;
            let parejasResueltas = 0;
            let totalParejas = 0;

            function empezarJuego() {
                const nick = document.getElementById('nickname').value.trim();
                if (!nick) {
                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor, ingresa un nombre para comenzar.', confirmButtonColor: '#2563eb' });
                    return;
                }
                document.getElementById('display-nick').innerText = nick;
                document.getElementById('pantalla-inicio').classList.add('hidden');
                document.getElementById('pantalla-juego').classList.remove('hidden');
                iniciarCronometro();
                cargarPregunta();
            }

            function iniciarCronometro() {
                if(cronoInterval) clearInterval(cronoInterval);
                cronoInterval = setInterval(() => {
                    segundos++;
                    let min = Math.floor(segundos / 60);
                    let seg = segundos % 60;
                    document.getElementById('cronometro').innerText = `${min.toString().padStart(2,'0')}:${seg.toString().padStart(2,'0')}`;
                }, 1000);
            }

            async function cargarPregunta() {
                try {
                    const response = await fetch(`obtener_pregunta.php?nivel=${preguntaActual}`);
                    preguntaActualData = await response.json();

                    if (preguntaActualData) {
                        haFalladoActual = false;
                        document.getElementById('pregunta-header').innerText = `Nivel ${preguntaActual} de 5`;

                        const txtEval = document.getElementById('enunciado-texto');
                        const imgEval = document.getElementById('enunciado-imagen');

                        // Mostrar Texto e Imagen si existen
                        if (preguntaActualData.enunciado && preguntaActualData.enunciado.trim() !== "") {
                            txtEval.innerText = preguntaActualData.enunciado;
                            txtEval.classList.remove('hidden');
                        } else { txtEval.classList.add('hidden'); }

                        if (preguntaActualData.imagen_enunciado && preguntaActualData.imagen_enunciado.trim() !== "") {
                            imgEval.src = "uploads/" + preguntaActualData.imagen_enunciado;
                            imgEval.classList.remove('hidden');
                        } else { imgEval.classList.add('hidden'); }

                        document.getElementById('seccion-ayuda').classList.add('hidden');
                        document.getElementById('contenedor-imagen-ayuda').classList.add('hidden');

                        renderizarInterfazRespuesta();
                    }
                } catch (error) { console.error("Error:", error); }
            }

            function renderizarInterfazRespuesta() {
                const contenedor = document.getElementById('contenedor-respuestas');
                contenedor.innerHTML = "";
                itemSeleccionadoIzq = null;
                parejasResueltas = 0;

                if (preguntaActualData.formato === 'relacionar') {
                    contenedor.classList.remove('grid-cols-1', 'md:grid-cols-2');
                    let rawData = [preguntaActualData.respuesta_correcta, preguntaActualData.opcion_b, preguntaActualData.opcion_c, preguntaActualData.opcion_d, preguntaActualData.opcion_e].filter(p => p && p.includes('|'));
                    let pairs = rawData.map(p => { let parts = p.split('|'); return { izq: parts[0].trim(), der: parts[1].trim() }; });
                    totalParejas = pairs.length;

                    let listaIzq = pairs.map(p => p.izq).sort(() => Math.random() - 0.5);
                    let listaDer = pairs.map(p => p.der).sort(() => Math.random() - 0.5);

                    contenedor.innerHTML = `<div class="grid grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-200"><div class="flex flex-col gap-3" id="col-izq"></div><div class="flex flex-col gap-3" id="col-der"></div></div>`;
                    const divIzq = document.getElementById('col-izq');
                    const divDer = document.getElementById('col-der');

                    listaIzq.forEach(texto => {
                        const btn = document.createElement('button');
                        btn.innerText = texto;
                        btn.className = "btn-relacionar p-3 bg-white border-2 border-gray-200 rounded-lg text-sm font-bold shadow-sm hover:border-blue-400";
                        btn.onclick = () => { document.querySelectorAll('#col-izq button').forEach(b => b.classList.remove('seleccionado')); btn.classList.add('seleccionado'); itemSeleccionadoIzq = texto; };
                        divIzq.appendChild(btn);
                    });

                    listaDer.forEach(texto => {
                        const btn = document.createElement('button');
                        btn.innerText = texto;
                        btn.className = "btn-relacionar p-3 bg-white border-2 border-gray-200 rounded-lg text-sm font-bold shadow-sm hover:border-blue-400";
                        btn.onclick = () => {
                            if (!itemSeleccionadoIzq) { Swal.fire({ icon: 'info', title: 'Aviso', text: 'Selecciona primero un concepto de la izquierda', timer: 1500 }); return; }
                            if (pairs.some(p => p.izq === itemSeleccionadoIzq && p.der === texto)) {
                                btn.classList.add('completado');
                                document.querySelector('#col-izq button.seleccionado').classList.add('completado');
                                itemSeleccionadoIzq = null;
                                parejasResueltas++;
                                if (parejasResueltas === totalParejas) { validarRespuesta("CORRECTO_RELACIONAR"); }
                            } else {
                                haFalladoActual = true;
                                Swal.fire({ icon: 'error', title: 'Pareja Incorrecta', text: 'Sigue intentándolo', timer: 1000, showConfirmButton: false });
                                mostrarSeccionAyuda();
                            }
                        };
                        divDer.appendChild(btn);
                    });

                } else if (preguntaActualData.formato === 'multiple' || (preguntaActualData.opcion_b && preguntaActualData.opcion_b.trim() !== "")) {
                    contenedor.classList.add('grid-cols-1', 'md:grid-cols-2');
                    let opciones = [preguntaActualData.respuesta_correcta, preguntaActualData.opcion_b, preguntaActualData.opcion_c, preguntaActualData.opcion_d, preguntaActualData.opcion_e].filter(o => o && o.trim() !== "");
                    opciones.sort(() => Math.random() - 0.5);
                    opciones.forEach(opt => {
                        const btn = document.createElement('button');
                        btn.innerText = opt;
                        btn.className = "p-4 bg-gray-50 border-2 border-gray-200 rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-200 font-bold text-lg shadow-sm";
                        btn.onclick = () => validarRespuesta(opt);
                        contenedor.appendChild(btn);
                    });
                } else {
                    contenedor.classList.remove('grid-cols-1', 'md:grid-cols-2');
                    contenedor.innerHTML = `<div class="flex flex-col gap-4"><input type="text" id="respuesta-user" autofocus placeholder="Escribe tu respuesta aquí..." class="w-full border-2 p-4 rounded-xl text-xl focus:border-blue-500 outline-none text-center shadow-inner"><button id="btn-verificar" class="w-full bg-green-500 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-green-600 transition">VERIFICAR RESULTADO</button></div>`;
                    setTimeout(() => {
                        const input = document.getElementById('respuesta-user');
                        const btn = document.getElementById('btn-verificar');
                        if (input) { input.focus(); btn.onclick = () => validarRespuesta(input.value); input.onkeypress = (e) => { if (e.key === 'Enter') validarRespuesta(input.value); }; }
                    }, 100);
                }
            }

            // Función centralizada para mostrar la ayuda (Imagen y Texto)
            function mostrarSeccionAyuda() {
                haFalladoActual = true;
                const textoAyuda = document.getElementById('formula-texto');
                const imgAyuda = document.getElementById('formula-imagen');
                const contImgAyuda = document.getElementById('contenedor-imagen-ayuda');
                const seccionAyuda = document.getElementById('seccion-ayuda');

                textoAyuda.innerText = preguntaActualData.formula_ayuda || "Revisa el procedimiento e intenta de nuevo.";
                
                // Verificamos que el campo no sea null ni esté vacío
                if (preguntaActualData.imagen_ayuda && preguntaActualData.imagen_ayuda.trim() !== "") {
                    imgAyuda.src = "uploads/" + preguntaActualData.imagen_ayuda;
                    contImgAyuda.classList.remove('hidden');
                } else {
                    contImgAyuda.classList.add('hidden');
                }
                
                seccionAyuda.classList.remove('hidden');
            }

            function validarRespuesta(rptaUsuario) {
                if (rptaUsuario === "CORRECTO_RELACIONAR") {
                    const puntosAGanar = haFalladoActual ? 1 : 2;
                    puntos += puntosAGanar;
                    document.getElementById('display-puntos').innerText = puntos;
                    Swal.fire({ icon: 'success', title: '¡Muy bien!', text: `Has relacionado todas las parejas. +${puntosAGanar} punto(s).`, confirmButtonColor: '#059669', timer: 2000 }).then(() => { if (preguntaActual < 5) { preguntaActual++; cargarPregunta(); } else { finalizarJuego(); } });
                    return;
                }

                if (!rptaUsuario || rptaUsuario.toString().trim() === "") return;
                const correcta = preguntaActualData.respuesta_correcta.toString().trim().toLowerCase();
                const usuario = rptaUsuario.toString().trim().toLowerCase();

                if (usuario === correcta) {
                    const puntosAGanar = haFalladoActual ? 1 : 2;
                    puntos += puntosAGanar;
                    document.getElementById('display-puntos').innerText = puntos;
                    Swal.fire({ icon: 'success', title: '¡Excelente!', text: `Respuesta correcta. +${puntosAGanar} punto(s).`, confirmButtonColor: '#059669', timer: 2000, timerProgressBar: true }).then(() => { if (preguntaActual < 5) { preguntaActual++; cargarPregunta(); } else { finalizarJuego(); } });
                } else {
                    Swal.fire({ icon: 'error', title: 'Respuesta Incorrecta', text: 'Inténtalo de nuevo o revisa la ayuda.', timer: 2000, confirmButtonColor: '#059669', timerProgressBar: true });
                    mostrarSeccionAyuda();
                }
            }

            function mostrarVideo() {
                const videoId = preguntaActualData.url_youtube;
                if (!videoId) { Swal.fire({ icon: 'info', title: 'Tutorial', text: 'No hay video disponible.' }); return; }
                document.getElementById('iframe-video').src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                document.getElementById('modal-video').style.display = 'flex';
            }

            function cerrarModal() { document.getElementById('modal-video').style.display = 'none'; document.getElementById('iframe-video').src = ""; }

            function finalizarJuego() {
                clearInterval(cronoInterval);
                const nick = document.getElementById('nickname').value;
                const datos = new FormData();
                datos.append('nickname', nick);
                datos.append('puntos', puntos);
                datos.append('tiempo', segundos);

                fetch('guardar_ranking.php', { method: 'POST', body: datos })
                    .then(res => res.json())
                    .then(data => { Swal.fire({ icon: 'success', title: '¡Desafío Completado!', html: `<b>${nick}</b>, lograste <b>${puntos} puntos</b> en <b>${segundos} segundos</b>.`, confirmButtonColor: '#2563eb', confirmButtonText: 'Ver Ranking' }).then(() => { location.reload(); }); })
                    .catch(err => { console.error("Error guardando ranking:", err); location.reload(); });
            }
        </script>
    </body>
    </html>

<?php
} catch (PDOException $e) { die("Error crítico de conexión: " . $e->getMessage()); }
?>