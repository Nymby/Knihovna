<?php
include '/data/web/virtuals/193972/virtual/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = trim($_POST['password']);
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    if (!empty($token) && !empty($new_password)) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($email);
            $stmt->fetch();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
            $stmt->bind_param("ss", $hashed_password, $token);

            if ($stmt->execute()) {
                echo "Heslo bylo úspěšně změněno. <a href='login.php'>Přihlaste se</a>.";
            } else {
                echo "Chyba při aktualizaci hesla.";
            }
        } else {
            echo "Neplatný nebo vypršený odkaz.";
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
    <title>Obnovit heslo</title>
</head>
<body>
    <h2>Obnovit heslo</h2>
    <form method="POST" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        
        <label for="password">Nové heslo:</label>
        <input type="password" id="password" name="password" required><br>
        
        <button type="submit">Resetovat heslo</button>
    </form>
</body>
</html>
