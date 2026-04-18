<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathQuiz Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <div id="pantalla-inicio" class="container mx-auto max-w-xl py-10 px-4">
        <h1 class="text-4xl font-bold text-center text-blue-600 mb-8">Desafío Matemático</h1>
        
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <input type="text" id="nickname" placeholder="Tu Nickname" class="w-full border p-3 rounded mb-4 focus:ring-2 focus:ring-blue-400 outline-none">
            <button onclick="empezarJuego()" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition">INICIAR JUEGO</button>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4 text-gray-700 border-b pb-2">Top 10 Mejores Puntuaciones</h2>
            <div id="ranking-container">
                <table class="w-full text-left">
                    <thead><tr class="text-gray-500 text-sm"><th>Pos</th><th>Usuario</th><th>Pts</th><th>Tiempo</th></tr></thead>
                    <tbody id="lista-ranking">
                        <tr><td>1</td><td>Anibal_Dev</td><td>20</td><td>45s</td></tr>
                        <tr><td>2</td><td>User123</td><td>18</td><td>52s</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="pantalla-juego" class="hidden container mx-auto max-w-2xl py-10 px-4">
        <div class="flex justify-between items-center mb-6 bg-blue-600 text-white p-4 rounded-lg shadow-md">
            <div>Nick: <span id="display-nick" class="font-bold text-yellow-300"></span></div>
            <div class="text-2xl font-mono">⏳ <span id="cronometro">00:00</span></div>
            <div>Puntos: <span id="display-puntos" class="font-bold">0</span></div>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-2xl border-t-4 border-blue-500">
            <div id="pregunta-header" class="text-sm font-bold text-blue-500 mb-2 uppercase tracking-widest">Pregunta 1 de 10</div>
            <p id="enunciado" class="text-2xl text-gray-800 mb-6 font-medium">¿Cuál es el resultado de la siguiente operación?</p>
            
            <input type="text" id="respuesta-user" placeholder="Escribe tu respuesta aquí" class="w-full border-2 p-4 rounded-lg mb-4 text-xl focus:border-blue-500 outline-none">
            
            <button onclick="validarRespuesta()" id="btn-responder" class="w-full bg-green-500 text-white py-4 rounded-lg font-bold text-lg shadow-lg hover:bg-green-600">ENVIAR RESPUESTA</button>

            <div id="seccion-ayuda" class="hidden mt-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                <p class="text-red-700 font-bold mb-2">¡Incorrecto!</p>
                <p id="formula-texto" class="text-gray-700 italic mb-4"></p>
                <button onclick="mostrarVideo()" class="bg-red-600 text-white px-4 py-2 rounded text-sm flex items-center gap-2">
                    Ver Tutorial en YouTube 🎥
                </button>
            </div>
        </div>
    </div>

    <div id="modal-video" class="modal flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-2 max-w-3xl w-full relative">
            <button onclick="cerrarModal()" class="absolute -top-10 right-0 text-white text-3xl">&times; Cerrar</button>
            <div class="aspect-video">
                <iframe id="iframe-video" class="w-full h-full" src="" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <script>
        let segundos = 0;
        let cronoInterval;
        let puntos = 0;
        let preguntaActual = 1;

        // Datos de ejemplo (Esto vendría de tu BD PHP)
        const preguntaMock = {
            enunciado: "Calcula el área de un círculo con radio de 5cm. (Usa π = 3.14)",
            correcta: "78.5",
            formula: "Área = π × r²",
            videoId: "7m9-7iT3u-Y" // ID de ejemplo
        };

        function empezarJuego() {
            const nick = document.getElementById('nickname').value;
            if(!nick) return alert("Ingresa un nickname");

            document.getElementById('display-nick').innerText = nick;
            document.getElementById('pantalla-inicio').classList.add('hidden');
            document.getElementById('pantalla-juego').classList.remove('hidden');

            iniciarCronometro();
            cargarPregunta();
        }

        function iniciarCronometro() {
            cronoInterval = setInterval(() => {
                segundos++;
                let min = Math.floor(segundos / 60);
                let seg = segundos % 60;
                document.getElementById('cronometro').innerText = 
                    `${min.toString().padStart(2,'0')}:${seg.toString().padStart(2,'0')}`;
            }, 1000);
        }

        function cargarPregunta() {
            document.getElementById('enunciado').innerText = preguntaMock.enunciado;
            document.getElementById('seccion-ayuda').classList.add('hidden');
            document.getElementById('respuesta-user').value = "";
        }

        function validarRespuesta() {
            const rpta = document.getElementById('respuesta-user').value.trim();
            if(rpta === preguntaMock.correcta) {
                puntos += 2;
                document.getElementById('display-puntos').innerText = puntos;
                alert("¡Excelente! +2 puntos");
                // Aquí iría la lógica para pasar al siguiente Tipo (2..10)
            } else {
                document.getElementById('formula-texto').innerText = "Fórmula sugerida: " + preguntaMock.formula;
                document.getElementById('seccion-ayuda').classList.remove('hidden');
            }
        }

        function mostrarVideo() {
            document.getElementById('iframe-video').src = `https://www.youtube.com/embed/${preguntaMock.videoId}`;
            document.getElementById('modal-video').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modal-video').style.display = 'none';
            document.getElementById('iframe-video').src = "";
        }
    </script>
</body>
</html>