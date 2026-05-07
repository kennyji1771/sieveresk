<?php
/**
 * Escanea la carpeta /pdf y genera data.json
 * No renombra archivos, solo registra las rutas reales.
 */

function escanearPDFs($dir, $baseDir = 'pdf') {
    $resultados = [];
    $items = scandir($dir);
    if ($items === false) return $resultados;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $rutaCompleta = $dir . DIRECTORY_SEPARATOR . $item;
        $rutaRelativa = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $rutaCompleta);
        $rutaRelativa = str_replace(DIRECTORY_SEPARATOR, '/', $rutaRelativa); // para URLs

        if (is_dir($rutaCompleta)) {
            $resultados = array_merge($resultados, escanearPDFs($rutaCompleta, $baseDir));
        } elseif (preg_match('/\.pdf$/i', $item)) {
            $partes = explode('/', $rutaRelativa);
            $nombreArchivo = array_pop($partes);
            $categoria = $partes[0] ?? 'variados';
            $subcategoria = $partes[1] ?? '';
            $autor = $partes[2] ?? '';
            
            // Limpiar el título (sin extensión, reemplazar _ por espacio, etc.)
            $titulo = pathinfo($nombreArchivo, PATHINFO_FILENAME);
            $titulo = str_replace(['_', '-'], ' ', $titulo);
            $titulo = preg_replace('/^\d+\s*/', '', $titulo); // quita prefijos como "001 "
            $titulo = ucwords(trim($titulo));
            
            $resultados[] = [
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'autor' => $autor,
                'titulo' => $titulo,
                'ruta' => $rutaRelativa,
                'microdescripcion' => "Documento en {$categoria}" . ($subcategoria ? " / {$subcategoria}" : ''),
                'descripcion' => 'Consulta el documento completo usando el botón "Ver".'
            ];
        }
    }
    return $resultados;
}

// Cargar data.json existente para conservar los comandos (Programas Educativos y Tecnología Social)
$rutaData = __DIR__ . '/data.json';
$comandosExistentes = [];

if (file_exists($rutaData)) {
    $dataVieja = json_decode(file_get_contents($rutaData), true);
    if (isset($dataVieja['comandos']) && is_array($dataVieja['comandos'])) {
        $comandosExistentes = $dataVieja['comandos'];
    }
}

// Si no hay comandos previos, inicializamos con estructura vacía (evita que se pierdan)
if (empty($comandosExistentes)) {
    $comandosExistentes = [
        ['id' => 'educacion', 'subcomandos' => []],
        ['id' => 'tecnosocial', 'subcomandos' => []]
    ];
}

// Escanear PDFs
$pdfs = escanearPDFs(__DIR__ . '/pdf');

// Construir el nuevo data.json
$nuevoData = [
    'comandos' => $comandosExistentes,
    'pdfs' => $pdfs
];

// Guardar con formato legible
if (file_put_contents($rutaData, json_encode($nuevoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "✅ data.json actualizado correctamente.<br>";
    echo "📄 Total de PDFs encontrados: " . count($pdfs) . "<br>";
    echo "📂 Categorías detectadas:<ul>";
    $cats = array_unique(array_column($pdfs, 'categoria'));
    foreach ($cats as $cat) echo "<li>$cat</li>";
    echo "</ul>";
    echo "⚠️ Recuerda: los archivos mantienen sus nombres originales (con espacios y acentos).<br>";
    echo "➡️ Para que funcionen en la web, debes modificar el index.html como se indica abajo.";
} else {
    echo "❌ Error: No se pudo escribir data.json. Verifica permisos.";
}
?>