<?php
include '/data/web/virtuals/193972/virtual/connect.php';

$year = isset($_GET['year']) ? $_GET['year'] : 'all';
$month = isset($_GET['month']) ? $_GET['month'] : null;

if ($month === null) {
    echo "<p>Chyba: Neplatný měsíc.</p>";
    exit();
}

$whereCondition = "WHERE MONTH(bookborrow) = $month";
if ($year !== "all") {
    $whereCondition .= " AND YEAR(bookborrow) = $year";
}

$books_query = "SELECT BookName FROM borrow $whereCondition GROUP BY BookName ORDER BY BookName";
$books_result = $conn->query($books_query);
$books = $books_result->fetch_all(MYSQLI_ASSOC);
?>

<h3>Knihy půjčené v měsíci <?php echo $month . "/" . $year; ?></h3>
<ul>
    <?php foreach ($books as $book) {
        echo "<li>" . $book['BookName'] . "</li>";
    } ?>
</ul>
