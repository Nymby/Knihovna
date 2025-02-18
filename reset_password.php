<?php
include '/data/web/virtuals/193972/virtual/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {

        $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $token = bin2hex(random_bytes(50));
            $reset_link = "https://nirnberg.cz/datatest/reset_confirm.php?token=" . $token;

            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
            $stmt->bind_param("ss", $token, $email);
            $stmt->execute();

            $subject = "Resetování hesla";
            $message = "<html>
<head>
    <title>Resetování hesla</title>
</head>
<body>
    <p>Pro resetování hesla klikněte na následující odkaz:</p>
    <p><a href='" . $reset_link . "'>Klikněte zde pro resetování hesla</a></p>
</body>
</html>";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: petr@nirnberg.cz" . "\r\n";

            if (mail($email, $subject, $message, $headers)) {
                echo "E-mail pro reset hesla byl odeslán.";
            } else {
                echo "Chyba při odesílání e-mailu.";
            }

        } else {
            echo "Zadaný email není registrován.";
        }

        $stmt->close();
    } else {
        echo "Email je povinný!";
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetovat heslo</title>
</head>
<body>
    <form method="POST" action="" class="containeros">
        <h2>Resetovat heslo</h2>
        <div class="form-group">
        <p>📧</p>
        <input type="email" id="email" name="email" placeholder="E-mail"required><br>
        </div>
        <button type="submit" class="btn-submit">Odeslat odkaz pro obnovení</button>
    </form>
</body>
</html>
