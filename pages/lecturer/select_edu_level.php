<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header('Location: ../../auth/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edu_level'])) {
    $_SESSION['edu_level'] = $_POST['edu_level'];
    header('Location: lecturer_dashboard.php');
    exit();
}

$levels = ['Foundation', 'Diploma', 'Undergraduate', 'Postgraduate'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Education Level</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/lecturer_select_edu_level.css">
</head>
<body>
    
    <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-lg w-100" style="max-width: 500px;">
            <h2 class="mb-4 text-center">Select Education Level</h2>
            <form method="POST">
                <div class="row g-3">
                    <?php foreach ($levels as $level): ?>
                        <div class="col-12">
                            <button type="submit" name="edu_level" value="<?= $level ?>" class="edu-level-btn btn btn-outline-primary w-100">
                                <?= $level ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 