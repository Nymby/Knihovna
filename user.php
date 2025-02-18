<?php
include '/data/web/virtuals/193972/virtual/connect.php';

// Načtení seznamu půjček
$sql = "SELECT borrow.BookID, books.BookName, borrow.Name, borrow.Lastname 
        FROM borrow 
        LEFT JOIN books ON borrow.BookID = books.BookID
        ORDER BY borrow.id ASC";
$result = $conn->query($sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seznam půjček</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Seznam půjček</h1>
</body>
</html>
