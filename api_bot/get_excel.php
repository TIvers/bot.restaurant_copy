<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Подключение библиотеки PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Подключение к базе данных
    $pdo = getDbConnection();

    // Запрос данных из таблицы rest_bot_data с сортировкой по id в порядке убывания
    $stmt = $pdo->query("SELECT * FROM rest_bot_data ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Проверка наличия данных в таблице
    if (empty($data)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Таблица rest_bot_data пуста'
        ]);
        exit;
    }

    // Создание нового объекта Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Запись заголовков
    $columns = array_keys($data[0]);
    foreach ($columns as $columnIndex => $columnName) {
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
        $sheet->setCellValue($columnLetter . '1', $columnName);
        $sheet->getStyle($columnLetter . '1')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getColumnDimension($columnLetter)->setAutoSize(true); // Установка автоширины для столбцов
    }

    // Запись данных
    foreach ($data as $rowIndex => $row) {
        foreach ($columns as $columnIndex => $columnName) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValueExplicit($columnLetter . ($rowIndex + 2), $row[$columnName], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getStyle($columnLetter . ($rowIndex + 2))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }
    }

    // Сохранение Excel-файла во временную директорию
    $filename = 'RestaurantsList.xlsx';
    $temp_file = sys_get_temp_dir() . '/' . $filename;

    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);

    // Отправка файла клиенту
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp_file));
    
    // Убедитесь, что ничего не выводится до этого момента
    ob_clean(); // Очистка буфера вывода
    flush(); // Сброс системы вывода
    readfile($temp_file);

    // Удаление временного файла
    unlink($temp_file);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при получении данных из базы: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при создании Excel-файла: ' . $e->getMessage()
    ]);
}
?>
