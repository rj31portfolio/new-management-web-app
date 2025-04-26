<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isEmployee()) {
    redirect('../employee/login.php', 'You must be logged in as an employee', 'error');
}

$pdo = getDatabase();

// Handle check in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        $location = sanitize($_POST['location'] ?? 'Location not available');

        $stmt = $pdo->prepare("
            INSERT INTO attendance (employee_id, check_in, check_in_location, status)
            VALUES (?, NOW(), ?, 'present')
        ");
        $stmt->execute([$_SESSION['user_id'], $location]);

        redirect('attendance.php', 'Checked in successfully', 'success');
    } elseif (isset($_POST['check_out'])) {
        $location = sanitize($_POST['location'] ?? 'Location not available');

        $stmt = $pdo->prepare("
            SELECT id FROM attendance 
            WHERE employee_id = ? AND DATE(check_in) = CURDATE() 
            ORDER BY check_in DESC LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $attendance = $stmt->fetch();

        if ($attendance) {
            $stmt = $pdo->prepare("
                UPDATE attendance 
                SET check_out = NOW(), check_out_location = ? 
                WHERE id = ?
            ");
            $stmt->execute([$location, $attendance['id']]);
            redirect('attendance.php', 'Checked out successfully', 'success');
        } else {
            redirect('attendance.php', 'No check-in record found for today', 'error');
        }
    }
}

$stmt = $pdo->prepare("
    SELECT id, check_in, check_out 
    FROM attendance 
    WHERE employee_id = ? AND DATE(check_in) = CURDATE() 
    ORDER BY check_in DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$todayRecord = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = ? 
    ORDER BY check_in DESC 
    LIMIT 30
");
$stmt->execute([$_SESSION['user_id']]);
$attendanceHistory = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2>My Attendance</h2>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Today's Status</h5>
        </div>
        <div class="card-body">
            <?php if ($todayRecord): ?>
                <?php if ($todayRecord['check_out']): ?>
                    <div class="alert alert-success">
                        You checked in at <?php echo date('h:i A', strtotime($todayRecord['check_in'])); ?> 
                        and checked out at <?php echo date('h:i A', strtotime($todayRecord['check_out'])); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You checked in at <?php echo date('h:i A', strtotime($todayRecord['check_in'])); ?>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="check_out" value="1">
                            <input type="hidden" name="location" id="check_out_location">
                            <button type="submit" class="btn btn-danger">Check Out</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="check_in" value="1">
                    <input type="hidden" name="location" id="check_in_location">
                    <button type="submit" class="btn btn-primary">Check In</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Attendance History (Last 30 Days)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check In Location</th>
                            <th>Check Out</th>
                            <th>Check Out Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceHistory as $record): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($record['check_in'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($record['check_in'])); ?></td>
                            
                            <td>
                                <?php echo htmlspecialchars($record['check_in_location'] ?? '--'); ?>
                                <?php 
                                    preg_match('/Lat:\s*(-?\d+\.\d+),\s*Long:\s*(-?\d+\.\d+)/', $record['check_in_location'], $matches);
                                    if (isset($matches[1], $matches[2])): 
                                        $lat = $matches[1];
                                        $long = $matches[2];
                                ?>
                                    <div class="mt-2">
                                        <iframe
                                            width="200"
                                            height="150"
                                            style="border:0;"
                                            loading="lazy"
                                            allowfullscreen
                                            referrerpolicy="no-referrer-when-downgrade"
                                            src="https://www.google.com/maps?q=<?php echo $lat; ?>,<?php echo $long; ?>&output=embed">
                                        </iframe>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td><?php echo $record['check_out'] ? date('h:i A', strtotime($record['check_out'])) : '--'; ?></td>
                            
                            <td>
                                <?php echo htmlspecialchars($record['check_out_location'] ?? '--'); ?>
                                <?php 
                                    preg_match('/Lat:\s*(-?\d+\.\d+),\s*Long:\s*(-?\d+\.\d+)/', $record['check_out_location'], $matches);
                                    if (isset($matches[1], $matches[2])): 
                                        $lat = $matches[1];
                                        $long = $matches[2];
                                ?>
                                    <div class="mt-2">
                                        <iframe
                                            width="200"
                                            height="150"
                                            style="border:0;"
                                            loading="lazy"
                                            allowfullscreen
                                            referrerpolicy="no-referrer-when-downgrade"
                                            src="https://www.google.com/maps?q=<?php echo $lat; ?>,<?php echo $long; ?>&output=embed">
                                        </iframe>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge badge-<?php 
                                    echo $record['status'] === 'present' ? 'success' : 
                                         ($record['status'] === 'absent' ? 'danger' : 
                                         ($record['status'] === 'late' ? 'warning' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Location capture script -->
<script>
function getLocation(callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const location = `Lat: ${position.coords.latitude}, Long: ${position.coords.longitude}`;
                callback(location);
            },
            function(error) {
                console.error("Error getting location:", error);
                callback("Location not available");
            }
        );
    } else {
        callback("Location not supported");
    }
}

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const locationField = form.querySelector('input[name="location"]');
        const isCheckIn = form.querySelector('input[name="check_in"]');
        const isCheckOut = form.querySelector('input[name="check_out"]');

        let confirmMessage = '';
        if (isCheckIn) confirmMessage = "Are you sure you want to Check In?";
        else if (isCheckOut) confirmMessage = "Are you sure you want to Check Out?";

        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return;
        }

        if (locationField && !locationField.value) {
            e.preventDefault();
            getLocation(function(loc) {
                locationField.value = loc;
                form.submit();
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
