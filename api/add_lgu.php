<?php
// api/add_lgu.php
header('Content-Type: application/json');
require_once '../db.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData);

if (isset($data->municipality) && isset($data->province)) {
    try {
        // We now include v1_operational and v2_operational in the insert query
        $stmt = $pdo->prepare("
            INSERT INTO lgus (province, municipality, district, income_class, mayor, contact_name, contact_email, contact_number, v1_operational, v2_operational, overall_status) 
            VALUES (:province, :municipality, :district, :income, :mayor, :contact_name, :contact_email, :contact_number, :v1, :v2, 'For Engagement')
        ");
        
        $stmt->execute([
            ':province' => $data->province,
            ':municipality' => $data->municipality,
            ':district' => $data->district,
            ':income' => $data->income,
            ':mayor' => $data->mayor,
            ':contact_name' => $data->contact_name,
            ':contact_email' => $data->contact_email,
            ':contact_number' => $data->contact_number,
            ':v1' => $data->v1_operational,
            ':v2' => $data->v2_operational
        ]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
}
?>       