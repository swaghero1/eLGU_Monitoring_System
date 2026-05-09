<?php
// import.php
session_start();
require_once 'db.php';

// Security check: Only allow logged-in Admins
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== FALSE) {
        // 1. Clear the table safely before importing to avoid duplicates
        $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->query("TRUNCATE TABLE lgus");
        $pdo->query("SET FOREIGN_KEY_CHECKS = 1");

        // 2. Prepare the insert statement
        $stmt = $pdo->prepare("INSERT INTO lgus (region, province, congressional_district, municipal_class, municipality, current_system) VALUES (:region, :province, :district, :class, :municipality, :system)");

        // Skip the header row
        fgetcsv($handle);

        $count = 0;
        // Loop through the CSV rows
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Map the CSV columns:
            // [0] Region, [1] Province, [2] District, [3] Class, [4] Municipality, [5] V1 Status, [6] V2 Status
            $region = trim($data[0] ?? 'Region IX');
            $province = trim($data[1] ?? '');
            $district = trim($data[2] ?? '');
            $class = trim($data[3] ?? '');
            $municipality = trim($data[4] ?? '');
            
            $v1 = strtolower(trim($data[5] ?? ''));
            $v2 = strtolower(trim($data[6] ?? ''));

            // Smart logic to determine their current system status
            $current_system = 'None';
            if (strpos($v1, 'own system') !== false) {
                $current_system = 'Own System';
            } elseif (strpos($v2, 'operational') !== false) {
                $current_system = 'Version 2';
            } elseif (strpos($v1, 'operational') !== false) {
                $current_system = 'Version 1';
            }

            // Only insert if it has an actual municipality name
            if (!empty($municipality) && !empty($province)) {
                $stmt->execute([
                    ':region' => $region,
                    ':province' => $province,
                    ':district' => $district,
                    ':class' => $class,
                    ':municipality' => $municipality,
                    ':system' => $current_system
                ]);
                $count++;
            }
        }
        fclose($handle);
        $message = "<div style='color: #10b981; background: #d1fae5; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;'>Success, Boss! Imported $count LGUs into the Database.</div>";
    } else {
        $message = "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>Error opening the file. Ensure it is a valid CSV.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Command Center - Mass Importer</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { padding: 40px; background: var(--bg-color); display: flex; justify-content: center; align-items: center; height: 100vh;}
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: var(--shadow-lg); width: 100%; max-width: 500px; text-align: center; }
        .card h2 { color: var(--brand-navy); margin-bottom: 10px; }
        input[type="file"] { margin: 20px 0; padding: 10px; width: 100%; border: 1px dashed var(--border-color); border-radius: 8px; background: #f8fafc; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <img src="logo3.png" alt="Logo" style="height: 50px; margin-bottom: 20px;">
        <h2>Mass LGU Importer</h2>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Upload your <strong>List of All LGUs in R9 and BARM.csv</strong> file here.</p>
        
        <?= $message ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; border:none; border-radius: 8px; cursor: pointer; background-color: var(--brand-blue); color: white; font-weight: bold;">Run Import Sequence</button>
        </form>
        
        <a href="index.html" style="display: inline-block; margin-top: 25px; color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">← Back to Command Center</a>
    </div>
</body>
</html> 