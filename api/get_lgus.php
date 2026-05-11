<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    // Stitching the normalized tables together via LEFT JOIN
    $stmt = $pdo->query("
        SELECT l.*, 
               v1.*, v2.*, b.* FROM lgus l
        LEFT JOIN v1_monitoring v1 ON l.id = v1.lgu_id
        LEFT JOIN v2_monitoring v2 ON l.id = v2.lgu_id
        LEFT JOIN bpco_monitoring b ON l.id = b.lgu_id
        ORDER BY l.province, l.municipality
    ");
    $lgus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contactStmt = $pdo->query("SELECT * FROM lgu_contacts");
    $allContacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $contactsByLgu = [];
    foreach($allContacts as $c) {
        $contactsByLgu[$c['lgu_id']][] = $c;
    }

    foreach($lgus as &$lgu) {
        $lgu['contacts'] = isset($contactsByLgu[$lgu['id']]) ? $contactsByLgu[$lgu['id']] : [];
    }

    echo json_encode(['success' => true, 'data' => $lgus]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 