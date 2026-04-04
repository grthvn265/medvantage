<?php
include "../../components/db.php";

if (!isset($_GET["id"])) {
    header("Location: billing.php");
    exit;
}

$billing_id = (int) $_GET["id"];

// Fetch billing record
$stmt = $pdo->prepare("
    SELECT b.*, 
           p.first_name AS p_fname, p.last_name AS p_lname,
           d.first_name AS d_fname, d.last_name AS d_lname
    FROM billing b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    LEFT JOIN doctors d ON b.doctor_id = d.doctor_id
    WHERE b.billing_id = ?
");
$stmt->execute([$billing_id]);
$billing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$billing) {
    die("Billing record not found.");
}

// Fetch patients and doctors for dropdowns
$patients = $pdo->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get amount
$amount = floatval($billing["amount"]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Billing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <style>
        body { font-size: 16px; }
        .card { border-radius: 12px; }
    </style>
</head>
<body>

<?php include "../../components/sidebar.php"; ?>

<div class="content-wrapper p-4">
<div class="container-fluid">

<div class="card shadow-lg mt-4">
    <div class="card-header text-black">
        <h4 class="fw-bold mb-0">Edit Billing Record</h4>
    </div>
    <div class="card-body">

        <div id="formMessages"></div>

        <?php if ($billing["status"] === "Paid"): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>⚠️ Cannot Edit:</strong> This billing record has been marked as Paid and cannot be edited.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form id="editBillingForm">
            <input type="hidden" name="billing_id" value="<?= $billing["billing_id"] ?>">
            <input type="hidden" name="invoice_date" value="<?= $billing["invoice_date"] ?>">
            <input type="hidden" name="patient_id" value="<?= $billing["patient_id"] ?>">
            <input type="hidden" name="doctor_id" value="<?= $billing["doctor_id"] ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label>Invoice ID</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($billing["invoice_id"]) ?>" disabled>
                    <small class="text-muted">Auto-generated, cannot be changed</small>
                </div>

                <div class="col-md-6">
                    <label>Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="invoice_date" class="form-control" value="<?= $billing["invoice_date"] ?>" disabled>
                    <small class="text-muted">Cannot be changed</small>
                </div>

                <div class="col-md-6">
                    <label>Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" class="form-select" disabled>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                        <option value="<?= $p["patient_id"] ?>" <?= $p["patient_id"] == $billing["patient_id"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($p["last_name"] . ", " . $p["first_name"]) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Cannot be changed</small>
                </div>

                <div class="col-md-6">
                    <label>Doctor <span class="text-danger">*</span></label>
                    <select name="doctor_id" class="form-select" disabled>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d["doctor_id"] ?>" <?= $d["doctor_id"] == $billing["doctor_id"] ? "selected" : "" ?>>
                            Dr. <?= htmlspecialchars($d["last_name"] . ", " . $d["first_name"]) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Cannot be changed</small>
                </div>

                <div class="col-md-6">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" min="0" step="0.01" value="<?= $amount ?>" required <?php echo ($billing["status"] === "Paid") ? 'disabled' : ''; ?>>
                </div>

                <div class="col-md-6">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required <?php echo ($billing["status"] === "Paid") ? 'disabled' : ''; ?>>
                        <option value="Unpaid" <?= $billing["status"] === "Unpaid" ? "selected" : "" ?>>Unpaid</option>
                        <option value="Paid" <?= $billing["status"] === "Paid" ? "selected" : "" ?>>Paid</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <button type="submit" class="btn btn-warning" <?php echo ($billing["status"] === "Paid") ? 'disabled' : ''; ?>>Update Billing</button>
                    <a href="billing.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>

    </div>
</div>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById("editBillingForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch("update_billing.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const successHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Billing record updated successfully
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById("formMessages").innerHTML = successHtml;

            setTimeout(() => {
                window.location.href = "billing.php";
            }, 2000);
        } else {
            const errorHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${data.message || "An error occurred"}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById("formMessages").innerHTML = errorHtml;
        }
    } catch (error) {
        const errorHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Failed to update billing record
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.getElementById("formMessages").innerHTML = errorHtml;
        console.error(error);
    }
});
</script>

</body>
</html>
