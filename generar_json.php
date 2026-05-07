<?php
function scanPdfs($dir, $baseDir = 'pdf') {
    $result = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . '/' . $item;
        $relativePath = str_replace($baseDir . '/', '', $fullPath);
        if (is_dir($fullPath)) {
            $result = array_merge($result, scanPdfs($fullPath, $baseDir));
        } elseif (preg_match('/\.pdf$/i', $item)) {
            // Extraer categoría, subcategoría, autor desde la estructura de carpetas
            $parts = explode('/', $relativePath);
            $categoria = $parts[0] ?? 'variados';
            $subcategoria = ($parts[1] ?? '') && !preg_match('/\.pdf$/i', $parts[1]) ? $parts[1] : '';
            $autor = (count($parts) > 2 && !preg_match('/\.pdf$/i', $parts[2])) ? $parts[2] : '';
            $titulo = pathinfo($item, PATHINFO_FILENAME);
            // Limpiar nombres (quitar números iniciales y espacios extra)
            $titulo = preg_replace('/^\d+\s*/', '', $titulo);
            $titulo = preg_replace('/\s+/', ' ', $titulo);
            $ruta = $relativePath;
            $result[] = [
                'categoria' => $categoria,
                'subcategoria' => $subcategoria ?: '',
                'autor' => $autor ?: '',
                'titulo' => $titulo,
                'ruta' => $ruta,
                'microdescripcion' => 'Documento almacenado en ' . $categoria,
                'descripcion' => 'Accede al recurso para más información.'
            ];
        }
    }
    return $result;
}

$pdfs = scanPdfs(__DIR__ . '/pdf');
// Mantener la estructura que espera tu HTML
$comandos = [
    ['id' => 'educacion', 'subcomandos' => [...]], // si tienes datos estáticos, mantenlos
    ['id' => 'tecnosocial', 'subcomandos' => [...]]
];
$data = [
    'comandos' => $comandos,
    'pdfs' => $pdfs
];
file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ data.json generado con " . count($pdfs) . " PDFs.";