<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students' Performance Analytics System (SPAS)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <header class="spas-header text-center py-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-2">Students' Performance Analytics System</h1>
            <p class="lead spas-tagline mb-3">Track, Analyze, and Improve Student Performance</p>
            <p class="spas-summary mb-4">SPAS empowers students, lecturers, and administrators to monitor academic progress, identify risks early, and make data-driven decisions for better educational outcomes.</p>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="auth/login.php" class="btn spas-btn-primary btn-lg px-4">Login</a>
                <a href="auth/signup.php" class="btn spas-btn-outline btn-lg px-4">Sign up</a>
                <a href="auth/presentation_login.php" class="btn btn-warning btn-lg px-4">
                    <i class="bi bi-presentation"></i> Demo Mode
                </a>
            </div>
        </div>
    </header>

    <section class="spas-features py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-4">
                <h2 class="fw-bold mb-3">Key Features</h2>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-12 col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="feature-icon mb-3"><i class="bi bi-bar-chart-line"></i></div>
                        <h5 class="fw-semibold mb-2">Grade Visualization</h5>
                        <p>Interactive charts and dashboards to help students and lecturers visualize academic performance over time.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="feature-icon mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                        <h5 class="fw-semibold mb-2">Risk Detection</h5>
                        <p>Early warning system to identify at-risk students and provide timely interventions for academic success.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="feature-icon mb-3"><i class="bi bi-calendar-event"></i></div>
                        <h5 class="fw-semibold mb-2">Assessment Calendar</h5>
                        <p>Centralized calendar to track upcoming assessments, deadlines, and important academic events.</p>
                    </div>
                </div>
            </div>
            <div class="row mt-5 justify-content-center">
                <div class="col-12 col-md-8 text-center">
                    <div class="screenshot-placeholder mb-3">
                        <div class="placeholder-img">[ Screenshot or Demo Video Coming Soon ]</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="spas-about py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 text-center">
                    <h2 class="fw-bold mb-3">About SPAS</h2>
                    <p>SPAS (Students' Performance Analytics System) is an innovative platform developed for Multimedia University (MMU) to enhance academic monitoring and support. Our mission is to provide actionable insights for students, lecturers, and administrators, fostering a culture of continuous improvement and academic excellence.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="spas-footer py-4 bg-dark text-light">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">&copy; 2024 Multimedia University (MMU) - SPAS</div>
            <div>
                <a href="#contact" class="footer-link">Contact</a>
                <span class="mx-2">|</span>
                <a href="#faq" class="footer-link">FAQ</a>
                <span class="mx-2">|</span>
                <a href="#terms" class="footer-link">Terms of Use</a>
                <span class="mx-2">|</span>
                <a href="#privacy" class="footer-link">Privacy Policy</a>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 