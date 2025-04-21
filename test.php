<!-- เชื่อมต่อกับฐานข้อมูล ชื่อ classroom_schedule -->
 <?php
$db_host = 'localhost';
$db_name = 'classroom_schedule';
$db_user = 'root';
$db_pass = '';
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    echo "Connected successfully";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
<!-- SELECT * FROM room_status WHERE status = 'available'; เอาคำสั่งนี้มาสร้างเป็นตาราง--><?php
$sql = "SELECT * FROM room_status WHERE status = 'available'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Room Status</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>

<h2>Room Status</h2>

<table>
    <thead>
        <tr>
            <th>Room ID</th>
            <th>Day</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $row): ?>
        <tr>
            <td><?php echo $row['room_id']; ?></td>
            <td><?php echo $row['day']; ?></td>
            <td><?php echo $row['start_time']; ?></td>
            <td><?php echo $row['end_time']; ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
