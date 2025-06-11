document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginForm");
    const errorMessage = document.getElementById("error-message");

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('login.php', { // adjust path if inside auth/
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            data = data.trim();
            if (data === "admin") {
                window.location.href = "../pages/admin/dashboard.php";
            } else if (data === "student") {
                window.location.href = "../pages/student/student_dashboard.php";
            } 
            else if (data === "lecturer") {
                window.location.href = "../pages/lecturer/lecturer_dashboard.php";
            } else {
                errorMessage.textContent = data; // Show error like "Invalid password"
            }
        })
        .catch(error => {
            errorMessage.textContent = "An error occurred: " + error;
        });
    });
});
