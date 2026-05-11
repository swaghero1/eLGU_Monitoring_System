<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, "r")) !== FALSE) {
        $pdo->beginTransaction();
        try {
            $header = fgetcsv($handle); 

            $stmtCheck = $pdo->prepare("SELECT id FROM lgus WHERE province = ? AND municipality = ?");
            
            $stmtInsertCore = $pdo->prepare("INSERT INTO lgus (region, province, district, income_class, municipality, own_system, overall_status) VALUES (?, ?, ?, ?, ?, ?, 'For Engagement')");
            $stmtInsertV1 = $pdo->prepare("INSERT INTO v1_monitoring (lgu_id, v1_operational, v1_status) VALUES (?, ?, ?)");
            $stmtInsertV2 = $pdo->prepare("INSERT INTO v2_monitoring (lgu_id, v2_operational, v2_status) VALUES (?, ?, ?)");
            $stmtInsertBPCO = $pdo->prepare("INSERT INTO bpco_monitoring (lgu_id, bpco_status) VALUES (?, ?)");

            $stmtUpdateCore = $pdo->prepare("UPDATE lgus SET region=?, district=?, income_class=?, own_system=? WHERE id=?");
            $stmtUpdateV1 = $pdo->prepare("UPDATE v1_monitoring SET v1_operational=?, v1_status=? WHERE lgu_id=?");
            $stmtUpdateV2 = $pdo->prepare("UPDATE v2_monitoring SET v2_operational=?, v2_status=? WHERE lgu_id=?");
            $stmtUpdateBPCO = $pdo->prepare("UPDATE bpco_monitoring SET bpco_status=? WHERE lgu_id=?");

            $countInsert = 0; $countUpdate = 0;

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty(trim($row[1])) || empty(trim($row[4]))) continue; 

                $region = trim($row[0] ?? 'Region IX');
                $province = trim($row[1]);
                $district = trim($row[2] ?? 'Lone District');
                $income = trim($row[3] ?? 'Unknown');
                $muni = trim($row[4]);
                
                $v1_status = trim($row[5] ?? 'Pending');
                $v2_status = trim($row[6] ?? 'Pending');
                $bpco_status = trim($row[7] ?? 'Pending');

                $v1_op = (stripos($v1_status, 'operational') !== false && stripos($v1_status, 'non') === false) ? 1 : 0;
                $v2_op = (stripos($v2_status, 'operational') !== false && stripos($v2_status, 'non') === false) ? 1 : 0;
                $own = (stripos($v1_status, 'own system') !== false || stripos($v2_status, 'own system') !== false) ? 1 : 0;

                $stmtCheck->execute([$province, $muni]);
                $existing = $stmtCheck->fetch();

                if ($existing) {
                    $id = $existing['id'];
                    $stmtUpdateCore->execute([$region, $district, $income, $own, $id]);
                    $stmtUpdateV1->execute([$v1_op, $v1_status, $id]);
                    $stmtUpdateV2->execute([$v2_op, $v2_status, $id]);
                    $stmtUpdateBPCO->execute([$bpco_status, $id]);
                    $countUpdate++;
                } else {
                    $stmtInsertCore->execute([$region, $province, $district, $income, $muni, $own]);
                    $id = $pdo->lastInsertId();
                    $stmtInsertV1->execute([$id, $v1_op, $v1_status]);
                    $stmtInsertV2->execute([$id, $v2_op, $v2_status]);
                    $stmtInsertBPCO->execute([$id, $bpco_status]);
                    $countInsert++;
                }
            }
            
            $admin_user = $_SESSION['username'];
            $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin_user, "Ran Mass Data Import: $countInsert LGUs added, $countUpdate LGUs updated."]);

            $pdo->commit(); echo json_encode(['success' => true, 'message' => "Import Complete!\nAdded: $countInsert new LGUs.\nUpdated: $countUpdate existing LGUs."]);
        } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]); }
        fclose($handle);
    } else { echo json_encode(['success' => false, 'message' => 'Failed to read the uploaded file.']); }
}
?> 