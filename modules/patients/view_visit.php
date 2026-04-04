<?php
include '../../components/db.php';
if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT v.*, 
           p.first_name AS p_fname, 
           p.last_name AS p_lname,
           d.first_name AS d_fname, 
           d.last_name AS d_lname
    FROM visits v
    JOIN patients p ON v.patient_id = p.patient_id
    JOIN doctors d ON v.doctor_id = d.doctor_id
    WHERE v.visit_id = ?
");
$stmt->execute([$id]);
$visit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visit) {
    die("Visit not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Visit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
    <div class="container-fluid">

        <div class="card shadow-lg mt-4">
            <div class="card-header text-black">
                <h4 class="fw-bold mb-1 mt-1">Visit Details</h4>
            </div>

            <div class="card-body fs-5">

                <p><strong>Visit ID:</strong> <?= $visit['visit_id'] ?></p>
                <p><strong>Patient:</strong> <?= $visit['p_fname'] . " " . $visit['p_lname'] ?></p>
                <p><strong>Doctor:</strong> <?= $visit['d_fname'] . " " . $visit['d_lname'] ?></p>
                <p><strong>Date & Time:</strong> <?= $visit['visit_datetime'] ?></p>

                <hr>

                <p><strong>Nature of Visit:</strong><br><?= $visit['nature_of_visit'] ?></p>
                <p><strong>Symptoms:</strong><br><?= $visit['symptoms'] ?></p>
                <p><strong>Affected Area:</strong><br><?= $visit['affected_area'] ?></p>
                <p><strong>Observation:</strong><br><?= $visit['observation'] ?></p>
                <p><strong>Procedure Done:</strong><br><?= $visit['procedure_done'] ?></p>
                <p><strong>Meds Prescribed:</strong><br><?= $visit['meds_prescribed'] ?></p>
                <p><strong>Instruction to Patient:</strong><br><?= $visit['instruction_to_patient'] ?></p>
                <p><strong>Remarks:</strong><br><?= $visit['remarks'] ?></p>

                <hr>

                <a href="patient_visit_history.php?id=<?= $visit['patient_id'] ?>" 
                   class="btn btn-secondary">
                   Back to Patient
                </a>

            </div>
        </div>

    </div>
</div>

</body>
</html>