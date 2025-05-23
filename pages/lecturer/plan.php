<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
    exit();
}

if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';

$user_id = $_SESSION['user_id'];
// Get lecturer_id
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$stmt->close();
$lecturer_id = $lecturer ? $lecturer['lecturer_id'] : null;

// Fetch subjects/classes taught by this lecturer
$subjects = [];
if ($lecturer_id) {
    $sql = "SELECT DISTINCT s.subject_name, s.subject_id, s.subject_code, c.class_id, c.class_name
            FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.lecturer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lecturer_id);
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
        function addAssessment() {
            const container = document.getElementById("assessments");
            const index = container.children.length;
            
            const div = document.createElement("div");
            div.className = "assessment-row";
            div.innerHTML = `
                <input type="text" name="assessment_type[]" placeholder="Assessment Type" required>
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
            document.querySelectorAll("input[name='weightage[]']").forEach(input => {
                total += parseInt(input.value) || 0;
            });
            document.getElementById("totalWeightage").innerText = `Total Weightage: ${total}%`;
            document.getElementById("errorMsg").style.display = total !== 100 ? "block" : "none";
        }
    </script>
</head>
<body>
    <?php include 'topbar.php'; ?>

    <div class="container">
        <h1>Assessment Configuration</h1>
        <form action="save.php" method="post">
            <label for="subject">Select Subject/Class:</label>
            <select name="subject" required>
                <?php foreach ($subjects as $subj): ?>
                    <option value="<?php echo htmlspecialchars($subj['subject_name']); ?>">
                        <?php echo htmlspecialchars($subj['subject_name'] . ' (' . $subj['subject_code'] . ') - ' . $subj['class_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="assessments">
                <div class="assessment-row">
                    <input type="text" name="assessment_type[]" placeholder="Assessment Type" required>
                    <input type="number" name="weightage[]" min="0" max="100" value="0" oninput="updateTotal()" required>
                    <input type="date" name="due_date[]" required>
                    <button type="button" onclick="removeAssessment(this)">&#10060;</button>
                </div>
            </div>
            
            <button type="button" onclick="addAssessment()">&#43; Add Assessment</button>
            <p id="totalWeightage">Total Weightage: 0%</p>
            <p id="errorMsg" style="display: none;">Total weightage must be exactly 100%!</p>
            
            <button type="submit">Save Configuration</button>
        </form>
    </div>
</body>
</html>
