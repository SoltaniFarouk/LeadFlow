<?php
// monitor_batch_management.php

require_once __DIR__ . '/app/Connection/ConfigLeadFrDatabase.php';
use App\Connection\ConfigLeadFrDatabase;

// Connect
$pdo = (new ConfigLeadFrDatabase())->connect();

// Optional search
$search = $_GET['q'] ?? '';

// Base SQL
$sql = "SELECT * FROM tb_batch_management";
$params = [];

// Add search filter if needed
if (!empty($search)) {
    $sql .= " WHERE batch_name LIKE :search OR batch_file LIKE :search OR table_name LIKE :search";
    $params[':search'] = "%$search%";
}

// Add order and limit
$sql .= " ORDER BY id LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suivi Batch Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #2d323e; color: #FFFFFF; }
        h1 { text-align: center; color: #FFFFFF; }
        .search-box { margin: 20px 0; text-align: center; }
        .search-box form { display: inline-flex; gap: 10px; }
        input[type="text"] { padding: 8px; width: 250px; }
        input[type="submit"], .refresh a { padding: 8px 16px; cursor: pointer; border: none; }
        input[type="submit"] { background: #007BFF; color: white; border-radius: 5px; }
        input[type="submit"]:hover { background: #0056b3; }
        .refresh a { display: inline-block; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .refresh a:hover { background: #218838; }
        table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); color: #000; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #007BFF; color: white; cursor: pointer; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
    <script>
        // Simple table sorting
        function sortTable(n) {
            let table = document.getElementById("batchTable");
            let rows = table.rows;
            let switching = true;
            let dir = "asc";
            let switchcount = 0;

            while (switching) {
                switching = false;
                let shouldSwitch;

                for (let i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    let x = rows[i].getElementsByTagName("TD")[n];
                    let y = rows[i + 1].getElementsByTagName("TD")[n];

                    let cmpX = x.innerText.toLowerCase();
                    let cmpY = y.innerText.toLowerCase();

                    if (!isNaN(parseFloat(cmpX)) && !isNaN(parseFloat(cmpY))) {
                        cmpX = parseFloat(cmpX);
                        cmpY = parseFloat(cmpY);
                    }

                    if (dir === "asc" && cmpX > cmpY) {
                        shouldSwitch = true;
                        break;
                    }
                    if (dir === "desc" && cmpX < cmpY) {
                        shouldSwitch = true;
                        break;
                    }
                }

                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount === 0 && dir === "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    </script>
</head>
<body>
    <h1>📊 Suivi Batch Management</h1>

    <div class="search-box">
        <form method="get" action="">
            <input type="text" name="q" placeholder="Search batch, file, table..." value="<?= htmlspecialchars($search) ?>">
            <input type="submit" value="Search">
            <div class="refresh">
                <a href="monitor_batch_management.php">🔄 Refresh</a>
            </div>
        </form>
    </div>

    <table id="batchTable">
        <tr>
            <th onclick="sortTable(0)">ID</th>
            <th onclick="sortTable(1)">Name</th>
            <th onclick="sortTable(2)">File</th>
            <th onclick="sortTable(3)">Split Date</th>
            <th onclick="sortTable(4)">Department</th>
            <th onclick="sortTable(5)">Database</th>
            <th onclick="sortTable(6)">Table</th>
            <th onclick="sortTable(7)">Phone Prefix</th>
            <th onclick="sortTable(8)">Phone Count</th>
            <th onclick="sortTable(9)">Date Insertion</th>
            <th onclick="sortTable(10)">Date Modification</th>
        </tr>

        <?php if (empty($rows)): ?>
            <tr><td colspan="11">No data found</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['batch_name']) ?></td>
                    <td><?= htmlspecialchars($row['batch_file']) ?></td>
                    <td><?= htmlspecialchars($row['batch_split_date']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['database_name']) ?></td>
                    <td><?= htmlspecialchars($row['table_name']) ?></td>
                    <td><?= htmlspecialchars($row['phone_prefix']) ?></td>
                    <td><?= htmlspecialchars($row['phone_nombre']) ?></td>
                    <td><?= htmlspecialchars($row['date_insertion']) ?></td>
                    <td><?= htmlspecialchars($row['date_modification']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>
