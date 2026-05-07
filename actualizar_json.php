<?php
// --- 1. FUNCIÓN PARA LIMPIAR NOMBRES DE ARCHIVO ---
function limpiarNombre($nombre) {
    // Reemplazar espacios y caracteres especiales por guiones bajos
    $nombre = str_replace(' ', '_', $nombre);
    $nombre = str_replace('.', '', $nombre);
    $nombre = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $nombre);
    return $nombre;
}

// --- 2. FUNCIÓN PARA ESCANEAR Y PROCESAR LOS PDFs ---
function escanearPDFs($directorio, $baseDir = 'pdf') {
    $resultado = [];
    $items = scandir($directorio);
    if ($items === false) return $resultado;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $rutaCompleta = $directorio . '/' . $item;
        $rutaRelativa = str_replace($baseDir . '/', '', $rutaCompleta);

        if (is_dir($rutaCompleta)) {
            $resultado = array_merge($resultado, escanearPDFs($rutaCompleta, $baseDir));
        } elseif (preg_match('/\.pdf$/i', $item)) {
            // Limpiar el nombre del archivo
            $nombreLimpio = limpiarNombre(pathinfo($item, PATHINFO_FILENAME));
            $rutaOriginal = $rutaRelativa;
            $directorioBase = dirname($rutaOriginal);
            $nuevoNombreArchivo = $nombreLimpio . '.pdf';
            $nuevaRuta = ($directorioBase != '.' ? $directorioBase . '/' : '') . $nuevoNombreArchivo;

            // RENOMBRAR EL ARCHIVO EN EL DISCO DURO
            if ($rutaCompleta !== $directorio . '/' . $nuevaRuta && rename($rutaCompleta, $directorio . '/' . $nuevaRuta)) {
                $rutaOriginal = $nuevaRuta;
                echo "Renombrado: " . $item . " -> " . $nuevoNombreArchivo . "<br>";
            }

            $partes = explode('/', $rutaOriginal);
            array_pop($partes);
            $categoria = $partes[0] ?? 'variados';
            $subcategoria = $partes[1] ?? '';
            $autor = $partes[2] ?? '';

            $resultado[] = [
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'autor' => $autor,
                'titulo' => $nombreLimpio,
                'ruta' => $rutaOriginal,
                'microdescripcion' => 'Documento PDF en ' . $categoria . ($subcategoria ? ' / ' . $subcategoria : ''),
                'descripcion' => 'Accede al documento completo haciendo clic en "Ver".'
            ];
        }
    }
    return $resultado;
}

// --- 3. CARGAR LA ESTRUCTURA DE COMANDOS EXISTENTE (PROGRAMAS EDUCATIVOS Y TECNOLOGÍA SOCIAL) ---
$rutaDataJson = __DIR__ . '/data.json';
$comandos = [
    'id' => 'educacion',
    'subcomandos' => [
        ['nombre' => 'Desarrollo de Proyectos', 'icono' => 'fas fa-project-diagram', 'enlaces' => []],
        ['nombre' => 'Seguimiento y Evaluación', 'icono' => 'fas fa-chart-line', 'enlaces' => []]
    ]
];
// Si quieres restaurar la estructura antigua, descomenta las líneas 67 a 84.
/*
if (file_exists($rutaDataJson)) {
    $dataVieja = json_decode(file_get_contents($rutaDataJson), true);
    if (isset($dataVieja['comandos']) && !empty($dataVieja['comandos'])) {
        $comandos = $dataVieja['comandos'];
    } else {
        // Si no, restauramos la estructura por defecto (puedes editarla a mano)
        $comandos = [
            ['id' => 'educacion', 'subcomandos' => [
                ['nombre' => 'Desarrollo de Proyectos', 'icono' => 'fas fa-project-diagram', 'enlaces' => []],
                ['nombre' => 'Seguimiento y Evaluación', 'icono' => 'fas fa-chart-line', 'enlaces' => []]
            ]],
            ['id' => 'tecnosocial', 'subcomandos' => [
                ['nombre' => 'Herramientas Digitales', 'icono' => 'fas fa-tools', 'enlaces' => []],
                ['nombre' => 'Análisis de Datos', 'icono' => 'fas fa-chart-pie', 'enlaces' => []]
            ]]
        ];
    }
}
*/

// --- 4. ESCANEAR TODOS LOS PDFs ---
$pdfs = escanearPDFs(__DIR__ . '/pdf');

// --- 5. CREAR EL NUEVO archivo data.json ---
$nuevoData = [
    'comandos' => $comandos,
    'pdfs' => $pdfs
];

if (file_put_contents($rutaDataJson, json_encode($nuevoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "<hr><strong style='color: green;'>✅ ¡Éxito! data.json generado correctamente.</strong><br>";
    echo "📄 <strong>Total de PDFs encontrados y registrados:</strong> " . count($pdfs) . "<br>";
    echo "📂 <strong>Categorías detectadas:</strong><br><ul>";
    $categorias = array_unique(array_column($pdfs, 'categoria'));
    foreach ($categorias as $cat) echo "<li>$cat</li>";
    echo "</ul>";
    echo "<hr><strong>⚠️ Instrucciones finales:</strong><br>";
    echo "1. Verifica que los nombres de los archivos en la carpeta 'pdf/' se hayan limpiado (sin espacios ni acentos).<br>";
    echo "2. Sube TODO (la carpeta 'pdf/' y el nuevo 'data.json') a tu repositorio de GitHub.<br>";
    echo "3. Vercel desplegará automáticamente los cambios.<br>";
} else {
    echo "<strong style='color: red;'>❌ Error: No se pudo guardar el archivo data.json. Verifica los permisos de escritura en la carpeta.</strong>";
}
?>