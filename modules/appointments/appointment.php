<?php
require '../../components/db.php';

/* ============================
   DATE RANGE CALCULATION
============================ */
$minDate = date('Y-m-d'); // Today
$maxDate = date('Y-m-d', strtotime('+6 months')); // 6 months from today
$minBlockedDate = date('Y-m-d', strtotime('+1 day')); // Tomorrow (only future dates can be blocked)
$maxBlockedDate = date('Y-m-d', strtotime('+1 year')); // 1 year from today for blocked dates

/* ============================
   FILTERS
============================ */

$status_filter = $_GET['status'] ?? '';
$month_filter = $_GET['month'] ?? '';
$doctor_filter = $_GET['doctor'] ?? '';
$time_filter = $_GET['time'] ?? '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

$where = [];
$params = [];

// Default: exclude archived unless specifically viewing archived
if (!$show_archived) {
    $where[] = "a.is_archived = 0";
} else {
    $where[] = "a.is_archived = 1";
}

if ($status_filter) {
    $where[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($month_filter) {
    $where[] = "MONTH(a.appointment_date) = ?";
    $params[] = $month_filter;
}

if ($doctor_filter) {
    $where[] = "a.doctor_id = ?";
    $params[] = (int)$doctor_filter;
}

if ($time_filter) {
    // Extract start time from range if needed
    $timeToFilter = $time_filter;
    if (strpos($time_filter, '-') !== false) {
        $timeRange = explode('-', $time_filter);
        $timeToFilter = trim($timeRange[0]);
    }
    $where[] = "TIME_FORMAT(a.appointment_time, '%H:%i') = ?";
    $params[] = $timeToFilter;
}

$where_sql = "WHERE " . implode(" AND ", $where);

/* ============================
   FETCH APPOINTMENTS
============================ */

$stmt = $pdo->prepare("
    SELECT a.*, 
           p.first_name AS p_fname, p.last_name AS p_lname,
           d.first_name AS d_fname, d.last_name AS d_lname
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    $where_sql
    ORDER BY a.appointment_date DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   FETCH PATIENTS & DOCTORS
============================ */

$patients = $pdo->query("SELECT patient_id, first_name, last_name FROM patients WHERE status = 'active' ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query("SELECT * FROM doctors WHERE is_archived = 0 ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// No longer fetching all doctor availability here - it will be requested via AJAX when needed

function timeSlots() {
    return [
        "10:00",
        "11:00",
        "12:00",
        "13:00",
        "14:00",
        "15:00",
        "16:00",
        "17:00",
        "18:00"
    ];
}

function formatTimeSlot($timeStr) {
    // Convert stored time (e.g., "10:00") to hourly range format with 12-hour time
    if (strpos($timeStr, '-') !== false) {
        return $timeStr; // Already in range format
    }
    // Extract hour from time string
    $hour = (int)substr($timeStr, 0, 2);
    $startTime = date('g:ia', strtotime($timeStr));
    $endHour = $hour + 1;
    $endTime = date('g:ia', strtotime(str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':00'));
    return $startTime . ' - ' . $endTime;
}

function convertTo12Hour($time24) {
    // Convert 24-hour format to 12-hour format (e.g., "13:00" -> "1:00pm")
    return date('g:ia', strtotime($time24));
}

function getTimeRangeDisplay($time24) {
    // Convert 24-hour time to 12-hour range format (e.g., "13:00" -> "1:00pm - 2:00pm")
    $hour = (int)substr($time24, 0, 2);
    $startTime = convertTo12Hour($time24);
    $endHour = str_pad($hour + 1, 2, '0', STR_PAD_LEFT);
    $endTime = convertTo12Hour($endHour . ':00');
    return $startTime . ' - ' . $endTime;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-size: 16px; }
        .table th { font-size: 16px; }
        .table td { font-size: 15px; }
        .btn-sm { padding: 6px 12px; }
        
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        
        .dataTables_wrapper .row:last-child {
             display: flex;
    align-items: center;
}
        
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        #appointmentsTable {
    table-layout: fixed;
    width: 100%;
}

#appointmentsTable td {
    word-wrap: break-word;
    overflow-wrap: break-word;
}
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        #blockedDate.is-invalid,
        #blockedReason.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        #blockedDate.is-invalid:focus,
        #blockedReason.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        
        #appointmentReason.is-invalid,
        #appointmentReason.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>


<div class="content-wrapper p-4">
<div class="container-fluid">

<div class="card shadow-lg mt-4">
<div class="card-body">

<h4 class="fw-bold mb-4 d-flex justify-content-between align-items-center">
    <span>Appointments</span>
    <div>
        
        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#blockedDatesModal">
            Manage Blocked Dates
        </button>
        <button id="showAppointmentBtn" class="btn btn-primary me-2">+ Schedule Appointment</button>
    </div>
</h4>

<!-- Display Messages -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✓ Appointment saved successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✓ Appointment updated successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['archived'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        ✓ Appointment archived successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['restored'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        ✓ Appointment restored successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['permanently_deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ✓ Appointment permanently deleted
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ✓ Appointment deleted
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        ⚠️ <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- FILTERS -->
<div id="filterContainer" class="mb-3">
<form method="GET" class="row g-2 mb-4">

<div class="col-md-auto">
<select name="month" class="form-select form-select-sm">
<option value="">Month</option>
<?php for ($m=1; $m<=12; $m++): ?>
<option value="<?= $m ?>" <?= $month_filter == $m ? 'selected' : '' ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-auto">
<select name="time" class="form-select form-select-sm">
<option value="">Time</option>
<?php foreach (timeSlots() as $time): ?>
<option value="<?= $time ?>" <?= $time_filter === $time ? 'selected' : '' ?>><?= getTimeRangeDisplay($time) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-auto">
<select name="doctor" class="form-select form-select-sm">
<option value="">Doctor</option>
<?php foreach ($doctors as $doc): ?>
<option value="<?= $doc['doctor_id'] ?>" <?= $doctor_filter == $doc['doctor_id'] ? 'selected' : '' ?>>
Dr. <?= $doc['last_name'] ?>, <?= $doc['first_name'] ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-auto">
<select name="status" class="form-select form-select-sm">
<option value="">Status</option>
<option value="Scheduled" <?= $status_filter === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
<option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
<option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>

<div class="col-md-auto">
<select name="show_archived" class="form-select form-select-sm">
<option value="0" <?= !$show_archived ? 'selected' : '' ?>>Active</option>
<option value="1" <?= $show_archived ? 'selected' : '' ?>>Archived</option>
</select>
</div>

<div class="col-md-auto">
<button class="btn btn-primary btn-sm" type="submit" style="display:none;">Filter</button>
</div>

<div class="col-md-auto">
<a href="appointment.php" class="btn btn-secondary btn-sm">Reset</a>
</div>
</form>
</div>

<!-- APPOINTMENT TABLE -->
<table id="appointmentsTable" class="table table-striped table-hover table-bordered align-middle">
    
<thead class="table-dark">
<tr>
<th>Appointment ID</th>
<th>Date</th>
<th>Time</th>
<th>Patient</th>
<th>Doctor</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>

<?php foreach ($appointments as $a): ?>
<tr>
<td><?= $a['appointment_id'] ?></td>
<td><?= date("m/d/Y", strtotime($a['appointment_date'])) ?></td>
<td><?= formatTimeSlot(date("H:i", strtotime($a['appointment_time']))) ?></td>
<td><?= $a['p_lname'] ?>, <?= $a['p_fname'] ?></td>
<td>Dr. <?= $a['d_lname'] ?></td>
<td>
<span class="badge 
<?= $a['status']=='Scheduled'?'bg-primary':
   ($a['status']=='Completed'?'bg-success':'bg-danger') ?>">
<?= $a['status'] ?>
</span>
</td>
<td>
<?php if (!$show_archived): ?>
<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewAppointmentModal" onclick="loadAppointmentDetails(<?= $a['appointment_id'] ?>, <?= $a['patient_id'] ?>, <?= $a['doctor_id'] ?>, '<?= $a['appointment_date'] ?>', '<?= $a['appointment_time'] ?>', '<?= $a['status'] ?>')">View</button>
<a href="<?= htmlspecialchars(appUrl('/modules/appointments/appointment_archive_handler.php?action=archive&id=' . (int) $a['appointment_id'])) ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Archive this appointment?');">Archive</a>
<?php else: ?>
<a href="<?= htmlspecialchars(appUrl('/modules/appointments/appointment_archive_handler.php?action=restore&id=' . (int) $a['appointment_id'])) ?>" class="btn btn-info btn-sm" onclick="return confirm('Are you sure you want to restore this appointment?');">Restore</a>
<a href="<?= htmlspecialchars(appUrl('/modules/appointments/appointment_archive_handler.php?action=permanently_delete&id=' . (int) $a['appointment_id'])) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Permanently delete? This cannot be undone.');">Delete</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<!-- ADD APPOINTMENT FORM (modal) -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Schedule Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

<form action="<?= htmlspecialchars(appUrl('/modules/appointments/save_appointment.php')) ?>" method="POST">

<div class="row g-3">

<?php
// Get list of patients with scheduled appointments
$stmt = $pdo->prepare("
    SELECT DISTINCT patient_id FROM appointments 
    WHERE status = 'Scheduled' AND is_archived = 0
");
$stmt->execute();
$patientsWithAppointments = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="col-md-3">
<label>Patient</label>
<select id="patientSelect" name="patient_id" class="form-select" required>
<option value="">Select Patient</option>
<?php foreach ($patients as $p): ?>
    <?php $hasAppointment = in_array($p['patient_id'], $patientsWithAppointments); ?>
    <option value="<?= $p['patient_id'] ?>" <?= $hasAppointment ? 'disabled' : '' ?>>
    <?= $p['last_name'] ?>, <?= $p['first_name'] ?>
    <?= $hasAppointment ? ' [Has Scheduled Appointment]' : '' ?>
    </option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Doctor</label>
<select id="doctorSelect" name="doctor_id" class="form-select" required>
<option value="">Select Doctor</option>
<?php foreach ($doctors as $doc): ?>
<option value="<?= $doc['doctor_id'] ?>" data-doctor-id="<?= $doc['doctor_id'] ?>">
Dr. <?= $doc['last_name'] ?>, <?= $doc['first_name'] ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Date</label>
<input type="date" id="appointmentDate" name="appointment_date" class="form-control" min="<?= $minDate ?>" max="<?= $maxDate ?>" required>
<div class="error-message" id="dateError"></div>
</div>

<div class="col-md-3">
<label>Time</label>
<select id="appointmentTime" name="appointment_time" class="form-select" required>
<option value="">Select Time</option>
<?php foreach (timeSlots() as $time): ?>
<option value="<?= $time ?>"><?= getTimeRangeDisplay($time) ?></option>
<?php endforeach; ?>
</select>
<div class="error-message" id="timeError"></div>
</div>

<div class="col-md-3">
<label>Status</label>
<input type="text" class="form-control" value="Scheduled" disabled>
<input type="hidden" name="status" value="Scheduled">
</div>

<div class="col-md-12">
<label>Reason (Max 100 characters)</label>
<textarea id="appointmentReason" name="reason" class="form-control" maxlength="100" placeholder="Enter reason for appointment" oninput="validateAppointmentReason()" onkeyup="validateAppointmentReason()"></textarea>
<div class="d-flex justify-content-between align-items-center">
<small class="text-danger d-none" id="appointmentReasonError"></small>
<small class="text-muted" id="reasonCharCount">0/100</small>
</div>
</div>

<div class="col-md-12">
<button class="btn btn-success">Save Appointment</button>
</div>

</div>
</form>

      </div>
    </div>
  </div>
</div>

<!-- VIEW APPOINTMENT MODAL -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title fw-bold">Appointment Details</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="appointmentDetailsContent">
          <!-- Content will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="completeAppointmentBtn" onclick="completeAppointmentFromModal()">Complete</button>
        <button type="button" class="btn btn-danger" id="cancelAppointmentBtn" onclick="cancelAppointmentFromModal()">Cancel</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

</div>
</div>


<!-- ================= VISIT MODAL (Complete Appointment) ================= -->

<div class="modal fade" id="visitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Complete Appointment - Record Visit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

<form id="completeVisitForm">

<input type="hidden" id="appointmentId" name="appointment_id">
<input type="hidden" id="patientId" name="patient_id">
<input type="hidden" id="doctorId" name="doctor_id">

<div class="row g-3">

<div class="col-md-6">
    <label class="form-label">Visit Date <span class="text-danger">*</span></label>
    <input type="date" id="visitDate" class="form-control" disabled>
    <input type="hidden" id="visitDateHidden" name="visit_date" required>
    <small class="text-danger d-none" data-error="visit_date"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Visit Time (Hourly Slot) <span class="text-danger">*</span></label>
    <select id="visitTime" class="form-select" disabled>
        <option value="">Select Hour</option>
        <option value="10">10:00 AM - 11:00 AM</option>
        <option value="11">11:00 AM - 12:00 PM</option>
        <option value="12">12:00 PM - 1:00 PM</option>
        <option value="13">1:00 PM - 2:00 PM</option>
        <option value="14">2:00 PM - 3:00 PM</option>
        <option value="15">3:00 PM - 4:00 PM</option>
        <option value="16">4:00 PM - 5:00 PM</option>
        <option value="17">5:00 PM - 6:00 PM</option>
        <option value="18">6:00 PM - 7:00 PM</option>
    </select>
    <input type="hidden" id="visitTimeHidden" name="visit_time" required>
    <small class="text-danger d-none" data-error="visit_time"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Nature of Visit <span class="text-danger">*</span></label>
    <input type="text" name="nature_of_visit" class="form-control" placeholder="e.g., Consultation, Follow-up" maxlength="100" required>
    <small class="text-danger d-none" data-error="nature_of_visit"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Affected Area</label>
    <input type="text" name="affected_area" class="form-control" placeholder="e.g., Left knee, Head" maxlength="100">
    <small class="text-danger d-none" data-error="affected_area"></small>
</div>

<div class="col-md-12">
    <label class="form-label">Symptoms</label>
    <textarea name="symptoms" class="form-control" rows="2" placeholder="Describe symptoms..."></textarea>
    <small class="text-danger d-none" data-error="symptoms"></small>
</div>

<div class="col-md-12">
    <label class="form-label">Observation</label>
    <textarea name="observation" class="form-control" rows="2" placeholder="Doctor's observation..."></textarea>
    <small class="text-danger d-none" data-error="observation"></small>
</div>

<div class="col-md-12">
    <label class="form-label">Procedure Done</label>
    <textarea name="procedure_done" class="form-control" rows="2" placeholder="Any procedure performed..."></textarea>
    <small class="text-danger d-none" data-error="procedure_done"></small>
</div>

<div class="col-md-12">
    <label class="form-label">Medications Prescribed</label>
    <textarea name="meds_prescribed" class="form-control" rows="2" placeholder="e.g., Aspirin 500mg..."></textarea>
    <small class="text-danger d-none" data-error="meds_prescribed"></small>
</div>

<div class="col-md-12">
    <label class="form-label">Instructions to Patient</label>
    <textarea name="instruction_to_patient" class="form-control" rows="2" placeholder="e.g., Rest for 2 days, take medication thrice daily..."></textarea>
    <small class="text-danger d-none" data-error="instruction_to_patient"></small>
</div>

<div class="col-md-12">
    <label class="form-label">Remarks</label>
    <textarea name="remarks" class="form-control" rows="2" placeholder="Additional remarks..."></textarea>
    <small class="text-danger d-none" data-error="remarks"></small>
</div>

</div>
</form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="completeVisitForm" class="btn btn-success">Complete Visit & Mark Appointment Done</button>
      </div>
    </div>
  </div>
</div>

</div>
</div>


<script>
// Store current appointment data for modal actions
let currentAppointmentData = {};

// Load and display appointment details
function loadAppointmentDetails(appointmentId, patientId, doctorId, appointmentDate, appointmentTime, status) {
    currentAppointmentData = {
        appointmentId: appointmentId,
        patientId: patientId,
        doctorId: doctorId,
        appointmentDate: appointmentDate,
        appointmentTime: appointmentTime,
        status: status
    };
    displayAppointmentDetails(appointmentId);
}

function displayAppointmentDetails(appointmentId) {
    // Get the appointment row data
    const row = document.querySelector(`button[onclick*="loadAppointmentDetails(${appointmentId}"]`).closest('tr');
    if (!row) return;

    const cells = row.querySelectorAll('td');
    const date = cells[1].textContent.trim();
    const time = cells[2].textContent.trim();
    const patient = cells[3].textContent.trim();
    const doctor = cells[4].textContent.trim();
    const status = cells[5].textContent.trim();

    const detailsHtml = `
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Appointment ID</label>
                <p class="form-control-plaintext">${appointmentId}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Date</label>
                <p class="form-control-plaintext">${date}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Time</label>
                <p class="form-control-plaintext">${time}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Patient</label>
                <p class="form-control-plaintext">${patient}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Doctor</label>
                <p class="form-control-plaintext">${doctor}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <p class="form-control-plaintext">${status}</p>
            </div>
        </div>
    `;

    document.getElementById('appointmentDetailsContent').innerHTML = detailsHtml;
    
    // Show/hide buttons based on status
    const completeBtn = document.getElementById('completeAppointmentBtn');
    const cancelBtn = document.getElementById('cancelAppointmentBtn');
    
    if (status === 'Scheduled') {
        completeBtn.style.display = 'block';
        cancelBtn.style.display = 'block';
    } else {
        completeBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }
}

// Complete appointment - opens visit modal
function completeAppointmentFromModal() {
    const data = currentAppointmentData;
    openVisitModal(data.appointmentId, data.patientId, data.doctorId, data.appointmentDate, data.appointmentTime);
    // Close the view modal
    bootstrap.Modal.getInstance(document.getElementById('viewAppointmentModal')).hide();
}

// Cancel appointment - change status to Cancelled
function cancelAppointmentFromModal() {
    if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
    }
    
    const data = currentAppointmentData;
    const formData = new FormData();
    formData.append('appointment_id', data.appointmentId);
    formData.append('action', 'cancel');
    
    fetch('<?= htmlspecialchars(appUrl('/modules/appointments/cancel_appointment.php')) ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('viewAppointmentModal')).hide();
            alert('Appointment cancelled successfully');
            location.reload();
        } else {
            alert(result.message || 'Error cancelling appointment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error cancelling appointment');
    });
}

/* ========================================
   Industry-Standard Appointment Logic
   - AJAX-based dynamic availability
   - Server-side validation
   - Real-time UI feedback
======================================== */

const elements = {
    appointmentDate: document.getElementById('appointmentDate'),
    appointmentTime: document.getElementById('appointmentTime'),
    doctorSelect: document.getElementById('doctorSelect'),
    dateError: document.getElementById('dateError'),
    timeError: document.getElementById('timeError'),
};

let bookedTimes = [];

// Clear error states
function clearErrors() {
    elements.appointmentDate.classList.remove('is-invalid');
    elements.appointmentTime.classList.remove('is-invalid');
    elements.dateError.classList.remove('show');
    elements.timeError.classList.remove('show');
}

// Show date error
function showDateError(message) {
    elements.appointmentDate.classList.add('is-invalid');
    elements.dateError.textContent = message;
    elements.dateError.classList.add('show');
}

// Show time error
function showTimeError(message) {
    elements.appointmentTime.classList.add('is-invalid');
    elements.timeError.textContent = message;
    elements.timeError.classList.add('show');
}

// Validate appointment reason
function validateAppointmentReason() {
    const reasonInput = document.getElementById('appointmentReason');
    const errorElement = document.getElementById('appointmentReasonError');
    const charCount = document.getElementById('reasonCharCount');
    const text = reasonInput.value;

    // Update character count
    if (charCount) {
        charCount.textContent = text.length + '/100';
    }

    // Check if exceeds max length
    if (text.length > 100) {
        errorElement.textContent = 'Reason must be 100 characters or less';
        errorElement.classList.remove('d-none');
        reasonInput.classList.add('is-invalid');
        return false;
    } else {
        errorElement.classList.add('d-none');
        reasonInput.classList.remove('is-invalid');
        return true;
    }
}

// Display loading/error states
function showTimeLoadingState() {
    elements.appointmentTime.innerHTML = '<option value="">Loading times...</option>';
    elements.appointmentTime.disabled = true;
}

function showTimeErrorState(message) {
    elements.appointmentTime.innerHTML = `<option value="">⚠️ ${message}</option>`;
    elements.appointmentTime.disabled = true;
    showTimeError(message);
}

function showNoDoctorSelected() {
    elements.appointmentTime.innerHTML = '<option value="">Select doctor first</option>';
    elements.appointmentTime.disabled = true;
}

// Fetch available times from server
async function fetchAvailableTimes(doctorId, date) {
    try {
        clearErrors();
        showTimeLoadingState();

        const response = await fetch(
            `<?= htmlspecialchars(appUrl('/modules/appointments/get_doctor_availability.php')) ?>?doctor_id=${doctorId}&date=${date}`
        );

        const data = await response.json();

        // Check for errors in response
        if (data.error) {
            showTimeErrorState(data.error);
            return;
        }

        if (!data.available) {
            showDateError(data.message);
            elements.appointmentTime.innerHTML = '<option value="">Select Time</option>';
            elements.appointmentTime.disabled = true;
            elements.timeError.classList.remove('show');
            return;
        }

        // Date is available, clear date error if any
        elements.appointmentDate.classList.remove('is-invalid');
        elements.dateError.classList.remove('show');

        // Store booked times for validation
        bookedTimes = data.booked || [];

        // Populate time dropdown with available times
        let html = '<option value="">Select Time</option>';
        data.times.forEach(time => {
            // Normalize incoming values (e.g., HH:MM or HH:MM:SS) to HH:MM
            const startTime = String(time).slice(0, 5);
            const hour = parseInt(startTime.substring(0, 2));
            const endHour = String(hour + 1).padStart(2, '0');
            const endTime = endHour + ':00';
            
            // Convert to 12-hour format for display
            const startDate = new Date(`2000-01-01T${startTime}:00`);
            const endDate = new Date(`2000-01-01T${endTime}:00`);
            const startDisplay = startDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const endDisplay = endDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const rangeDisplay = startDisplay + ' - ' + endDisplay;
            
            html += `<option value="${time}">${rangeDisplay}</option>`;
        });

        elements.appointmentTime.innerHTML = html;
        elements.appointmentTime.disabled = false;
        elements.timeError.classList.remove('show');

    } catch (error) {
        console.error('Fetch error:', error);
        showTimeErrorState('Error loading times - check console');
    }
}

// Update available times when date or doctor changes
function handleAvailabilityUpdate() {
    const date = elements.appointmentDate.value;
    const doctorId = elements.doctorSelect.value;

    if (!date || !doctorId) {
        showNoDoctorSelected();
        clearErrors();
        return;
    }

    // Validate date is within allowed range before fetching
    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const sixMonthsLater = new Date(today);
    sixMonthsLater.setMonth(sixMonthsLater.getMonth() + 6);

    if (selectedDate < today || selectedDate > sixMonthsLater) {
        showDateError('Date must be within the next 6 months');
        elements.appointmentTime.innerHTML = '<option value="">Select Time</option>';
        elements.appointmentTime.disabled = true;
        return;
    }

    fetchAvailableTimes(doctorId, date);
}

// Event listeners
elements.appointmentDate.addEventListener('change', handleAvailabilityUpdate);
elements.doctorSelect.addEventListener('change', handleAvailabilityUpdate);

// Clear error on input
elements.appointmentTime.addEventListener('change', function() {
    if (this.value) {
        // Check if this time is booked
        if (bookedTimes.includes(this.value)) {
            showTimeError('This time is already taken');
            elements.appointmentTime.classList.add('is-invalid');
        } else {
            elements.appointmentTime.classList.remove('is-invalid');
            elements.timeError.classList.remove('show');
        }
    }
});

// Initialize on page load
window.addEventListener('DOMContentLoaded', () => {
    showNoDoctorSelected();
    // initialize Select2 for patient dropdown (callable so we can re-run when modal opens)
    function initPatientSelect2() {
        $('#patientSelect').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Patient',
            allowClear: true,
            searching: true,
            width: '100%',
            dropdownParent: $('#appointmentModal')
        });
    }
    initPatientSelect2();

    // initialize Select2 for doctor dropdown (callable so we can re-run when modal opens)
    function initDoctorSelect2() {
        $('#doctorSelect').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Doctor',
            allowClear: true,
            searching: true,
            width: '100%',
            dropdownParent: $('#appointmentModal')
        });
    }
    initDoctorSelect2();

    // re-init if modal opens (element may have been hidden previously)
    $('#appointmentModal').on('shown.bs.modal', function() {
        initPatientSelect2();
        initDoctorSelect2();
    });

    // clear form and errors when modal closed
    $('#appointmentModal').on('hidden.bs.modal', function() {
        const form = this.querySelector('form');
        if (form) form.reset();
        $('#patientSelect').val(null).trigger('change');
        clearErrors();
        // Clear reason validation
        const reasonInput = document.getElementById('appointmentReason');
        if (reasonInput) {
            reasonInput.classList.remove('is-invalid');
            const errorElement = document.getElementById('appointmentReasonError');
            if (errorElement) {
                errorElement.classList.add('d-none');
            }
        }
    });
    
    // Update character count on appointment reason
    const appointmentReasonInput = document.getElementById('appointmentReason');
    if (appointmentReasonInput) {
        appointmentReasonInput.addEventListener('input', function() {
            const charCount = document.getElementById('reasonCharCount');
            if (charCount) {
                charCount.textContent = this.value.length + '/100';
            }
        });
    }

    const reasonInput = document.getElementById('blockedReason');
    if (reasonInput) {
        reasonInput.addEventListener('input', function() {
            validateBlockedReason();
        });
        reasonInput.addEventListener('change', function() {
            validateBlockedReason();
        });
        reasonInput.addEventListener('keyup', function() {
            validateBlockedReason();
        });
    }

    // show appointment modal when button clicked
    const showBtn = document.getElementById('showAppointmentBtn');
    if (showBtn) {
        const appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
        showBtn.addEventListener('click', () => {
            appointmentModal.show();
        });
    }

    // Auto-submit filter form when any filter dropdown changes
    const filterForm = document.querySelector('form[method="GET"]');
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select[name="month"], select[name="time"], select[name="doctor"], select[name="status"], select[name="show_archived"]');
        filterSelects.forEach(select => {
            select.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    }
});
</script>

<!-- ================= BLOCKED DATES MODAL ================= -->

<div class="modal fade" id="blockedDatesModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
    <h5 class="modal-title">Manage Blocked Dates</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div id="blockedDatesMessages"></div>

<h6 class="mb-3">Add Blocked Date</h6>

<div class="row g-3">
<div class="col-md-6">
    <label>Date</label>
    <input type="date" id="blockedDate" class="form-control" min="<?= $minBlockedDate ?>" max="<?= $maxBlockedDate ?>">
    <small class="text-danger d-none" id="blockedDateError"></small>
</div>

<div class="col-md-6">
    <label>Reason (Optional)</label>
    <input type="text" id="blockedReason" class="form-control" placeholder="e.g., Public Holiday, Maintenance" oninput="validateBlockedReason()" onkeyup="validateBlockedReason()">
    <small class="text-danger d-none" id="blockedReasonError"></small>
</div>

<div class="col-md-12">
    <button class="btn btn-primary" onclick="addBlockedDate()">Add Blocked Date</button>
</div>
</div>

<hr class="my-4">

<h6 class="mb-3">Blocked Dates</h6>

<div id="blockedDatesList"></div>

</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

</div>
</div>
</div>

<script>
// Blocked dates management

function loadBlockedDates() {
    fetch('<?= htmlspecialchars(appUrl('/modules/appointments/get_blocked_dates.php')) ?>')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('blockedDatesList');
                container.innerHTML = '';
                data.dates.forEach(dateObj => {
                    const formattedDate = new Date(dateObj.blocked_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    });
                    const item = document.createElement('div');
                    item.className = 'd-flex justify-content-between align-items-center mb-2';
                    item.innerHTML = `
                        <span>${formattedDate} - ${dateObj.reason || '-'}</span>
                        <button class="btn btn-danger btn-sm" onclick="removeBlockedDate(${dateObj.id})">Delete</button>
                    `;
                    container.appendChild(item);
                });
            }
        })
        .catch(err => console.error('Error loading blocked dates:', err));
}

function validateBlockedDate() {
    const blockedDateInput = document.getElementById('blockedDate');
    const errorElement = document.getElementById('blockedDateError');
    const date = blockedDateInput.value;

    // Clear previous error
    errorElement.classList.add('d-none');
    blockedDateInput.classList.remove('is-invalid');

    if (!date) {
        return; // No validation if empty, user will get error on submit
    }

    // Validate date is in the future (not today or past)
    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate <= today) {
        errorElement.textContent = 'Only future dates can be blocked';
        errorElement.classList.remove('d-none');
        blockedDateInput.classList.add('is-invalid');
    } else {
        blockedDateInput.classList.remove('is-invalid');
    }
}

function validateBlockedReason() {
    const reasonInput = document.getElementById('blockedReason');
    const errorElement = document.getElementById('blockedReasonError');
    const text = reasonInput.value;

    // Always apply validation on input
    if (text.length > 100) {
        errorElement.textContent = 'Reason must be 100 characters or less (current: ' + text.length + ')';
        errorElement.classList.remove('d-none');
        reasonInput.classList.add('is-invalid');
        return false;
    }

    if (text.length > 0 && /\d/.test(text)) {
        errorElement.textContent = 'Numbers are not allowed in reason';
        errorElement.classList.remove('d-none');
        reasonInput.classList.add('is-invalid');
        return false;
    }

    // If no errors, clear styling
    errorElement.classList.add('d-none');
    reasonInput.classList.remove('is-invalid');
    return true;
}

function addBlockedDate() {
    const date = document.getElementById('blockedDate').value;
    const reason = document.getElementById('blockedReason').value;
    const errorElement = document.getElementById('blockedDateError');

    // Clear previous error
    errorElement.classList.add('d-none');

    if (!date) {
        errorElement.textContent = 'Please select a date';
        errorElement.classList.remove('d-none');
        return;
    }

    // Validate date is in the future (not today or past)
    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate <= today) {
        errorElement.textContent = 'Only future dates can be blocked';
        errorElement.classList.remove('d-none');
        return;
    }

    // Validate reason field
    if (!validateBlockedReason()) {
        return;
    }

    const formData = new FormData();
    formData.append('blocked_date', date);
    formData.append('reason', reason);

    fetch('<?= htmlspecialchars(appUrl('/modules/appointments/add_blocked_date.php')) ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('blockedDate').value = '';
            document.getElementById('blockedReason').value = '';
            document.getElementById('blockedDate').classList.remove('is-invalid');
            document.getElementById('blockedReason').classList.remove('is-invalid');
            document.getElementById('blockedReasonError').classList.add('d-none');
            loadBlockedDates();
            showBlockedDateMessage('✓ Blocked date added', 'success');
        } else {
            showBlockedDateMessage('⚠️ ' + (data.message || 'Error adding blocked date'), 'danger');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showBlockedDateMessage('⚠️ Error adding blocked date', 'danger');
    });
}

function removeBlockedDate(id) {
    if (!confirm('Remove this blocked date?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch('<?= htmlspecialchars(appUrl('/modules/appointments/remove_blocked_date.php')) ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadBlockedDates();
            showBlockedDateMessage('✓ Blocked date removed', 'success');
        } else {
            showBlockedDateMessage('⚠️ Error removing blocked date', 'danger');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showBlockedDateMessage('⚠️ Error removing blocked date', 'danger');
    });
}

function showBlockedDateMessage(message, type) {
    const messageDiv = document.getElementById('blockedDatesMessages');
    messageDiv.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    setTimeout(() => {
        messageDiv.innerHTML = '';
    }, 3000);
}

// Load blocked dates when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for appointments list
    const appointmentsTable = $('#appointmentsTable').DataTable({
    pageLength: 10,
    lengthMenu: [10, 30, 50],
    dom: '<"top d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center"ip>'
});

const filterContainer = document.getElementById("filterContainer");
const dataTableWrapper = document.querySelector("#appointmentsTable_wrapper");

const filterDiv = dataTableWrapper.querySelector(".top");

if (filterDiv && filterContainer) {
    filterDiv.insertAdjacentElement("afterend", filterContainer);
}

    // Real-time validation for blocked date input
    const blockedDateInput = document.getElementById('blockedDate');
    if (blockedDateInput) {
        blockedDateInput.addEventListener('change', validateBlockedDate);
        blockedDateInput.addEventListener('input', validateBlockedDate);
    }

    const blockedModal = document.getElementById('blockedDatesModal');
    if (blockedModal) {
        blockedModal.addEventListener('show.bs.modal', function() {
            // Attach event listeners when modal opens
            const reasonInput = document.getElementById('blockedReason');
            if (reasonInput) {
                reasonInput.removeEventListener('input', validateBlockedReason);
                reasonInput.removeEventListener('change', validateBlockedReason);
                reasonInput.addEventListener('input', function() {
                    validateBlockedReason();
                });
                reasonInput.addEventListener('change', function() {
                    validateBlockedReason();
                });
                reasonInput.addEventListener('keyup', function() {
                    validateBlockedReason();
                });
            }
            loadBlockedDates();
        });
    }

    // Handle visit form submission
    const visitForm = document.getElementById('completeVisitForm');
    if (visitForm) {
        visitForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('<?= htmlspecialchars(appUrl('/modules/appointments/save_visit_from_appointment.php')) ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Close the modal
                    const visitModal = bootstrap.Modal.getInstance(document.getElementById('visitModal'));
                    visitModal.hide();
                    
                    // Show success message and reload
                    alert('✓ Visit recorded successfully. Appointment marked as completed.');
                    location.reload();
                } else {
                    alert('⚠️ ' + (data.message || 'Error saving visit'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('⚠️ Error saving visit');
            }
        });
    }
});

function openVisitModal(appointmentId, patientId, doctorId, appointmentDate, appointmentTime) {
    // Pre-fill the visit form with appointment details
    document.getElementById('completeVisitForm').reset();
    document.getElementById('appointmentId').value = appointmentId;
    document.getElementById('patientId').value = patientId;
    document.getElementById('doctorId').value = doctorId;
    
    // Set and lock the date and time
    document.getElementById('visitDate').value = appointmentDate;
    document.getElementById('visitDateHidden').value = appointmentDate;
    document.getElementById('visitTime').value = parseInt(appointmentTime.split(':')[0]);
    document.getElementById('visitTimeHidden').value = parseInt(appointmentTime.split(':')[0]);
    
    // Show the visit modal
    const visitModal = new bootstrap.Modal(document.getElementById('visitModal'));
    visitModal.show();
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</body>
</html>