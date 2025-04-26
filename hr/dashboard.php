<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

// TimeAgo function
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

$pdo = getDatabase();

// Get stats for dashboard
$employeeCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$activeLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$pendingSalaries = $pdo->query("SELECT COUNT(*) FROM salaries WHERE status = 'pending'")->fetchColumn();
$followupsToday = $pdo->query("SELECT COUNT(*) FROM client_followups WHERE DATE(followup_date) = CURDATE() AND status = 'pending'")->fetchColumn();

// Get urgent client followups for the popup (due in next 30 minutes or overdue)
$urgentClientFollowups = $pdo->query("
    SELECT f.*, c.name as client_name, c.phone as client_phone, 
           TIMESTAMPDIFF(MINUTE, NOW(), f.followup_date) as minutes_remaining,
           'client' as type
    FROM client_followups f
    JOIN clients c ON f.client_id = c.id
    WHERE f.followup_date BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    AND f.status = 'pending'
    ORDER BY f.followup_date ASC
")->fetchAll();

// Get urgent lead followups for the popup (due in next 30 minutes or overdue)
$urgentLeadFollowups = $pdo->query("
    SELECT f.*, l.name as lead_name, l.phone as lead_phone, 
           TIMESTAMPDIFF(MINUTE, NOW(), f.followup_date) as minutes_remaining,
           'lead' as type
    FROM lead_followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.followup_date BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    AND f.status = 'pending'
    ORDER BY f.followup_date ASC
")->fetchAll();

// Get leads needing followup (no followup in last 2 days)
$leadsNeedingFollowup = $pdo->query("
    SELECT l.*, 
           (SELECT MAX(f.followup_date) FROM lead_followups f WHERE f.lead_id = l.id) as last_followup,
           DATEDIFF(NOW(), (SELECT MAX(f.followup_date) FROM lead_followups f WHERE f.lead_id = l.id)) as days_since_followup
    FROM leads l
    WHERE l.status != 'converted'
    HAVING last_followup IS NULL OR days_since_followup > 2
    ORDER BY days_since_followup DESC
    LIMIT 5
")->fetchAll();

// Combine all urgent followups
$urgentFollowups = array_merge($urgentClientFollowups, $urgentLeadFollowups);
usort($urgentFollowups, function($a, $b) {
    return $a['minutes_remaining'] <=> $b['minutes_remaining'];
});

// Convert to JSON for JavaScript
$urgentFollowupsJson = json_encode($urgentFollowups);
$leadsNeedingFollowupJson = json_encode($leadsNeedingFollowup);
?>

<div class="container mt-4">
    <h2>HR Dashboard</h2>
    
    <!-- Stats Cards -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Employees</h5>
                    <p class="card-text display-4"><?= $employeeCount ?></p>
                    <a href="employees.php" class="text-white">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pending Leaves</h5>
                    <p class="card-text display-4"><?= $activeLeaves ?></p>
                    <a href="leaves.php" class="text-white">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pending Salaries</h5>
                    <p class="card-text display-4"><?= $pendingSalaries ?></p>
                    <a href="salaries.php" class="text-white">Process</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Today's Followups</h5>
                    <p class="card-text display-4"><?= $followupsToday ?></p>
                    <a href="followups.php" class="text-white">View</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Recent Activities</h5>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php
                $stmt = $pdo->query("
                    (SELECT 'leave' as type, l.id, CONCAT('Leave request from ', e.name) as description, l.created_at 
                     FROM leaves l
                     JOIN employees e ON l.employee_id = e.id
                     ORDER BY l.created_at DESC LIMIT 3)
                    UNION
                    (SELECT 'attendance' as type, a.id, CONCAT('Attendance marked by ', e.name) as description, a.check_in as created_at 
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     ORDER BY a.check_in DESC LIMIT 3)
                    UNION
                    (SELECT 'followup' as type, f.id, CONCAT('Followup with client ', c.name) as description, f.created_at 
                     FROM client_followups f
                     JOIN clients c ON f.client_id = c.id
                     ORDER BY f.created_at DESC LIMIT 3)
                    ORDER BY created_at DESC LIMIT 5
                ");

                while ($activity = $stmt->fetch()):
                ?>
                <li class="list-group-item">
                    <span class="badge bg-<?= 
                        $activity['type'] === 'leave' ? 'warning' : 
                        ($activity['type'] === 'attendance' ? 'info' : 'success')
                    ?>">
                        <?= ucfirst($activity['type']) ?>
                    </span>
                    <?= $activity['description'] ?>
                    <small class="text-muted float-right"><?= timeAgo($activity['created_at']) ?></small>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>


<!-- Followup Reminder Modal -->
<div class="modal fade" id="followupReminderModal" tabindex="-1" aria-labelledby="followupReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="followupReminderModalLabel">
                    <i class="fas fa-bell me-2"></i>Followup Reminders
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reminderModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Checking for followups...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="reminderViewClientsButton" style="display:none;">
                    <i class="fas fa-users me-1"></i> View Client Followups
                </button>
                <button type="button" class="btn btn-info" id="reminderViewLeadsButton" style="display:none;">
                    <i class="fas fa-user-plus me-1"></i> View Leads
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="reminderSound" preload="auto">
    <source src="../assets/sound/annoucement.mp3" type="audio/mpeg">
</audio>

<script>
$(document).ready(function() {
    const urgentFollowups = <?= $urgentFollowupsJson ?>;
    const leadsNeedingFollowup = <?= $leadsNeedingFollowupJson ?>;
    const reminderModal = new bootstrap.Modal(document.getElementById('followupReminderModal'));
    const reminderSound = document.getElementById('reminderSound');
    
    // Set the View buttons
    $('#reminderViewClientsButton').click(function() {
        window.location.href = 'followups.php';
    });
    
    $('#reminderViewLeadsButton').click(function() {
        window.location.href = 'leads.php';
    });
    
    // Function to show followup reminders
    function showFollowupReminders() {
        if (urgentFollowups.length > 0 || leadsNeedingFollowup.length > 0) {
            let content = '';
            
            // Show urgent followups first
            if (urgentFollowups.length > 0) {
                content += `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You have ${urgentFollowups.length} urgent followup(s)
                    </div>
                `;
                
                urgentFollowups.forEach(followup => {
                    const followupTime = new Date(followup.followup_date);
                    const timeString = followupTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    const dateString = followupTime.toLocaleDateString();
                    
                    const status = followup.minutes_remaining < 0 ? 
                        `<span class="badge bg-danger">OVERDUE by ${Math.abs(followup.minutes_remaining)} minutes</span>` : 
                        `<span class="badge bg-primary">Due in ${followup.minutes_remaining} minutes</span>`;
                    
                    const name = followup.type === 'client' ? followup.client_name : followup.lead_name;
                    const phone = followup.type === 'client' ? followup.client_phone : followup.lead_phone;
                    const viewLink = followup.type === 'client' ? 
                        `followups.php?id=${followup.client_id}` : 
                        `leads.php?id=${followup.lead_id}`;
                    const addLink = followup.type === 'client' ? 
                        `followups.php?client_id=${followup.client_id}` : 
                        `View_lead.php?id=${followup.lead_id}`;
                    
                    content += `
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title">${name}</h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <i class="fas fa-phone me-1"></i> ${phone}
                                            <span class="badge bg-${followup.type === 'client' ? 'info' : 'secondary'} ms-2">
                                                ${followup.type === 'client' ? 'Client' : 'Lead'}
                                            </span>
                                        </h6>
                                    </div>
                                    ${status}
                                </div>
                                <div class="mt-2">
                                    <p class="card-text"><strong>Scheduled:</strong> ${dateString} at ${timeString}</p>
                                    <p class="card-text"><strong>Notes:</strong> ${followup.notes || 'No notes available'}</p>
                                </div>
                                <div class="mt-3">
                                    <a href="${viewLink}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user me-1"></i> View ${followup.type === 'client' ? 'Client' : 'Lead'}
                                    </a>
                                    <a href="${addLink}" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-plus me-1"></i> Add Followup
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            // Show leads needing followup
            if (leadsNeedingFollowup.length > 0) {
                content += `
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-user-clock me-2"></i>
                        You have ${leadsNeedingFollowup.length} lead(s) needing followup
                    </div>
                `;
                
                leadsNeedingFollowup.forEach(lead => {
                    const lastFollowup = lead.last_followup ? 
                        new Date(lead.last_followup).toLocaleDateString() : 
                        'Never';
                    
                    content += `
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title">${lead.name}</h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <i class="fas fa-phone me-1"></i> ${lead.phone}
                                            <span class="badge bg-secondary ms-2">Lead</span>
                                        </h6>
                                    </div>
                                    <span class="badge bg-danger">
                                        ${lead.last_followup ? lead.days_since_followup + ' days since last' : 'No followups'}
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <p class="card-text"><strong>Status:</strong> 
                                        <span class="badge bg-${ 
                                            lead.status === 'interested' ? 'primary' : 
                                            (lead.status === 'pending' ? 'warning' : 'info')
                                        }">
                                            ${lead.status.charAt(0).toUpperCase() + lead.status.slice(1)}
                                        </span>
                                    </p>
                                    <p class="card-text"><strong>Last Followup:</strong> ${lastFollowup}</p>
                                </div>
                                <div class="mt-3">
                                    <a href="view_lead.php?id=${lead.id}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user me-1"></i> View Lead
                                    </a>
                                    <a href="add_lead_followup.php?lead_id=${lead.id}" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-plus me-1"></i> Add Followup
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            $('#reminderModalContent').html(content);
            
            // Show appropriate buttons
            if (urgentFollowups.length > 0 && leadsNeedingFollowup.length > 0) {
                $('#reminderViewClientsButton').show();
                $('#reminderViewLeadsButton').show();
            } else if (urgentFollowups.length > 0) {
                $('#reminderViewClientsButton').show();
                $('#reminderViewLeadsButton').hide();
            } else {
                $('#reminderViewClientsButton').hide();
                $('#reminderViewLeadsButton').show();
            }
            
            // Play sound and show modal
            reminderSound.play().catch(e => console.log("Audio play failed:", e));
            reminderModal.show();
            
            // Set timeout to check again in 5 minutes
            setTimeout(checkForNewFollowups, 300000);
        } else {
            // No urgent followups now, check again in 5 minutes
            setTimeout(checkForNewFollowups, 300000);
        }
    }
    
    // Function to check for new followups via AJAX
    function checkForNewFollowups() {
        $.ajax({
            url: '../api/check_followups.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.has_new_followups || response.has_leads_needing_followup) {
                    // Refresh the page to show new followups
                    location.reload();
                } else {
                    // Check again in 5 minutes
                    setTimeout(checkForNewFollowups, 300000);
                }
            }
        });
    }
    
    // Initial check for reminders
    showFollowupReminders();
    
    // Also check when the page becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForNewFollowups();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>