<?php
ob_start();
session_start();
include '/data/web/virtuals/193972/virtual/connect.php';
 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
 
    $stmt = $conn->prepare("SELECT username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
 
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_username, $db_password, $db_role);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {

            $_SESSION['username'] = $db_username;
            $_SESSION['role'] = $db_role;
 
            if ($db_role == 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: user.php");
            }
            exit();
        } else {
            echo "Nesprávné jméno nebo heslo!";
        }
    } else {
        echo "Nesprávné jméno nebo heslo!";
    }
 
    $stmt->close();
}
 
$conn->close();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knihovna</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="login.css">

</head>
	
<body>
    <form method="POST" action="" class="containeros">
        <h2>Přihlášení</h2>
        <div class="form-group">
			<p>👤</p>
		<input type="text" name="username" placeholder="Uživatelské jméno" required><br>
			</div>
        <div class="form-group">
			<p>🔒</p>
        <input type="password" name="password" placeholder="Heslo" required><br>
			</div>
        <button type="submit" class="btn-submit">Přihlásit se</button>
		<div class="options">
	<a href="#" data-bs-toggle="modal" data-bs-target="#registraceModal">Registruj se zde</a> <a href="#" data-bs-toggle="modal" data-bs-target="#zapomenuteHesloModal">Obnovit heslo</a>
	</div>
    </form>
<div class="modal fade" id="registraceModal" tabindex="-1" aria-labelledby="registraceModalLabel" aria-hidden="true">
    <div class="modal-dialog custom-dialog">
        <div class="modal-content custom-content">
            <div class="modal-body custom-body">
                <?php include 'register.php'; ?>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="zapomenuteHesloModal" tabindex="-1" aria-labelledby="zapomenuteHesloModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content custom-content">
            <div class="modal-body custom-body">
                <?php include 'reset_password.php'; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const registerForm = document.querySelector("#registraceModal form");

        if (registerForm) {
            registerForm.addEventListener("submit", function(event) {
                event.preventDefault(); 
                const formData = new FormData(registerForm);

                fetch("register.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "success") {
                        alert("Registrace byla úspěšná! Nyní se můžete přihlásit.");
                        const modal = document.getElementById("registraceModal");
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        modalInstance.hide();
                    } else {
                        alert(data);
                    }
                })
                .catch(error => console.error("Chyba:", error));
            });
        }
    });
    document.addEventListener("DOMContentLoaded", function() {
        const resetForm = document.querySelector("#zapomenuteHesloModal form");

        if (resetForm) {
            resetForm.addEventListener("submit", function(event) {
                event.preventDefault();
                const formData = new FormData(resetForm);

                fetch("reset_password.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    const modal = document.getElementById("zapomenuteHesloModal");
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    modalInstance.hide();
                })
                .catch(error => console.error("Chyba:", error));
            });
        }
    });
</script>
</body>
</html>
