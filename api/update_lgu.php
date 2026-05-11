<?php
header('Content-Type: application/json'); require_once '../db.php'; session_start();
$data = json_decode(file_get_contents("php://input"));
$admin = isset($_SESSION['username']) ? $_SESSION['username'] : 'System Admin';

if (isset($data->id)) {
    try {
        $pdo->beginTransaction(); 
        
        $sys = $data->current_system_version;
        $own = ($sys === 'Own System') ? 1 : 0;
        
        // Fetch old data to make the audit log extremely descriptive
        $oldStmt = $pdo->prepare("SELECT municipality, current_system_version, overall_status FROM lgus WHERE id = ?");
        $oldStmt->execute([$data->id]);
        $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

        $actionMsg = "Updated LGU " . $old['municipality'] . " master profile.";
        if (isset($data->trigger_v1_to_v2) && $data->trigger_v1_to_v2 == true) {
            $actionMsg = "Migrated LGU " . $old['municipality'] . " from V1 Pipeline to V2 Pipeline.";
        } elseif ($old['current_system_version'] !== $sys) {
            $actionMsg = "Shifted LGU " . $old['municipality'] . " system version to " . $sys . ".";
        } elseif ($old['overall_status'] !== $data->overall_status) {
            $actionMsg = "Updated LGU " . $old['municipality'] . " overall status to " . $data->overall_status . ".";
        }
        
        if(isset($data->trigger_v1_to_v2) && $data->trigger_v1_to_v2 == true) {
            $pdo->prepare("UPDATE lgus SET converted_v1_to_v2 = 1 WHERE id = ?")->execute([$data->id]);
            $pdo->prepare("UPDATE v2_monitoring SET previous_v1_user = 1 WHERE lgu_id = ?")->execute([$data->id]);
        }

        $stmtCore = $pdo->prepare("UPDATE lgus SET overall_status = :status, region = :region, province = :province, municipality = :muni, district = :district, mayor = :mayor, income_class = :income, own_system = :own, current_system_version = :sys WHERE id = :id");
        $stmtCore->execute([':status'=>$data->overall_status, ':region'=>$data->region, ':province'=>$data->province, ':muni'=>$data->municipality, ':district'=>$data->district, ':mayor'=>$data->mayor, ':income'=>$data->income_class, ':own'=>$own, ':sys'=>$sys, ':id'=>$data->id]);

        $stmtV1 = $pdo->prepare("UPDATE v1_monitoring SET v1_operational = :v1, v1_status = :v1_s, v1_loi_date = :v1_loi, v1_loi_link = :v1_loi_l, v1_sb_reso_date = :v1_sb, v1_sb_link = :v1_sb_l, v1_moa_date = :v1_moa, v1_moa_link = :v1_moa_l, v1_cbd_date = :v1_cbd, v1_cbd_link = :v1_cbd_l WHERE lgu_id = :id");
        $stmtV1->execute([':v1'=>($sys==='V1')?1:0, ':v1_s'=>$data->v1_status, ':v1_loi'=>empty($data->v1_loi_date)?null:$data->v1_loi_date, ':v1_loi_l'=>$data->v1_loi_link, ':v1_sb'=>empty($data->v1_sb_reso_date)?null:$data->v1_sb_reso_date, ':v1_sb_l'=>$data->v1_sb_link, ':v1_moa'=>empty($data->v1_moa_date)?null:$data->v1_moa_date, ':v1_moa_l'=>$data->v1_moa_link, ':v1_cbd'=>empty($data->v1_cbd_date)?null:$data->v1_cbd_date, ':v1_cbd_l'=>$data->v1_cbd_link, ':id'=>$data->id]);

        $stmtV2 = $pdo->prepare("UPDATE v2_monitoring SET v2_operational = :v2, ereadiness_score = :score, e_readiness_label = :label, v2_ereadiness_link = :v2_er_l, v2_status = :v2_s, v2_loi_date = :v2_loi, v2_loi_link = :v2_loi_l, v2_sb_reso_date = :v2_sb, v2_sb_link = :v2_sb_l, v2_mou_date = :v2_mou, v2_mou_link = :v2_mou_l, v2_cbd_date = :v2_cbd, v2_cbd_link = :v2_cbd_l, v2_cbd_endorsed = :v2_cbd_end, v2_cbd_approved = :v2_cbd_app, v2_training_date = :tr_date, v2_training_pax_m = :tr_m, v2_training_pax_f = :tr_f, v2_training_aar = :tr_aar, v2_training_consent = :tr_con, v2_training_desig = :tr_desig, v2_training_status = :tr_stat, v2_uat_sys_status = :v2_uat, v2_uat_sys_rem = :v2_uat_sr, v2_uat_egov_stat = :v2_uat_es, v2_uat_egov_rem = :v2_uat_er, v2_dry_run_date = :v2_dry, v2_dry_run_status = :dry_stat, v2_dry_run_pax_m = :dry_m, v2_dry_run_pax_f = :dry_f, v2_dry_run_aar = :dry_aar, v2_migration_status = :mig_stat, v2_migration_remarks = :mig_rem, v2_launch_date = :v2_l, v2_launch_pax = :l_pax, v2_launch_status = :l_stat WHERE lgu_id = :id");
        $stmtV2->execute([':v2'=>($sys==='V2')?1:0, ':score'=>empty($data->ereadiness_score)?null:$data->ereadiness_score, ':label'=>$data->e_readiness_label, ':v2_er_l'=>$data->v2_ereadiness_link, ':v2_s'=>$data->v2_status, ':v2_loi'=>empty($data->v2_loi_date)?null:$data->v2_loi_date, ':v2_loi_l'=>$data->v2_loi_link, ':v2_sb'=>empty($data->v2_sb_reso_date)?null:$data->v2_sb_reso_date, ':v2_sb_l'=>$data->v2_sb_link, ':v2_mou'=>empty($data->v2_mou_date)?null:$data->v2_mou_date, ':v2_mou_l'=>$data->v2_mou_link, ':v2_cbd'=>empty($data->v2_cbd_date)?null:$data->v2_cbd_date, ':v2_cbd_l'=>$data->v2_cbd_link, ':v2_cbd_end'=>empty($data->v2_cbd_endorsed)?null:$data->v2_cbd_endorsed, ':v2_cbd_app'=>empty($data->v2_cbd_approved)?null:$data->v2_cbd_approved, ':tr_date'=>empty($data->v2_training_date)?null:$data->v2_training_date, ':tr_m'=>$data->v2_training_pax_m, ':tr_f'=>$data->v2_training_pax_f, ':tr_aar'=>$data->v2_training_aar, ':tr_con'=>$data->v2_training_consent, ':tr_desig'=>$data->v2_training_desig, ':tr_stat'=>$data->v2_training_status, ':v2_uat'=>$data->v2_uat_sys_status, ':v2_uat_sr'=>$data->v2_uat_sys_rem, ':v2_uat_es'=>$data->v2_uat_egov_stat, ':v2_uat_er'=>$data->v2_uat_egov_rem, ':v2_dry'=>empty($data->v2_dry_run_date)?null:$data->v2_dry_run_date, ':dry_stat'=>$data->v2_dry_run_status, ':dry_m'=>$data->v2_dry_run_pax_m, ':dry_f'=>$data->v2_dry_run_pax_f, ':dry_aar'=>$data->v2_dry_run_aar, ':mig_stat'=>$data->v2_migration_status, ':mig_rem'=>$data->v2_migration_remarks, ':v2_l'=>empty($data->v2_launch_date)?null:$data->v2_launch_date, ':l_pax'=>$data->v2_launch_pax, ':l_stat'=>$data->v2_launch_status, ':id'=>$data->id]);

        $stmtBPCO = $pdo->prepare("UPDATE bpco_monitoring SET bpco_status = :bpco, bpco_loi_date = :bpco_loi, bpco_loi_link = :bpco_loi_l, bpco_desig_date = :bpco_desig, bpco_desig_link = :bpco_desig_l, bpco_trn_start = :bpco_ts, bpco_trn_end = :bpco_te, bpco_trn_m = :bpco_tm, bpco_trn_f = :bpco_tf, bpco_db_start = :bpco_ds, bpco_db_end = :bpco_de, bpco_db_m = :bpco_dm, bpco_db_f = :bpco_df WHERE lgu_id = :id");
        $stmtBPCO->execute([':bpco'=>$data->bpco_status, ':bpco_loi'=>empty($data->bpco_loi_date)?null:$data->bpco_loi_date, ':bpco_loi_l'=>$data->bpco_loi_link, ':bpco_desig'=>empty($data->bpco_desig_date)?null:$data->bpco_desig_date, ':bpco_desig_l'=>$data->bpco_desig_link, ':bpco_ts'=>empty($data->bpco_trn_start)?null:$data->bpco_trn_start, ':bpco_te'=>empty($data->bpco_trn_end)?null:$data->bpco_trn_end, ':bpco_tm'=>$data->bpco_trn_m, ':bpco_tf'=>$data->bpco_trn_f, ':bpco_ds'=>empty($data->bpco_db_start)?null:$data->bpco_db_start, ':bpco_de'=>empty($data->bpco_db_end)?null:$data->bpco_db_end, ':bpco_dm'=>$data->bpco_db_m, ':bpco_df'=>$data->bpco_db_f, ':id'=>$data->id]);

        $delStmt = $pdo->prepare("DELETE FROM lgu_contacts WHERE lgu_id = :id"); $delStmt->execute([':id' => $data->id]);
        if (isset($data->contacts) && is_array($data->contacts)) {
            $insStmt = $pdo->prepare("INSERT INTO lgu_contacts (lgu_id, name, email, contact_number, role, designation, is_active) VALUES (:lgu_id, :name, :email, :contact, :role, :desig, :active)");
            foreach($data->contacts as $c) { $insStmt->execute([':lgu_id' => $data->id, ':name' => $c->name, ':email' => $c->email, ':contact' => $c->contact_number, ':role' => $c->role, ':desig' => $c->designation, ':active' => $c->is_active]); }
        }

        $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin, $actionMsg]);

        $pdo->commit(); echo json_encode(['success' => true]);
    } catch (\PDOException $e) { $pdo->rollBack(); echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
}
?> 