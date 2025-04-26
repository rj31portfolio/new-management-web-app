<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

$pdo = getDatabase();

// Handle attendance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['manual_entry'])) {
        $employeeId = sanitize($_POST['employee_id']);
        $date = sanitize($_POST['date']);
        $checkIn = sanitize($_POST['check_in']);
        $checkOut = sanitize($_POST['check_out']);
        $status = sanitize($_POST['status']);
        $checkInLocation = sanitize($_POST['check_in_location'] ?? 'Location not available');
        $checkOutLocation = sanitize($_POST['check_out_location'] ?? 'Location not available');

        $stmt = $pdo->prepare("
            INSERT INTO attendance (employee_id, check_in, check_out, status, check_in_location, check_out_location) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $employeeId,
            "$date $checkIn",
            $checkOut ? "$date $checkOut" : null,
            $status,
            $checkInLocation,
            $checkOut ? $checkOutLocation : null
        ]);

        redirect('attendance.php', 'Attendance record added successfully', 'success');
    } elseif (isset($_POST['edit_entry'])) {
        $attendanceId = sanitize($_POST['attendance_id']);
        $employeeId = sanitize($_POST['employee_id']);
        $date = sanitize($_POST['date']);
        $checkIn = sanitize($_POST['check_in']);
        $checkOut = sanitize($_POST['check_out']);
        $status = sanitize($_POST['status']);
        $checkInLocation = sanitize($_POST['check_in_location'] ?? 'Location not available');
        $checkOutLocation = sanitize($_POST['check_out_location'] ?? 'Location not available');

        $stmt = $pdo->prepare("
            UPDATE attendance 
            SET employee_id = ?, check_in = ?, check_out = ?, status = ?, 
                check_in_location = ?, check_out_location = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $employeeId,
            "$date $checkIn",
            $checkOut ? "$date $checkOut" : null,
            $status,
            $checkInLocation,
            $checkOut ? $checkOutLocation : null,
            $attendanceId
        ]);

        redirect('attendance.php', 'Attendance record updated successfully', 'success');
    }
}

// Get all attendance records
$stmt = $pdo->query("
    SELECT a.*, e.name as employee_name 
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    ORDER BY a.check_in DESC
");
$attendance = $stmt->fetchAll();

// Get all employees for dropdown
$employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll();
?>

<div class="container mt-4">
    <h2>Employee Attendance</h2>
    
    <div class="card mt-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#view" data-toggle="tab">View Attendance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#add" data-toggle="tab">Manual Entry</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="view">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check In Location</th>
                                    <th>Check Out</th>
                                    <th>Check Out Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($record['check_in'])); ?></td>
                                    <td><?php echo date('H:i:s', strtotime($record['check_in'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($record['check_in_location'] ?? '--'); ?>
                                        <?php 
                                        preg_match('/Lat:\s*(-?\d+\.\d+),\s*Long:\s*(-?\d+\.\d+)/', $record['check_in_location'], $matches);
                                        if (isset($matches[1], $matches[2])): 
                                            $lat = $matches[1];
                                            $long = $matches[2];
                                        ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#mapModal" 
                                                        data-lat="<?php echo $lat; ?>" data-long="<?php echo $long; ?>">
                                                    View Map
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $record['check_out'] ? date('H:i:s', strtotime($record['check_out'])) : '--'; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($record['check_out_location'] ?? '--'); ?>
                                        <?php 
                                        preg_match('/Lat:\s*(-?\d+\.\d+),\s*Long:\s*(-?\d+\.\d+)/', $record['check_out_location'], $matches);
                                        if (isset($matches[1], $matches[2])): 
                                            $lat = $matches[1];
                                            $long = $matches[2];
                                        ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#mapModal" 
                                                        data-lat="<?php echo $lat; ?>" data-long="<?php echo $long; ?>">
                                                    View Map
                                                </button>
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
                                    <td>
                                        <button class="btn btn-sm btn-info edit-btn" 
                                                data-toggle="modal" 
                                                data-target="#editModal"
                                                data-id="<?php echo $record['id']; ?>"
                                                data-employee-id="<?php echo $record['employee_id']; ?>"
                                                data-date="<?php echo date('Y-m-d', strtotime($record['check_in'])); ?>"
                                                data-check-in="<?php echo date('H:i', strtotime($record['check_in'])); ?>"
                                                data-check-out="<?php echo $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : ''; ?>"
                                                data-status="<?php echo $record['status']; ?>"
                                                data-check-in-location="<?php echo htmlspecialchars($record['check_in_location'] ?? ''); ?>"
                                                data-check-out-location="<?php echo htmlspecialchars($record['check_out_location'] ?? ''); ?>">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="add">
                    <form method="POST" id="attendanceForm">
                        <input type="hidden" name="manual_entry" value="1">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="employee_id">Employee</label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="date">Date</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="check_in">Check In Time</label>
                                <input type="time" class="form-control" id="check_in" name="check_in" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="check_in_location">Check In Location</label>
                                <input type="text" class="form-control" id="check_in_location" name="check_in_location" 
                                       placeholder="Enter or capture location">
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="captureLocation('check_in_location')">
                                    Capture Current Location
                                </button>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="check_out">Check Out Time</label>
                                <input type="time" class="form-control" id="check_out" name="check_out">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="check_out_location">Check Out Location</label>
                                <input type="text" class="form-control" id="check_out_location" name="check_out_location" 
                                       placeholder="Enter or capture location">
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="captureLocation('check_out_location')">
                                    Capture Current Location
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map Modal -->
<div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapModalLabel">Location Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe id="mapFrame" width="100%" height="400" style="border:0;" loading="lazy" 
                        allowfullscreen referrerpolicy="no-referrer-when-downgrade" src="">
                </iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Attendance Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editAttendanceForm">
                    <input type="hidden" name="edit_entry" value="1">
                    <input type="hidden" name="attendance_id" id="edit_attendance_id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_employee_id">Employee</label>
                            <select class="form-control" id="edit_employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_date">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_check_in">Check In Time</label>
                            <input type="time" class="form-control" id="edit_check_in" name="check_in" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_check_in_location">Check In Location</label>
                            <input type="text" class="form-control" id="edit_check_in_location" name="check_in_location" 
                                   placeholder="Enter or capture location">
                            <button type="button" class="btn btn-sm btn-info mt-2" onclick="captureLocation('edit_check_in_location')">
                                Capture Current Location
                            </button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_check_out">Check Out Time</label>
                            <input type="time" class="form-control" id="edit_check_out" name="check_out">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_check_out_location">Check Out Location</label>
                            <input type="text" class="form-control" id="edit_check_out_location" name="check_out_location" 
                                   placeholder="Enter or capture location">
                            <button type="button" class="btn btn-sm btn-info mt-2" onclick="captureLocation('edit_check_out_location')">
                                Capture Current Location
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Attendance</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Location capture and modal handling script -->
<script>
function captureLocation(fieldId) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const location = `Lat: ${position.coords.latitude}, Long: ${position.coords.longitude}`;
                document.getElementById(fieldId).value = location;
            },
            function(error) {
                console.error("Error getting location:", error);
                document.getElementById(fieldId).value = "Location not available";
            }
        );
    } else {
        document.getElementById(fieldId).value = "Location not supported";
    }
}

document.getElementById('attendanceForm').addEventListener('submit', function(e) {
    if (!confirm("Are you sure you want to save this attendance record?")) {
        e.preventDefault();
    }
});

document.getElementById('editAttendanceForm').addEventListener('submit', function(e) {
    if (!confirm("Are you sure you want to update this attendance record?")) {
        e.preventDefault();
    }
});

// Handle map modal
$('#mapModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const lat = button.data('lat');
    const long = button.data('long');
    const mapFrame = document.getElementById('mapFrame');
    mapFrame.src = `https://www.google.com/maps?q=${lat},${long}&output=embed`;
});

// Handle edit modal
$('.edit-btn').on('click', function() {
    const button = $(this);
    $('#edit_attendance_id').val(button.data('id'));
    $('#edit_employee_id').val(button.data('employee-id'));
    $('#edit_date').val(button.data('date'));
    $('#edit_check_in').val(button.data('check-in'));
    $('#edit_check_out').val(button.data('check-out'));
    $('#edit_status').val(button.data('status'));
    $('#edit_check_in_location').val(button.data('check-in-location'));
    $('#edit_check_out_location').val(button.data('check-out-location'));
});
</script>

<?php include '../includes/footer.php'; ?>