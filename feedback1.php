<?php
// Sample values; in production, get from session or URL parameters
$student_name = "Arif Faisal Bin Zakaria";
$course_name = "Programming 101";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback Entry - SPAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📝 Provide Feedback</h4>
        </div>
        <div class="card-body">
            <form action="submit_feedback.php" method="POST">
                <!-- Hidden inputs -->
                <input type="hidden" name="course_name" value="<?= htmlspecialchars($course_name) ?>">
                <input type="hidden" name="student_name" value="<?= htmlspecialchars($student_name) ?>">

                <div class="mb-3">
                    <label class="form-label">📘 Course:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($course_name) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">👤 Student:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($student_name) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label for="assessment" class="form-label">Select Assessment:</label>
                    <select class="form-select" name="assessment_title" id="assessment" required>
                        <option value="">-- Choose Assessment --</option>
                        <option value="Assignment 1">Assignment 1</option>
                        <option value="Quiz 1">Quiz 1</option>
                        <option value="Midterm Exam">Midterm Exam</option>
                        <option value="Final Project">Final Project</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="score" class="form-label">Enter Score (%):</label>
                    <input type="number" class="form-control" name="score" id="score" min="0" max="100" required>
                </div>

                <div class="mb-3">
                    <label for="feedback" class="form-label">Feedback:</label>
                    <textarea class="form-control" name="feedback_text" id="feedback" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="suggestions" class="form-label">Suggestions for Improvement (optional):</label>
                    <textarea class="form-control" name="suggestions" id="suggestions" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Feedback Type:</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="feedback_type" value="General" checked>
                        <label class="form-check-label">General</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="feedback_type" value="Detailed">
                        <label class="form-check-label">Detailed</label>
                    </div>
                </div>

                <div class="text-end">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-success">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
