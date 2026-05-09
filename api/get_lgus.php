<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    $stmt = $pdo->query("SELECT * FROM lgus ORDER BY province, municipality");
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