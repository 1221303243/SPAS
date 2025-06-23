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

// Get the lecturer's user_id from session
$user_id = $_SESSION['user_id'];

// Fetch the lecturer_id for this user
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$lecturer_id = $lecturer ? $lecturer['lecturer_id'] : null;
$stmt->close();

$current_trimester = getCurrentTrimester($conn);

// Fetch assessment configurations for this lecturer's subjects
$assessments = array();
if ($lecturer_id && isset($_SESSION['edu_level']) && $current_trimester) {
    $edu_level = $_SESSION['edu_level'];
    $sql = "SELECT DISTINCT
                a.assessment_id,
                a.subject_id,
                a.assessment_type,
                a.weightage,
                s.subject_code,
                s.subject_name,
                c.edu_level
            FROM assessment_plans a
            JOIN subjects s ON a.subject_id = s.subject_id
            JOIN classes c ON c.subject_id = s.subject_id
            WHERE c.lecturer_id = ?
              AND c.edu_level = ?
              AND s.trimester_id = ?
            ORDER BY s.subject_name, a.assessment_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $lecturer_id, $edu_level, $current_trimester['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assessments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Plans</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/assessment.css">
</head>
<body>
    <?php include 'topbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Assessment Plans</h2>
            <a href="plan.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Plan
            </a>
        </div>

        <?php if (empty($assessments)): ?>
            <div class="assessment-card">
                <div class="no-assessments">
                    <i class="bi bi-calendar-x fs-1 mb-3"></i>
                    <h4>No Assessment Plans Found</h4>
                    <p>You haven't created any assessment plans yet.</p>
                    <a href="plan.php" class="btn btn-primary mt-3">Create Your First Plan</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $current_subject = '';
            foreach ($assessments as $assessment):
                if ($current_subject !== $assessment['subject_name']):
                    if ($current_subject !== '') echo '</div></div>'; // Close previous card
                    $current_subject = $assessment['subject_name'];
            ?>
                <div class="assessment-card">
                    <div class="assessment-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($assessment['subject_name']); ?></h4>
                        <span class="subject-code"><?php echo htmlspecialchars($assessment['subject_code']); ?></span>
                        <span class="edu-level-badge"><?php echo htmlspecialchars($assessment['edu_level']); ?></span>
                    </div>
                    <div class="assessment-body">
            <?php endif; ?>
                        <div class="assessment-type">
                            <span><?php echo htmlspecialchars($assessment['assessment_type']); ?></span>
                            <span class="weightage-badge"><?php echo htmlspecialchars($assessment['weightage']); ?>%</span>
                            <button class="btn btn-sm btn-outline-secondary edit-assessment-btn" 
                                data-id="<?php echo $assessment['assessment_id']; ?>"
                                data-type="<?php echo htmlspecialchars($assessment['assessment_type']); ?>"
                                data-weightage="<?php echo htmlspecialchars($assessment['weightage']); ?>"
                                data-due="<?php 
                                    $due_date = '';
                                    $event_id = '';
                                    $due_stmt = $conn->prepare("SELECT event_date, event_id FROM calendar_events WHERE subject_id = ? AND event_text LIKE ? LIMIT 1");
                                    $event_text_like = "%{$assessment['assessment_type']}%";
                                    $due_stmt->bind_param("is", $assessment['subject_id'], $event_text_like);
                                    $due_stmt->execute();
                                    $due_result = $due_stmt->get_result();
                                    if ($due_row = $due_result->fetch_assoc()) {
                                        $due_date = $due_row['event_date'];
                                        $event_id = $due_row['event_id'];
                                    }
                                    $due_stmt->close();
                                    echo htmlspecialchars($due_date);
                                ?>"
                                data-eventid="<?php echo htmlspecialchars($event_id); ?>"
                                data-subject="<?php echo htmlspecialchars($assessment['subject_name']); ?>"
                                data-subjectid="<?php echo $assessment['subject_id']; ?>"
                                >
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
            <?php endforeach; ?>
            <?php if ($current_subject !== '') echo '</div></div>'; // Close last card ?>
        <?php endif; ?>
    </div>

    <!-- Edit Assessment Modal -->
    <div class="modal fade" id="editAssessmentModal" tabindex="-1" aria-labelledby="editAssessmentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="editAssessmentForm">
            <div class="modal-header">
              <h5 class="modal-title" id="editAssessmentModalLabel">Edit Assessment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="assessment_id" id="edit-assessment-id">
              <input type="hidden" name="subject_id" id="edit-subject-id">
              <input type="hidden" name="old_assessment_type" id="edit-old-assessment-type">
              <input type="hidden" name="event_id" id="edit-event-id">
              <div class="mb-3">
                <label for="edit-assessment-type" class="form-label">Assessment Type</label>
                <input type="text" class="form-control" name="assessment_type" id="edit-assessment-type" required>
              </div>
              <div class="mb-3">
                <label for="edit-weightage" class="form-label">Weightage (%)</label>
                <input type="number" class="form-control" name="weightage" id="edit-weightage" min="0" max="100" required>
              </div>
              <div class="mb-3">
                <label for="edit-due-date" class="form-label">Due Date</label>
                <input type="date" class="form-control" name="due_date" id="edit-due-date" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const editModal = new bootstrap.Modal(document.getElementById('editAssessmentModal'));
      document.querySelectorAll('.edit-assessment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          document.getElementById('edit-assessment-id').value = this.dataset.id;
          document.getElementById('edit-subject-id').value = this.dataset.subjectid;
          document.getElementById('edit-assessment-type').value = this.dataset.type;
          document.getElementById('edit-weightage').value = this.dataset.weightage;
          document.getElementById('edit-due-date').value = this.dataset.due;
          document.getElementById('edit-old-assessment-type').value = this.dataset.type;
          document.getElementById('edit-event-id').value = this.dataset.eventid;
          editModal.show();
        });
      });

      document.getElementById('editAssessmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('edit_assessment.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success && data.redirect) {
            window.location.href = data.redirect;
          } else if (data.success) {
            location.reload();
          } else {
            alert(data.message || 'Failed to update assessment.');
          }
        })
        .catch(() => alert('Failed to update assessment.'));
      });
    });
    </script>

    <?php if (isset($_GET['edit_success'])): ?>
        <div class="alert alert-success">Assessment updated successfully!</div>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
