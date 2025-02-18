<?php
session_start();
include '/data/web/virtuals/193972/virtual/connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiky knihovny</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<nav>
    <a href="admin_page.php">Admin Page</a>
</nav>

<main>
    <h1>Statistiky knihovny</h1>

    <div id="year-filter">
        <button class="year-btn" data-year="all">Celkově</button>
        <button class="year-btn" data-year="2024">2024</button>
        <button class="year-btn" data-year="2025">2025</button>
        <button class="year-btn" data-year="2026">2026</button>
        <button class="year-btn" data-year="2027">2027</button>
    </div>
    <section id="statistics">
        <p>Načítám data...</p>
    </section>
<aside id="book-list">
    <h3>Seznam půjčených knih</h3>
    <p>Vyberte měsíc pro zobrazení knih.</p>
</aside>
</main>

<script>
$(document).ready(function() {
    function loadStatistics(year) {
        $("#statistics").html("<p>Načítám data...</p>");
        $.ajax({
            url: "get_statistics.php",
            type: "GET",
            data: { year: year },
            success: function(response) {
                $("#statistics").html(response);
            },
            error: function() {
                $("#statistics").html("<p>Chyba při načítání dat.</p>");
            }
        });
    }

    function loadBooks(year, month) {
        $("#book-list").html("<p>Načítám knihy...</p>");
        $.ajax({
            url: "get_books.php",
            type: "GET",
            data: { year: year, month: month },
            success: function(response) {
                $("#book-list").html(response);
            },
            error: function() {
                $("#book-list").html("<p>Chyba při načítání knih.</p>");
            }
        });
    }

    loadStatistics("all");

    $(".year-btn").click(function() {
        let selectedYear = $(this).data("year");
        loadStatistics(selectedYear);
    });

    $(document).on("click", ".month-row", function() {
        let selectedYear = $(this).data("year");
        let selectedMonth = $(this).data("month");
        loadBooks(selectedYear, selectedMonth);
    });
});
</script>
</body>
</html>
