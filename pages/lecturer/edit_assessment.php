<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../auth/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_id = intval($_POST['assessment_id']);
    $subject_id = intval($_POST['subject_id']);
    $assessment_type = trim($_POST['assessment_type']);
    $weightage = intval($_POST['weightage']);
    $due_date = $_POST['due_date'];
    $old_assessment_type = isset($_POST['old_assessment_type']) ? trim($_POST['old_assessment_type']) : $assessment_type;
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;

    if ($weightage < 0 || $weightage > 100) {
        echo json_encode(['success' => false, 'message' => 'Weightage must be between 0 and 100.']);
        exit();
    }

    // Get subject name for event text
    $stmt = $conn->prepare('SELECT subject_name FROM subjects WHERE subject_id = ?');
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject = $result->fetch_assoc();
    $stmt->close();
    if (!$subject) {
        echo json_encode(['success' => false, 'message' => 'Invalid subject.']);
        exit();
    }
    $subject_name = $subject['subject_name'];

    $conn->begin_transaction();
    try {
        // Update assessment_plans
        $stmt = $conn->prepare('UPDATE assessment_plans SET assessment_type = ?, weightage = ?, due_date = ? WHERE assessment_id = ?');
        $stmt->bind_param('sisi', $assessment_type, $weightage, $due_date, $assessment_id);
        $stmt->execute();
        $stmt->close();

        // Update calendar_events by event_id if provided
        if ($event_id) {
            $upd_stmt = $conn->prepare('UPDATE calendar_events SET event_date = ? WHERE event_id = ?');
            $upd_stmt->bind_param('si', $due_date, $event_id);
            $upd_stmt->execute();
            $upd_stmt->close();
        } else {
            // Insert new event if not found
            $event_text = "Assessment Due: $assessment_type for $subject_name";
            $ins_stmt = $conn->prepare('INSERT INTO calendar_events (event_date, event_text) VALUES (?, ?)');
            $ins_stmt->bind_param('ss', $due_date, $event_text);
            $ins_stmt->execute();
            $ins_stmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
} 