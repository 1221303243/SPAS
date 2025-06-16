<?php
// Academic year configuration
define('SEMESTER_START_DATE', '2025-06-02');
define('SEMESTER_WEEKS', 14);
define('PASSING_PERCENTAGE', 50);

// Grade normalization constants
define('MAX_GRADE', 100);  // Maximum possible grade
define('MIN_GRADE', 0);    // Minimum possible grade

// Assessment types
define('ASSESSMENT_TYPES', [
    'EXAM' => 'Examination',
    'QUIZ' => 'Quiz',
    'ASSIGNMENT' => 'Assignment',
    'PROJECT' => 'Project',
    'LAB' => 'Lab',
    'MIDTERM' => 'Midterm',
    'FINAL' => 'Final',
    'TEST' => 'Test'
]);

function getCurrentTrimester($conn) {
    $sql = "SELECT * FROM current_semester WHERE is_active = 1 LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return [
            'id' => $row['id'],
            'trimester_name' => $row['trimester_name'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'is_active' => $row['is_active']
        ];
    }
    return null;
}
