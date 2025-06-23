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

// Check if education level is selected
if (!isset($_SESSION['edu_level'])) {
    header("Location: select_edu_level.php");
    exit();
}

require_once '../../auth/db_connection.php';
require_once '../../config/academic_config.php';

$user_id = $_SESSION['user_id'];
// Get lecturer_id
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$stmt->close();
$lecturer_id = $lecturer ? $lecturer['lecturer_id'] : null;

// Get current active trimester
$current_trimester = getCurrentTrimester($conn);

// Fetch subjects/classes taught by this lecturer
$subjects = [];
if ($lecturer_id && isset($_SESSION['edu_level']) && $current_trimester) {
    $edu_level = $_SESSION['edu_level'];
    $sql = "SELECT DISTINCT s.subject_name, s.subject_id, s.subject_code, s.assessment_type, c.class_id, c.class_name, c.edu_level
            FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.lecturer_id = ?
              AND c.edu_level = ?
              AND s.trimester_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $lecturer_id, $edu_level, $current_trimester['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Configuration</title>
    <link rel="stylesheet" href="../../css/plan.css">
    
    <script>
        let currentSubjectType = 'coursework_final_exam';
        
        function updateSubjectType() {
            const subjectSelect = document.getElementById('subjectSelect');
            const selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
            currentSubjectType = selectedOption.getAttribute('data-assessment-type') || 'coursework_final_exam';
            
            // Update the category options based on subject type
            const categorySelects = document.querySelectorAll('select[name="category[]"]');
            categorySelects.forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '';
                
                if (currentSubjectType === 'coursework_only') {
                    select.innerHTML = '<option value="coursework">Coursework</option>';
                    if (currentValue === 'final_exam') {
                        select.value = 'coursework';
                    }
                } else {
                    select.innerHTML = '<option value="coursework">Coursework</option><option value="final_exam">Final Exam</option>';
                }
            });
            
            updateTotal();
            updateInstructions();
        }
        
        function updateInstructions() {
            const instructionsDiv = document.getElementById('assessmentInstructions');
            if (currentSubjectType === 'coursework_only') {
                instructionsDiv.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Coursework Only Subject:</strong> All assessments must be coursework type. 
                        Total weightage must equal 100% for coursework assessments only.
                    </div>
                `;
            } else {
                instructionsDiv.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Coursework + Final Exam Subject:</strong> You can have both coursework and final exam assessments. 
                        Total weightage must equal 100% across all assessments.
                    </div>
                `;
            }
        }
        
        function addAssessment() {
            const container = document.getElementById("assessments");
            const index = container.children.length;
            
            const categoryOptions = currentSubjectType === 'coursework_only' 
                ? '<option value="coursework">Coursework</option>'
                : '<option value="coursework">Coursework</option><option value="final_exam">Final Exam</option>';
            
            const div = document.createElement("div");
            div.className = "assessment-row";
            div.innerHTML = `
                <input type="text" name="assessment_type[]" placeholder="Assessment Type" required>
                <select name="category[]" required>
                    ${categoryOptions}
                </select>
                <input type="number" name="weightage[]" min="0" max="100" value="0" oninput="updateTotal()" required>
                <input type="date" name="due_date[]" required>
                <button type="button" onclick="removeAssessment(this)">&#10060;</button>
            `;
            container.appendChild(div);
        }

        function removeAssessment(button) {
            button.parentElement.remove();
            updateTotal();
        }

        function updateTotal() {
            let total = 0;
            let courseworkTotal = 0;
            let finalExamTotal = 0;
            
            const rows = document.querySelectorAll(".assessment-row");
            rows.forEach(row => {
                const weightage = parseInt(row.querySelector("input[name='weightage[]']").value) || 0;
                const category = row.querySelector("select[name='category[]']").value;
                
                total += weightage;
                if (category === 'coursework') {
                    courseworkTotal += weightage;
                } else {
                    finalExamTotal += weightage;
                }
            });
            
            document.getElementById("totalWeightage").innerText = `Total Weightage: ${total}%`;
            
            if (currentSubjectType === 'coursework_only') {
                document.getElementById("courseworkWeightage").innerText = `Coursework Weightage: ${courseworkTotal}%`;
                document.getElementById("finalExamWeightage").style.display = 'none';
            } else {
                document.getElementById("courseworkWeightage").innerText = `Coursework Weightage: ${courseworkTotal}%`;
                document.getElementById("finalExamWeightage").innerText = `Final Exam Weightage: ${finalExamTotal}%`;
                document.getElementById("finalExamWeightage").style.display = 'block';
            }
            
            let hasError = false;
            if (currentSubjectType === 'coursework_only') {
                hasError = total !== 100;
            } else {
                hasError = total !== 100 || courseworkTotal > 100 || finalExamTotal > 100;
            }
            
            document.getElementById("errorMsg").style.display = hasError ? "block" : "none";
        }
    </script>
</head>
<body>
    <?php include 'topbar.php'; ?>

    <div class="container">
        <h1>Assessment Configuration</h1>
        <form action="save.php" method="post">
            <label for="subject">Select Subject/Class:</label>
            <select name="subject" id="subjectSelect" required onchange="updateSubjectType()">
                <option value="">Choose a subject...</option>
                <?php foreach ($subjects as $subj): ?>
                    <option value="<?php echo htmlspecialchars($subj['subject_name']); ?>" 
                            data-assessment-type="<?php echo htmlspecialchars($subj['assessment_type']); ?>">
                        <?php echo htmlspecialchars($subj['subject_name'] . ' (' . $subj['subject_code'] . ') - ' . $subj['class_name'] . ' [' . $subj['edu_level'] . ']'); ?>
                        <?php if ($subj['assessment_type'] === 'coursework_only'): ?>
                            (Coursework Only)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="assessmentInstructions">
                <div class="alert alert-info">
                    <strong>Coursework + Final Exam Subject:</strong> You can have both coursework and final exam assessments. 
                    Total weightage must equal 100% across all assessments.
                </div>
            </div>

            <div id="assessments">
                <div class="assessment-row">
                    <input type="text" name="assessment_type[]" placeholder="Assessment Type" required>
                    <select name="category[]" required>
                        <option value="coursework">Coursework</option>
                        <option value="final_exam">Final Exam</option>
                    </select>
                    <input type="number" name="weightage[]" min="0" max="100" value="0" oninput="updateTotal()" required>
                    <input type="date" name="due_date[]" required>
                    <button type="button" onclick="removeAssessment(this)">&#10060;</button>
                </div>
            </div>
            
            <button type="button" onclick="addAssessment()">&#43; Add Assessment</button>
            <p id="totalWeightage">Total Weightage: 0%</p>
            <p id="courseworkWeightage">Coursework Weightage: 0%</p>
            <p id="finalExamWeightage">Final Exam Weightage: 0%</p>
            <p id="errorMsg" style="display: none;">Total weightage must be exactly 100% and each category cannot exceed 100%!</p>
            
            <button type="submit">Save Configuration</button>
        </form>
    </div>
</body>
</html>
