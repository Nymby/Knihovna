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
           
            echo "Na váš email byl odeslán odkaz pro obnovení hesla.";
        } else {
            echo "Zadaný email není registrován.";
        }

        $stmt->close();
    } else {
        echo "Email je povinný!";
    }
}

$conn->close();
?>
