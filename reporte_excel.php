<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generarInformeExcel(array $registros, array $meta): string
{
    if (!class_exists(Spreadsheet::class)) {
        throw new RuntimeException(
            'PhpSpreadsheet no está instalado. Ejecute: composer install'
        );
    }

    $spreadsheet = new Spreadsheet();
    $detalle = $spreadsheet->getActiveSheet();
    $detalle->setTitle('Detalle');

    $detalle->setCellValue('A1', 'Informe de trazabilidad de bienes');
    $detalle->mergeCells('A1:M1');
    $detalle->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $detalle->setCellValue('A2', 'Generado: ' . ($meta['generado_en'] ?? ''));
    $detalle->setCellValue('A3', 'Usuario: ' . ($meta['usuario'] ?? '') . ' (' . ($meta['email'] ?? '') . ')');
    $detalle->setCellValue('A4', 'Filtros: ' . ($meta['filtros'] ?? ''));
    $detalle->setCellValue('A5', 'Total registros: ' . ($meta['total'] ?? count($registros)));

    $headers = [
        'A' => 'Código',
        'B' => 'Fecha registro',
        'C' => 'Municipio',
        'D' => 'Juzgado',
        'E' => 'Responsable',
        'F' => 'Tipo de bien',
        'G' => 'Cantidad',
        'H' => 'Unidad',
        'I' => 'Periféricos',
        'J' => 'Observaciones',
        'K' => 'Nº fotos',
        'L' => 'URLs fotos (web)',
        'M' => 'Registrado en sistema',
    ];

    $headerRow = 7;
    foreach ($headers as $col => $label) {
        $detalle->setCellValue($col . $headerRow, $label);
    }

    $headerStyle = $detalle->getStyle("A{$headerRow}:M{$headerRow}");
    $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E79');
    $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $row = $headerRow + 1;
    foreach ($registros as $reg) {
        $detalle->setCellValue("A{$row}", $reg['codigo'] ?? '');
        $detalle->setCellValue("B{$row}", $reg['fecha_registro'] ?? '');
        $detalle->setCellValue("C{$row}", $reg['municipio_nombre'] ?? '');
        $detalle->setCellValue("D{$row}", $reg['juzgado_nombre'] ?? '');
        $detalle->setCellValue("E{$row}", $reg['responsable_nombre'] ?? '');
        $detalle->setCellValue("F{$row}", $reg['tipo_bien_nombre'] ?? '');
        $detalle->setCellValue("G{$row}", (int) ($reg['cantidad'] ?? 0));
        $detalle->setCellValue("H{$row}", $reg['tipo_bien_unidad'] ?? '');
        $detalle->setCellValue("I{$row}", $reg['perifericos_texto'] ?? '—');
        $detalle->setCellValue("J{$row}", $reg['observaciones'] ?? '');
        $detalle->setCellValue("K{$row}", (int) ($reg['num_fotos'] ?? 0));
        $detalle->setCellValue("L{$row}", $reg['fotos_urls_texto'] ?? '');
        $detalle->setCellValue("M{$row}", $reg['created_at'] ?? '');

        $detalle->getStyle("L{$row}")->getAlignment()->setWrapText(true);
        $row++;
    }

    if ($row > $headerRow + 1) {
        $detalle->getStyle('A' . ($headerRow + 1) . ':M' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    foreach (range('A', 'M') as $col) {
        $detalle->getColumnDimension($col)->setAutoSize(true);
    }
    $detalle->getColumnDimension('L')->setWidth(48);

    agregarHojaResumen($spreadsheet, $registros);

    $tmp = tempnam(sys_get_temp_dir(), 'informe_bienes_');
    if ($tmp === false) {
        throw new RuntimeException('No se pudo crear archivo temporal.');
    }
    $archivo = $tmp . '.xlsx';
    rename($tmp, $archivo);

    $writer = new Xlsx($spreadsheet);
    $writer->save($archivo);
    $spreadsheet->disconnectWorksheets();

    return $archivo;
}

function agregarHojaResumen(Spreadsheet $spreadsheet, array $registros): void
{
    $resumen = $spreadsheet->createSheet();
    $resumen->setTitle('Resumen');

    $resumen->setCellValue('A1', 'Resumen por municipio');
    $resumen->getStyle('A1')->getFont()->setBold(true);

    $porMunicipio = [];
    $porTipo = [];
    $totalCantidad = 0;
    $totalFotos = 0;

    foreach ($registros as $reg) {
        $mun = $reg['municipio_nombre'] ?? 'Sin municipio';
        $tipo = $reg['tipo_bien_nombre'] ?? 'Sin tipo';
        $porMunicipio[$mun] = ($porMunicipio[$mun] ?? 0) + 1;
        $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + 1;
        $totalCantidad += (int) ($reg['cantidad'] ?? 0);
        $totalFotos += (int) ($reg['num_fotos'] ?? 0);
    }

    arsort($porMunicipio);
    arsort($porTipo);

    $resumen->setCellValue('A2', 'Municipio');
    $resumen->setCellValue('B2', 'Registros');
    $resumen->getStyle('A2:B2')->getFont()->setBold(true);

    $fila = 3;
    foreach ($porMunicipio as $nombre => $cantidad) {
        $resumen->setCellValue("A{$fila}", $nombre);
        $resumen->setCellValue("B{$fila}", $cantidad);
        $fila++;
    }

    $fila += 2;
    $resumen->setCellValue("A{$fila}", 'Resumen por tipo de bien');
    $resumen->getStyle("A{$fila}")->getFont()->setBold(true);
    $fila++;
    $resumen->setCellValue("A{$fila}", 'Tipo');
    $resumen->setCellValue("B{$fila}", 'Registros');
    $resumen->getStyle("A{$fila}:B{$fila}")->getFont()->setBold(true);
    $fila++;

    foreach ($porTipo as $nombre => $cantidad) {
        $resumen->setCellValue("A{$fila}", $nombre);
        $resumen->setCellValue("B{$fila}", $cantidad);
        $fila++;
    }

    $fila += 2;
    $resumen->setCellValue("A{$fila}", 'Totales generales');
    $resumen->getStyle("A{$fila}")->getFont()->setBold(true);
    $fila++;
    $resumen->setCellValue("A{$fila}", 'Registros exportados');
    $resumen->setCellValue("B{$fila}", count($registros));
    $fila++;
    $resumen->setCellValue("A{$fila}", 'Suma de cantidades');
    $resumen->setCellValue("B{$fila}", $totalCantidad);
    $fila++;
    $resumen->setCellValue("A{$fila}", 'Total fotografías');
    $resumen->setCellValue("B{$fila}", $totalFotos);

    $resumen->getColumnDimension('A')->setAutoSize(true);
    $resumen->getColumnDimension('B')->setAutoSize(true);
}
