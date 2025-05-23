<?php
require_once '../../auth/db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Date parameter is required']);
    exit;
}

$date = $_GET['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Get events for the specified date
$stmt = $conn->prepare("SELECT event_date, event_text FROM calendar_events WHERE event_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'event_date' => $row['event_date'],
        'event_text' => $row['event_text']
    ];
}

$stmt->close();
echo json_encode($events);
?> 