<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Подключение библиотеки PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Получаем restId из GET-параметров
$restId = $_GET['restId'] ?? null;

if (!$restId) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Параметр restId не передан'
    ]);
    exit;
}

try {
    // Подключение к базе данных
    $pdo = getDbConnection();

    // Запрос данных из таблицы orders для указанного restId и статусом "completed" с сортировкой по order_time в порядке убывания
    $stmt = $pdo->prepare("SELECT id, full_name, address, total_amount, id_tg, order_time, product_ids, card_number, product_details, unique_order_id FROM orders WHERE restaurant_id = :restId AND status = 'completed' ORDER BY order_time DESC");
    $stmt->bindParam(':restId', $restId, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Создание нового объекта Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if (empty($data)) {
        // Запрос колонок для таблицы orders
        $columnsStmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'orders'");
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Добавляем колонку для phone_number и unique_order_id
        $columns[] = 'phone_number';
        $columns[] = 'unique_order_id';

        // Запись заголовков в первую строку
        foreach ($columns as $columnIndex => $columnName) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue($columnLetter . '1', $columnName);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true); // Установка автоширины для столбцов
        }
    } else {
        // Запись заголовков из данных, добавляем колонку phone_number и unique_order_id
        $columns = array_keys($data[0]);
        $columns[] = 'phone_number';
        foreach ($columns as $columnIndex => $columnName) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue($columnLetter . '1', $columnName);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true); // Установка автоширины для столбцов
        }

        // Запись данных и добавление номера телефона
        foreach ($data as $rowIndex => $row) {
            foreach ($columns as $columnIndex => $columnName) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                if ($columnName === 'phone_number') {
                    // Запрос номера телефона из таблицы users по id_tg и restaurant_id
                    $phoneStmt = $pdo->prepare("SELECT phone_number FROM users WHERE id_tg = :id_tg AND restaurant_id = :restaurant_id LIMIT 1");
                    $phoneStmt->bindParam(':id_tg', $row['id_tg'], PDO::PARAM_INT);
                    $phoneStmt->bindParam(':restaurant_id', $restId, PDO::PARAM_INT); // Используем переменную $restId
                    $phoneStmt->execute();
                    $phoneNumber = $phoneStmt->fetchColumn();
                    $sheet->setCellValueExplicit($columnLetter . ($rowIndex + 2), $phoneNumber ?: 'Не найден', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    // Запись остальных данных
                    $sheet->setCellValueExplicit($columnLetter . ($rowIndex + 2), $row[$columnName], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $sheet->getStyle($columnLetter . ($rowIndex + 2))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
            }
        }
    }

    // Сохранение Excel-файла во временную директорию
    $filename = 'OrdersList.xlsx';
    $temp_file = sys_get_temp_dir() . '/' . $filename;

    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);

    // Отправка файла клиенту
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp_file));
    
    // Очистка буфера вывода перед отправкой файла
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
