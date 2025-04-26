<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

$pdo = getDatabase();

// Check if there are new urgent client followups
$clientFollowupsCount = $pdo->query("
    SELECT COUNT(*) 
    FROM client_followups 
    WHERE followup_date BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    AND status = 'pending'
")->fetchColumn();

// Check if there are new urgent lead followups
$leadFollowupsCount = $pdo->query("
    SELECT COUNT(*) 
    FROM lead_followups 
    WHERE followup_date BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    AND status = 'pending'
")->fetchColumn();

// Check for leads needing followup
$leadsNeedingFollowupCount = $pdo->query("
    SELECT COUNT(*) 
    FROM leads l
    WHERE l.status != 'converted'
    AND (
        NOT EXISTS (SELECT 1 FROM lead_followups f WHERE f.lead_id = l.id) OR
        DATEDIFF(NOW(), (SELECT MAX(f.followup_date) FROM lead_followups f WHERE f.lead_id = l.id)) > 2
    )
")->fetchColumn();

echo json_encode([
    'has_new_followups' => ($clientFollowupsCount + $leadFollowupsCount) > 0,
    'has_leads_needing_followup' => $leadsNeedingFollowupCount > 0
]);
?>