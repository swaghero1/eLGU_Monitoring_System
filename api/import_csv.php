<?php
// api/import_csv.php
header('Content-Type: application/json');
require_once '../db.php';

$filename = '../lgu_data.csv';

if (!file_exists($filename)) {
    die(json_encode(['success' => false, 'message' => 'Please name your file lgu_data.csv and place it in the root folder.']));
}

$file = fopen($filename, 'r');
$header = fgetcsv($file); // Skip the header row

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO lgus (region, province, district, income_class, municipality, v1_operational, v2_operational, own_system, overall_status) 
        VALUES (:region, :province, :district, :income, :municipality, :v1, :v2, :own, 'For Engagement')
    ");

    $count = 0;
    while (($row = fgetcsv($file)) !== FALSE) {
        // Skip empty rows
        if(empty($row[0]) || empty($row[4])) continue;

        $v1_status = strtolower($row[5] ?? '');
        $v2_status = strtolower($row[6] ?? '');

        $v1 = (strpos($v1_status, 'operational') !== false) ? 1 : 0;
        $v2 = (strpos($v2_status, 'operational') !== false) ? 1 : 0;
        $own = (strpos($v1_status, 'own system') !== false || strpos($v2_status, 'own system') !== false) ? 1 : 0;

        $stmt->execute([
            ':region' => $row[0],
            ':province' => $row[1],
            ':district' => $row[2] ?: 'Lone District',
            ':income' => $row[3] ?: 'Unknown',
            ':municipality' => $row[4],
            ':v1' => $v1,
            ':v2' => $v2,
            ':own' => $own
        ]);
        $count++;
    }

    $pdo->commit();
    fclose($file);
    echo json_encode(['success' => true, 'message' => "Successfully imported $count LGUs!"]);

} catch (\PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>