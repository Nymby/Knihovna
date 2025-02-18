<?php
ob_start();
session_start();
include '/data/web/virtuals/193972/virtual/connect.php';

// Pokud není přihlášen admin, přesměruj na login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Funkce pro logování
function logChange($action, $details) {
    $logFile = __DIR__ . '/log.txt';
    $username = $_SESSION['username'] ?? 'unknown_user';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] $username: $action - $details\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

if (isset($_GET['logout'])) {
    logChange('Odhlášení uživatele', 'Uživatel ' . $_SESSION['username'] . ' se odhlásil.');
    session_destroy(); 
    header("Location: index.php");
    exit;
}

// ------- Správa výpůjček -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_borrow'])) {
    $ids         = $_POST['id']         ?? [];
    $bookIDs     = $_POST['BookID']     ?? [];
    $names       = $_POST['Name']       ?? [];
    $classes     = $_POST['Class']      ?? [];
    $borrowDates = $_POST['bookborrow'] ?? [];
    $returnDates = $_POST['return_date']?? [];
    $returned    = $_POST['returned']   ?? [];
    $lost        = $_POST['lost']       ?? [];
    $notes       = $_POST['notes']      ?? [];

    foreach ($bookIDs as $index => $bookID) {
        $bookID     = trim($bookID);
        $name       = trim($names[$index] ?? '');
        $class      = trim($classes[$index] ?? '');
        $borrowDate = trim($borrowDates[$index] ?? '');
        $returnDate = trim($returnDates[$index] ?? '');
        
        // Checkboxy: pokud je index v poli returned, je 1, jinak 0
        $isReturned = array_key_exists($index, $returned) ? 1 : 0;
        $isLost     = array_key_exists($index, $lost)     ? 1 : 0;

        $note       = trim($notes[$index] ?? '');

        // Získání názvu knihy na základě BookID (pokud existuje v tabulce books)
        $stmtBook = $conn->prepare("SELECT BookName FROM books WHERE BookID = ?");
        $stmtBook->bind_param("s", $bookID);
        $stmtBook->execute();
        $resultBook   = $stmtBook->get_result();
        $bookNameRow  = $resultBook->fetch_assoc();
        $bookName     = $bookNameRow['BookName'] ?? "Neznámý název";
        $stmtBook->close();

        // Pokud je BookID prázdné, přeskočit (chráníme se proti prázdnému řádku)
        if (empty($bookID)) {
            continue;
        }

        // ID záznamu, pokud je vyplněné (stávající řádek), jinak prázdné (nový)
        $id = $ids[$index] ?? null;

        if ($id) {
            // ---- UPDATE existující výpůjčky ----
            $sql = "UPDATE borrow
                    SET BookID = ?, BookName = ?, Name = ?, Class = ?, 
                        bookborrow = ?, return_date = ?, returned = ?, lost = ?, notes = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssisi",
                $bookID,
                $bookName,
                $name,
                $class,
                $borrowDate,
                $returnDate,
                $isReturned,
                $isLost,
                $note,
                $id
            );
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Pokud došlo k reálné změně, zalogujeme
                logChange('Úprava výpůjčky', "ID: $id, BookID: $bookID, Jméno: $name, Třída: $class");
            }
            $stmt->close();
        } else {
            // ---- INSERT nové výpůjčky ----
            $sql = "INSERT INTO borrow
                    (BookID, BookName, Name, Class, bookborrow, return_date, returned, lost, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssis",
                $bookID,
                $bookName,
                $name,
                $class,
                $borrowDate,
                $returnDate,
                $isReturned,
                $isLost,
                $note
            );
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Zalogujeme novou výpůjčku
                logChange('Nová výpůjčka', "BookID: $bookID, BookName: $bookName, Jméno: $name, Třída: $class");
            }
            $stmt->close();
        }
    }
}

// ------- Smazání výpůjčky -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['deleteRowId'])) {
    $id = intval($_POST['deleteRowId']);
    if ($id > 0) {
        $sql = "DELETE FROM borrow WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // Pokud se podařilo smazat, zalogujeme
                logChange('Smazání výpůjčky', "ID: $id");
                echo json_encode(['success' => true, 'message' => 'Záznam byl úspěšně smazán.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Chyba při mazání: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Chyba při přípravě dotazu: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Neplatné ID.']);
    }
    $conn->close();
    exit();
}

// ------- Přidání knihy ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    $bookID   = trim($_POST['BookID']);
    $bookName = trim($_POST['BookName']);

    if (!empty($bookID) && !empty($bookName)) {
        $sql = "INSERT INTO books (BookID, BookName) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $bookID, $bookName);
        if ($stmt->execute()) {
            // Logování přidání knihy
            logChange('Přidání knihy', "BookID: $bookID, BookName: $bookName");
        }
        $stmt->close();
    } else {
        echo "<p style='color:red;'>Chybí kód nebo název knihy!</p>";
    }
}

// --------- Smazání knihy ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $bookID = trim($_POST['BookID']);

    if (!empty($bookID)) {
        $sql = "DELETE FROM books WHERE BookID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $bookID);
        if ($stmt->execute()) {
            // Logování smazání knihy
            logChange('Smazání knihy', "BookID: $bookID");
        }        
        $stmt->close();
    } else {
        echo "<p style='color:red;'>ID knihy nebylo předáno!</p>";
    }
}

// ------- Načtení záznamů pro zobrazení -------
$sql = "SELECT borrow.*, books.BookName 
        FROM borrow 
        LEFT JOIN books ON borrow.BookID = books.BookID
        ORDER BY borrow.id ASC";
$result = $conn->query($sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);

// Načtení knih
$sql_books = "
    SELECT 
        books.BookID, 
        books.BookName, 
        (CASE WHEN EXISTS (
            SELECT 1 FROM borrow 
            WHERE borrow.BookID = books.BookID 
              AND (borrow.return_date IS NULL OR borrow.return_date = '')
        ) THEN 'borrowed' ELSE 'available' END) AS status
    FROM books
";
$result_books = $conn->query($sql_books);
$books = $result_books->fetch_all(MYSQLI_ASSOC);

// Dotaz na záznamy bez vyplněného return_date
$sql_unreturned = "SELECT * FROM borrow WHERE return_date IS NULL OR return_date = ''";
$result_unreturned = $conn->query($sql_unreturned);
$unreturned_rows = $result_unreturned->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Stránka</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function handleCheckboxInput(checkbox) {
            checkbox.value = checkbox.checked ? '1' : '0';
        }

        function scrollToBottom() {
            const borrowList = document.getElementById('borrow-list');
            borrowList.scrollTop = borrowList.scrollHeight;
        }

        window.onload = scrollToBottom;
function setTodayDate(input) {
    const today = new Date().toISOString().split('T')[0];
    if (!input.value) {
        input.value = today;
    }
}
		function prependCode(input, prefix) {
    input.value = prefix + input.value.replace(prefix, '');
}
// ----- mazání záznamu ------
document.addEventListener('DOMContentLoaded', function () {
    let deleteRowId = null;

    // Zobrazení modalu
    window.showModal = function (rowId) {
        deleteRowId = rowId;
        document.getElementById('modal-overlay').style.display = 'block';
    }

    // Skrytí modalu
    window.hideModal = function () {
        document.getElementById('modal-overlay').style.display = 'none';
        deleteRowId = null;
    }

    // Potvrzení mazání (okamžitá aktualizace seznamu)
    document.getElementById('confirm-delete').addEventListener('click', function () {
        if (deleteRowId !== null && deleteRowId > 0) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'deleteRowId=' + encodeURIComponent(deleteRowId)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Chyba serveru.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Odstranění řádku z DOM bez reloadu
                    const rowElement = document.querySelector(`tr[data-id='${deleteRowId}']`);
                    if (rowElement) {
                        rowElement.remove();
                    }
                    hideModal();  // Zavřít modal
                } else {
                    alert('Chyba: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Chyba při komunikaci:', error);
                alert('Chyba při mazání záznamu.');
            });
        } else {
            alert('Chyba: ID pro smazání není platné.');
        }
    });

    // Zavření modalu při kliknutí na "Zrušit"
    document.getElementById('cancel-delete').addEventListener('click', hideModal);
});

</script>
</head>
<body>
    <nav>
        <a href="statistics.php">Statistiky</a>
    </nav>
<div class="container">
    <div class="left-side">
        <div class="table-wrapper">
            <form method="POST">
                <div id="borrow-list">
                    <table>
                        <thead>
                            <tr>
                                <th>X</th>
                                <th>Kód knihy</th>
                                <th>Kniha</th>
                                <th>Jméno</th>
                                <th>Třída</th>
                                <th>Půjčeno dne</th>
                                <th>Datum vrácení</th>
                                <th>Vráceno</th>
                                <th>Ztráta</th>
                                <th>Poznámka</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><span style="color: red; cursor: pointer;" onclick="showModal(<?php echo htmlspecialchars($row['id']); ?>)">&#x2716;</span></td>
                                <td><input type="text" name="BookID[]" value="<?php echo htmlspecialchars($row['BookID']); ?>"></td>
                                <td><?php echo htmlspecialchars($row['BookName']); ?></td>
                                <td><input type="text" name="Name[]" value="<?php echo htmlspecialchars($row['Name']); ?>"></td>
                                <td><input type="text" name="Class[]" value="<?php echo htmlspecialchars($row['Class']); ?>"></td>
                                <td><input type="date" name="bookborrow[]" value="<?php echo htmlspecialchars($row['bookborrow']); ?>"></td>
                                <td><input type="date" name="return_date[]" value="<?php echo htmlspecialchars($row['return_date']); ?>" onclick="setTodayDate(this)"></td>
                                <td><input type="checkbox" name="returned[<?php echo $index; ?>]" value="1" <?php echo $row['returned'] ? 'checked' : ''; ?> onchange="handleCheckboxInput(this)"></td>
                                <td><input type="checkbox" name="lost[<?php echo $index; ?>]" value="1" <?php echo $row['lost'] ? 'checked' : ''; ?> onchange="handleCheckboxInput(this)"></td>
                                <td><input type="text" name="notes[]" value="<?php echo htmlspecialchars($row['notes']); ?>"></td>
                                <td><form method="POST" action="generate_image.php" style="display: inline;">
                                        <input type="hidden" name="bookname" value="<?php echo htmlspecialchars($row['BookName']); ?>">
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['Name']); ?>">
                                        <button type="submit" name="gender" value="Her">Her</button>
                                        <button type="submit" name="gender" value="His">His</button>
                                    </form></td>
                                <td><input type="hidden" name="id[]" value="<?php echo $row['id']; ?>"></td>
                            </tr>
                            <?php endforeach; ?> 
                            <tr>
                                <td>-</td>
                                <td><input type="text" name="BookID[]" placeholder="POE3202" oninput="prependCode(this, 'POE3202')"></td>
                                <td>-</td>
                                <td><input type="text" name="Name[]"></td>
                                <td><input type="text" name="Class[]"></td>
                                <td><input type="date" name="bookborrow[]" value="<?php echo date('Y-m-d'); ?>"></td>
                                <td><input type="date" name="return_date[]" value=""></td>
                                <td><input type="checkbox" name="returned[]" value="0" onchange="handleCheckboxInput(this)"></td>
                                <td><input type="checkbox" name="lost[]" value="0" onchange="handleCheckboxInput(this)"></td>
                                <td><input type="text" name="notes[]"></td>
                                <td><input type="hidden" name="id[]" value="0"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button style="width: 100%;border-radius: 5px" type="submit" name="save_borrow">Uložit změny</button>
            </form>
            <div id="modal-overlay" class="modal-overlay">
                <div class="modal">
                    <p>Opravdu chcete tento záznam smazat?</p>
                    <button class="confirm" id="confirm-delete" onclick="confirmDelete()">Smazat</button>
                    <button class="cancel" id="cancel-delete">Zrušit</button>
                </div>
            </div>
        </div>
        <div class="table-wraper">
            <h3>Nevrácené knihy</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kód knihy</th>
                        <th>Kniha</th>
                        <th>Jméno</th>
                        <th>Půjčeno dne</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($unreturned_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['BookID']); ?></td>
                        <td><?php echo htmlspecialchars($row['BookName']); ?></td>
                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                        <td><?php echo htmlspecialchars($row['bookborrow']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
<p><a href="?logout=1">Odhlásit se</a></p>
        </div>
    </div>
    <div class="right-side">
    <ul class="book-list">
        <?php foreach ($books as $book): ?>
            <li class="<?php echo $book['status']; ?>">
                <span><?php echo htmlspecialchars($book['BookID']); ?></span>
                <span><?php echo htmlspecialchars($book['BookName']); ?></span>
            </li>
        <?php endforeach; ?>
            <li>
            <h4 style="color: blue"> Půjčeno </h4> <h4 style="color: red"> Vráceno </h4>
            </li>
    </ul>
        <h4>Přidat knihu:</h4>
        <form method="POST">
            <input type="text" name="BookID" placeholder="BookID" required>
            <input type="text" name="BookName" placeholder="BookName" required>
            <button type="submit" name="save_book">Přidat</button>
        </form>
        <h4>Odebrat knihu:</h4>
        <form method="POST">
            <input type="text" name="BookID" placeholder="BookID" required>
            <button type="submit" name="delete_book">Odebrat</button>
        </form>
    </div>
</div>
</body>
</html>	