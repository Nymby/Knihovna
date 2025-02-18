<?php
include '/data/web/virtuals/193972/virtual/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT); // Hash hesla
    $email = trim($_POST['email']);

    if (!empty($username) && !empty($password) && !empty($email)) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "Tento e-mail už je zaregistrovaný. Použijte jiný e-mail.";
        } else {

            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $username, $password, $email);

            if ($stmt->execute()) {
                echo "success";
                exit();
            } else {
                echo "Chyba při registraci: " . $conn->error;
            }
        }
        $stmt->close();
    } else {
        echo "Všechna pole jsou povinná!";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Registrace</title>
</head>
<body>
    <form method="POST" action="" class="containeros">
    <h2>Registrace</h2>
    <div class="form-group">
        <p>👤</p>
        <input type="text" id="username" name="username" placeholder="Uživatelské jméno" required><br>
        </div>
        <div class="form-group">
        <p>🔒</p>
        <input type="password" id="password" name="password" placeholder="Heslo" required><br>
        </div>
        <div class="form-group">
        <p>📧</p>
        <input type="email" id="email" name="email" placeholder="E-Mail" required><br>
        </div>
        <button type="submit" class="btn-submit">Zaregistrovat</button>
    </form>
</body>
</html>
	