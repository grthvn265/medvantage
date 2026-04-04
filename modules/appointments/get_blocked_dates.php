<?php
header('Content-Type: application/json');

require '../../components/db.php';

try {
    // Fetch all blocked dates ordered by date descending
    $stmt = $pdo->prepare("
        SELECT id, blocked_date, reason 
        FROM blocked_dates 
        ORDER BY blocked_date DESC
    ");
    $stmt->execute();
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'dates' => $dates
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
