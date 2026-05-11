<?php
header('Content-Type: application/json');
require_once '../db.php';
$rawData = file_get_contents("php://input");
$data = json_decode($rawData);

if (isset($data->municipality) && isset($data->province)) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO lgus (region, province, district, income_class, mayor, own_system, overall_status) VALUES ('Region IX', :province, :district, :income, :mayor, 0, 'For Engagement')");
        $stmt->execute([':province' => $data->province, ':district' => $data->district, ':income' => $data->income, ':mayor' => $data->mayor]);
        $lgu_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO v1_monitoring (lgu_id, v1_operational) VALUES (?, ?)")->execute([$lgu_id, $data->v1_operational]);
        $pdo->prepare("INSERT INTO v2_monitoring (lgu_id, v2_operational) VALUES (?, ?)")->execute([$lgu_id, $data->v2_operational]);
        $pdo->prepare("INSERT INTO bpco_monitoring (lgu_id) VALUES (?)")->execute([$lgu_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 