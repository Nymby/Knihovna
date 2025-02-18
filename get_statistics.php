<?php
include '/data/web/virtuals/193972/virtual/connect.php';

$year = isset($_GET['year']) ? $_GET['year'] : 'all';

if ($year === "all") {
    $total_borrows_query = "SELECT COUNT(*) AS total FROM borrow";
    $total_borrows_result = $conn->query($total_borrows_query);
    $total_borrows = $total_borrows_result->fetch_assoc()['total'];

    $most_borrowed_query = "SELECT BookName, COUNT(*) AS count FROM borrow GROUP BY BookName ORDER BY count DESC LIMIT 1";
    $most_borrowed_result = $conn->query($most_borrowed_query);
    $most_borrowed = $most_borrowed_result->fetch_assoc();

    $never_borrowed_query = "SELECT BookName FROM books WHERE BookID NOT IN (SELECT DISTINCT BookID FROM borrow)";
    $never_borrowed_result = $conn->query($never_borrowed_query);
    $never_borrowed_books = $never_borrowed_result->fetch_all(MYSQLI_ASSOC);

    echo "<h2>Celkový přehled knihovny</h2>";

    echo "<section id='total-borrows'><h3>Celkový počet výpůjček</h3><p><strong>$total_borrows</strong></p></section>";

    echo "<section id='most-borrowed'><h3>Nejvíce půjčovaná kniha</h3>";
    echo isset($most_borrowed['BookName']) ? "<p><strong>{$most_borrowed['BookName']} ({$most_borrowed['count']}x)</strong></p>" : "<p>Žádná data</p>";
    echo "</section>";

    echo "<section id='never-borrowed'><h3>Seznam nepůjčených knih</h3><ul>";
    foreach ($never_borrowed_books as $book) {
        echo "<li>{$book['BookName']}</li>";
    }
    echo "</ul></section>";

} else {
    $whereCondition = "WHERE YEAR(bookborrow) = $year";

    $total_borrows_year_query = "SELECT COUNT(*) AS total FROM borrow $whereCondition";
    $total_borrows_year_result = $conn->query($total_borrows_year_query);
    $total_borrows_year = $total_borrows_year_result->fetch_assoc()['total'];

    $monthly_borrows_query = "SELECT MONTH(bookborrow) AS month, COUNT(*) AS count FROM borrow $whereCondition GROUP BY MONTH(bookborrow) ORDER BY month";
    $monthly_borrows_result = $conn->query($monthly_borrows_query);
    $monthly_borrows = $monthly_borrows_result->fetch_all(MYSQLI_ASSOC);

    $months = [
        1 => "Leden", 2 => "Únor", 3 => "Březen", 4 => "Duben",
        5 => "Květen", 6 => "Červen", 7 => "Červenec", 8 => "Srpen",
        9 => "Září", 10 => "Říjen", 11 => "Listopad", 12 => "Prosinec"
    ];

    echo "<h2>Statistiky pro rok $year</h2>";

    echo "<section id='total-borrows-year'><h3>Celkový počet výpůjček v roce $year</h3><p><strong>$total_borrows_year</strong></p></section>";

    echo "<section id='monthly-borrows'><h3>Výpůjčky podle měsíců</h3><table>";
    echo "<tr><th>Měsíc</th><th>Počet výpůjček</th></tr>";
    foreach ($monthly_borrows as $month) {
        echo "<tr class='month-row' data-month='{$month['month']}' data-year='$year'>";
        echo "<td>{$months[$month['month']]}</td>";
        echo "<td>{$month['count']}</td>";
        echo "</tr>";
    }
    echo "</table></section>";

    echo "<p>Kliknutím na měsíc zobrazíte seznam půjčených knih.</p>";
}
?>
