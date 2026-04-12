<?php
require '../../components/db.php';

if (!isset($_GET['id'])) {
    header('Location: ' . appUrl('/patients'));
    exit;
}

$patient_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient not found.");
}

// Get visit history
$stmt = $pdo->prepare("
    SELECT v.*, d.last_name, d.first_name
    FROM visits v
    JOIN doctors d ON v.doctor_id = d.doctor_id
    WHERE v.patient_id = ?
    ORDER BY v.visit_datetime DESC
");
$stmt->execute([$patient_id]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visit History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { font-size: 16px; }
        .card { border-radius: 12px; }
        .btn-sm { font-size: 0.875rem; padding: 0.4rem 0.6rem; }
        #visitsTable { margin-top: 1rem; }
        #visitsTable td { font-size: 0.95rem; }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
    <div class="container-fluid">

        <div class="card shadow-lg mt-4">
            <div class="card-header">
                <h4 class="fw-bold mb-1 mt-1">
                    Visit History - <?= $patient['last_name'] ?>, <?= $patient['first_name'] ?>
                </h4>
            </div>

            <div class="card-body">

                <?php if (empty($visits)): ?>
                    <div class="alert alert-secondary">No visits recorded for this patient.</div>
                <?php else: ?>
                    <table class="table table-striped table-hover" id="visitsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Visit ID</th>
                                <th>Date & Time</th>
                                <th>Doctor in Charge</th>
                                <th>Nature of Visit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visits as $visit): ?>
                                <tr>
                                    <td><?= $visit['visit_id'] ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($visit['visit_datetime'])) ?></td>
                                    <td>Dr. <?= $visit['last_name'] ?>, <?= $visit['first_name'] ?></td>
                                    <td><?= !empty($visit['nature_of_visit']) ? substr($visit['nature_of_visit'], 0, 50) . (strlen($visit['nature_of_visit']) > 50 ? '...' : '') : 'N/A' ?></td>
                                    <td>
                                        <a href="<?= htmlspecialchars(appUrl('/modules/patients/view_visit.php?id=' . (int) $visit['visit_id'])) ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="<?= htmlspecialchars(appUrl('/modules/patients/view_patient.php?id=' . (int) $patient_id)) ?>" class="btn btn-secondary">Back to Patient Details</a>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#visitsTable').DataTable({
        pageLength: 10,
        lengthMenu: [10, 30, 50],
    });
});
</script>

</body>
</html>
