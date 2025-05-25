<?php
// Academic year configuration
define('SEMESTER_START_DATE', '2025-03-10');
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