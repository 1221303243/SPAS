<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = $_POST['subject'];
    $assessment_types = $_POST['assessment_type'];
    $categories = $_POST['category'];
    $weightages = $_POST['weightage'];
    $due_dates = $_POST['due_date'];
    
    // Get subject details including assessment_type
    $stmt = $conn->prepare("SELECT subject_id, assessment_type FROM subjects WHERE subject_name = ?");
    $stmt->bind_param("s", $subject_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject = $result->fetch_assoc();
    $stmt->close();
    
    if (!$subject) {
        $_SESSION['error'] = "Invalid subject selected";
        header("Location: plan.php");
        exit();
    }
    
    $subject_id = $subject['subject_id'];
    $subject_assessment_type = $subject['assessment_type'];
    
    // Validate total weightage and category weightages based on subject type
    $total_weightage = 0;
    $category_weightages = ['coursework' => 0, 'final_exam' => 0];
    
    for ($i = 0; $i < count($weightages); $i++) {
        $total_weightage += intval($weightages[$i]);
        $category_weightages[$categories[$i]] += intval($weightages[$i]);
    }
    
    if ($total_weightage !== 100) {
        $_SESSION['error'] = "Total weightage must be exactly 100%";
        header("Location: plan.php");
        exit();
    }
    
    // Validate based on subject type
    if ($subject_assessment_type === 'coursework_only') {
        // For coursework-only subjects, ensure no final exam assessments
        if ($category_weightages['final_exam'] > 0) {
            $_SESSION['error'] = "Coursework-only subjects cannot have final exam assessments";
            header("Location: plan.php");
            exit();
        }
        // Coursework should total 100%
        if ($category_weightages['coursework'] !== 100) {
            $_SESSION['error'] = "Coursework-only subjects must have 100% coursework weightage";
            header("Location: plan.php");
            exit();
        }
    } else {
        // For coursework + final exam subjects, validate category limits
        if ($category_weightages['coursework'] > 100 || $category_weightages['final_exam'] > 100) {
            $_SESSION['error'] = "Each category's weightage cannot exceed 100%";
            header("Location: plan.php");
            exit();
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing assessment plans for this subject
        $stmt = $conn->prepare("DELETE FROM assessment_plans WHERE subject_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new assessment plans
        $stmt = $conn->prepare("INSERT INTO assessment_plans (subject_id, assessment_type, category, weightage, due_date) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($assessment_types); $i++) {
            $stmt->bind_param("issis", $subject_id, $assessment_types[$i], $categories[$i], $weightages[$i], $due_dates[$i]);
            $stmt->execute();
            
            // Insert into calendar_events with subject_id
            $calendar_stmt = $conn->prepare("INSERT INTO calendar_events (event_date, event_text, subject_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE event_text = VALUES(event_text), subject_id = VALUES(subject_id)");
            $event_text = "Assessment Due: " . $assessment_types[$i] . " (" . ucfirst($categories[$i]) . ") for " . $subject_name;
            $calendar_stmt->bind_param("ssi", $due_dates[$i], $event_text, $subject_id);
            $calendar_stmt->execute();
            $calendar_stmt->close();
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Assessment configuration saved successfully!";
        header("Location: plan.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Failed to save assessment configuration: " . $e->getMessage();
        header("Location: plan.php");
        exit();
    }
} else {
    header("Location: plan.php");
    exit();
}
?>