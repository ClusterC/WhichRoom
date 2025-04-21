<?php
// -- ตารางที่ 1: room_details ใช้เก็บข้อมูลของห้อง
// CREATE TABLE room_details (
//     room_id INT AUTO_INCREMENT PRIMARY KEY,
//     room_name VARCHAR(255) NOT NULL,
//     capacity INT,
//     has_plug BOOLEAN,
//     has_computer BOOLEAN,
//     room_size ENUM('small', 'medium', 'large')
// );

// -- ตารางที่ 2: room_status ใช้สำหรับแสดงสถานะของห้องว่ามีเรียนหรือว่าง
// CREATE TABLE room_status (
//     status_id INT AUTO_INCREMENT PRIMARY KEY,
//     room_id INT,
//     day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
//     start_time TIME NOT NULL,
//     end_time TIME NOT NULL,
//     status ENUM('available', 'occupied') NOT NULL,
//     FOREIGN KEY (room_id) REFERENCES room_details(room_id)
// );

// -- ตารางที่ 3: reserved_status แยกสถานะการจองออกมาต่างหากแบบ boolean โดยใช้ room_id
// CREATE TABLE reserved_status (
//     room_id INT PRIMARY KEY,
//     is_reserved BOOLEAN DEFAULT 0,
//     FOREIGN KEY (room_id) REFERENCES room_details(room_id)

// -- ตารางที่ 4: users ใช้สำหรับเก็บข้อมูลผู้ใช้
// CREATE TABLE users (
//     user_id INT AUTO_INCREMENT PRIMARY KEY,
//     username VARCHAR(255),
//     password VARCHAR(255),
//     role ENUM('admin', 'user')
// );
session_start(); // <<--- เริ่มต้น Session เพื่อตรวจสอบการล็อกอิน
date_default_timezone_set('Asia/Bangkok');

// --- ตรวจสอบการล็อกอินและ Role ---
$isLoggedIn = isset($_SESSION['username']); // ตรวจสอบว่ามีการล็อกอินหรือยัง
$userRole = $_SESSION['role'] ?? null; // ดึงค่า role จาก session, ถ้าไม่มีให้เป็น null

// --- ตรวจสอบว่าเป็น Admin หรือไม่ ---
// เช็คว่าล็อกอินแล้ว และ role เป็น 'admin'
$isAdmin = ($isLoggedIn && $userRole === 'admin');

// --- (ถ้าต้องการ) Redirect ถ้ายังไม่ได้ล็อกอิน ---
if (!$isLoggedIn) {
    header('Location: login.php');
    exit();
}

// --- ลบหรือคอมเมนต์บรรทัดทดสอบนี้ออก ---
// $isAdmin = true; // <<--- ลบบรรทัดนี้ออก หรือ comment ไว้

// --- ค่าเริ่มต้นต่างๆ ---
$currentDay = date('l');
$currentTime = date('H:i');
$submitted_data = null;
$day_value = $currentDay;
$start_time_value = $currentTime;
$end_time_value = '';
$status_value = 'available';
$room_id_value = '';
$room_name_value = '';
$capacity_value = '';
$room_size_value = '';
$exclude_plug_checked = false;
$exclude_computer_checked = false;
$results = [];
$db_error = null;

// --- Database Connection Settings ---
// (ควรย้ายไปไฟล์ config ถ้าโปรเจกต์ใหญ่ขึ้น)
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


// --- จัดการ Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? null; // รับค่า action

    // =============================================
    // === START: จัดการ AJAX Requests (Admin & Booking) ===
    // =============================================
    if ($action) {
        header('Content-Type: application/json'); // ตั้งค่า header สำหรับ JSON response ทุก AJAX action
        $pdo = null;
        $response = ['success' => false, 'message' => 'Invalid action or permission denied.']; // Default response

        try {
            // --- เชื่อมต่อฐานข้อมูลสำหรับ AJAX actions ---
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            // === ACTION: BOOKING ===
            if ($action === 'book') {
                // (โค้ดส่วน Booking เหมือนเดิม)
                $roomId = $_POST['room_id'] ?? null;
                if (empty($roomId)) {
                    $response = ['success' => false, 'message' => 'Room ID ไม่ถูกต้อง'];
                } else {
                    $sql = "UPDATE reserved_status SET is_reserved = TRUE WHERE room_id = :room_id AND is_reserved = FALSE";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':room_id' => $roomId]);

                    if ($stmt->rowCount() > 0) {
                        $response = ['success' => true];
                    } else {
                        $checkSql = "SELECT is_reserved FROM reserved_status WHERE room_id = :room_id";
                        $checkStmt = $pdo->prepare($checkSql);
                        $checkStmt->execute([':room_id' => $roomId]);
                        $currentReservationStatus = $checkStmt->fetchColumn();
                        if ($currentReservationStatus === 1 || $currentReservationStatus === true) {
                            $response = ['success' => false, 'message' => 'ห้องนี้ถูกจองไปแล้ว'];
                        } elseif ($currentReservationStatus === false || $currentReservationStatus === 0) {
                            $response = ['success' => false, 'message' => 'ไม่สามารถจองได้ อาจถูกจองไปแล้ว หรือ Room ID ไม่มีในระบบจอง'];
                        } else {
                            $response = ['success' => false, 'message' => 'ไม่พบ Room ID นี้ในระบบสถานะการจอง'];
                        }
                    }
                }
            }

            // === ACTION: ADMIN ADD ===
            elseif ($action === 'admin_add') {
                if (!$isAdmin) { // *** ตรวจสอบ Admin ที่นี่ ***
                    throw new Exception("Unauthorized: Admin access required.");
                }
                // (โค้ดส่วน Admin Add จาก admin_actions.php)
                $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
                $day = filter_input(INPUT_POST, 'day', FILTER_SANITIZE_SPECIAL_CHARS);
                $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_SPECIAL_CHARS);
                $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_SPECIAL_CHARS);
                $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

                // Basic Validation
                if (empty($room_id) || empty($day) || empty($start_time) || empty($end_time) || empty($status)) {
                    throw new Exception("Missing required fields for adding data (Room ID, Day, Start Time, End Time, Status).");
                }
                if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $start_time) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $end_time)) {
                     throw new Exception("Invalid time format. Use HH:MM.");
                }
                 if ($end_time <= $start_time) {
                     throw new Exception("End time must be after start time.");
                 }
                $valid_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                if (!in_array($day, $valid_days)) {
                     throw new Exception("Invalid day specified.");
                }
                 $valid_statuses = ['available', 'occupied'];
                 if (!in_array($status, $valid_statuses)) {
                     throw new Exception("Invalid status specified.");
                 }

                // Check if room_id exists in room_details (optional but good practice)
                $checkRoomSql = "SELECT COUNT(*) FROM room_details WHERE room_id = :room_id";
                $checkStmt = $pdo->prepare($checkRoomSql);
                $checkStmt->execute([':room_id' => $room_id]);
                if ($checkStmt->fetchColumn() == 0) {
                     throw new Exception("Room ID {$room_id} does not exist in room_details table.");
                }

                // Prepare INSERT statement for room_status
                $sql = "INSERT INTO room_status (room_id, day, start_time, end_time, status)
                        VALUES (:room_id, :day, :start_time, :end_time, :status)";
                $stmt = $pdo->prepare($sql);
                $params = [
                    ':room_id' => $room_id,
                    ':day' => $day,
                    ':start_time' => $start_time . ':00', // Add seconds if needed
                    ':end_time' => $end_time . ':00',   // Add seconds if needed
                    ':status' => $status
                ];
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true, 'message' => 'Data added successfully to room_status.'];
                } else {
                    throw new Exception("Failed to add data. No rows affected (maybe duplicate or other issue?).");
                }
            }

            // === ACTION: ADMIN DELETE ===
            elseif ($action === 'admin_delete') {
                 if (!$isAdmin) { // *** ตรวจสอบ Admin ที่นี่ ***
                    throw new Exception("Unauthorized: Admin access required.");
                }
                // (โค้ดส่วน Admin Delete จาก admin_actions.php)
                $conditions = [];
                $params = [];

                // Build conditions based on provided form data
                if (!empty($_POST['room_id'])) {
                    $conditions[] = "room_id = :room_id";
                    $params[':room_id'] = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
                }
                if (!empty($_POST['day'])) {
                    $conditions[] = "day = :day";
                    $params[':day'] = filter_input(INPUT_POST, 'day', FILTER_SANITIZE_SPECIAL_CHARS);
                }
                if (!empty($_POST['start_time'])) {
                    $conditions[] = "start_time = :start_time";
                    $params[':start_time'] = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_SPECIAL_CHARS) . ':00';
                }
                if (!empty($_POST['end_time'])) {
                    $conditions[] = "end_time = :end_time";
                    $params[':end_time'] = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_SPECIAL_CHARS) . ':00';
                }
                if (!empty($_POST['status'])) {
                    $conditions[] = "status = :status";
                    $params[':status'] = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
                }
                // Add other fields if needed

                if (empty($conditions)) {
                    throw new Exception("No criteria specified for deletion. Aborting to prevent accidental mass deletion.");
                }

                $sql = "DELETE FROM room_status WHERE " . implode(" AND ", $conditions);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                $affectedRows = $stmt->rowCount();
                if ($affectedRows > 0) {
                    $response = ['success' => true, 'message' => "Successfully deleted {$affectedRows} record(s) matching the criteria."];
                } else {
                    $response = ['success' => true, 'message' => "No records found matching the criteria to delete."];
                }
            }

            // === ACTION: EXECUTE SQL ===
            elseif ($action === 'execute_sql') {
                 if (!$isAdmin) { // *** ตรวจสอบ Admin ที่นี่ ***
                    throw new Exception("Unauthorized: Admin access required.");
                }
                // (โค้ดส่วน Execute SQL จาก admin_actions.php)
                $sqlQuery = $_POST['sql_query'] ?? '';

                if (empty($sqlQuery)) {
                    throw new Exception("No SQL query provided.");
                }

                // Basic check for potentially very harmful commands
                $disallowed_keywords = ['DROP DATABASE', 'DROP TABLE', 'TRUNCATE TABLE'];
                foreach ($disallowed_keywords as $keyword) {
                    if (stripos($sqlQuery, $keyword) !== false) {
                         throw new Exception("Execution of potentially harmful command ('{$keyword}') is blocked for safety.");
                    }
                }

                $queryType = strtoupper(trim(substr($sqlQuery, 0, 6)));

                if ($queryType === 'SELECT') {
                    $stmt = $pdo->query($sqlQuery);
                    $resultsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $rowCount = count($resultsData);
                    $limitedResults = array_slice($resultsData, 0, 50);
                    $response = [
                        'success' => true,
                        'message' => "SELECT query executed successfully. Found {$rowCount} rows.",
                        'results_preview' => $limitedResults,
                        'total_rows' => $rowCount
                    ];
                } else {
                    $affectedRows = $pdo->exec($sqlQuery);
                    if ($affectedRows === false) {
                         $errorInfo = $pdo->errorInfo();
                         throw new Exception("SQL execution failed: " . ($errorInfo[2] ?? 'Unknown error'));
                    }
                    $response = [
                        'success' => true,
                        'message' => "Non-SELECT query executed successfully.",
                        'affected_rows' => $affectedRows
                    ];
                }
            }
            // === END OF AJAX ACTIONS ===

        } catch (PDOException $e) {
            error_log("AJAX Action PDO Error: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("AJAX Action Error: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        } finally {
            $pdo = null; // Close connection
            echo json_encode($response); // Send JSON response
            exit; // *** สำคัญ: หยุดการทำงานหลังจากจัดการ AJAX request แล้ว ***
        }
    }
    // =============================================
    // === END: จัดการ AJAX Requests             ===
    // =============================================


    // =============================================
    // === START: จัดการ Form Submission (SEARCH) ===
    // =============================================
    else {
        // Store all submitted data in an array
        $submitted_data = $_POST;

        // Update variables with submitted values for form pre-filling
        $day_value = htmlspecialchars($_POST['day'] ?? $currentDay);
        $start_time_value = htmlspecialchars($_POST['start_time'] ?? $currentTime);
        $end_time_value = htmlspecialchars($_POST['end_time'] ?? '');
        $status_value = htmlspecialchars($_POST['status'] ?? 'available');
        $room_id_value = htmlspecialchars($_POST['adv_room_id'] ?? '');
        $room_name_value = htmlspecialchars($_POST['adv_room_name'] ?? '');
        $capacity_value = htmlspecialchars($_POST['adv_capacity'] ?? '');
        $room_size_value = htmlspecialchars($_POST['adv_room_size'] ?? '');
        $exclude_plug_checked = isset($_POST['adv_has_plug']) && $_POST['adv_has_plug'] == '1';
        $exclude_computer_checked = isset($_POST['adv_has_computer']) && $_POST['adv_has_computer'] == '1';

        // --- Database Query Logic (SEARCH) ---
        $pdo = null;
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            // (โค้ดส่วนสร้าง SQL Query สำหรับ Search เหมือนเดิม)
            $params = [];
            $sql_conditions = [];
            $sql = "SELECT rs.room_id, rs.day, rs.start_time, rs.end_time, rs.status,
                           rd.room_name, rd.capacity, rd.room_size, rd.has_plug, rd.has_computer
                    FROM room_status rs
                    INNER JOIN room_details rd ON rs.room_id = rd.room_id
                    LEFT JOIN reserved_status rs_res ON rd.room_id = rs_res.room_id";

            if ($status_value == 'available' || $status_value == 'occupied') {
                 $sql_conditions[] = "rs.status = :status";
                 $params[':status'] = $status_value;
            } else {
                 $sql = ""; // Prevent query if status is invalid
            }
            $sql_conditions[] = "(rs_res.is_reserved IS NULL OR rs_res.is_reserved = FALSE)";
            if (!empty($room_id_value)) {
                $sql_conditions[] = "rd.room_id = :room_id";
                $params[':room_id'] = (int)$room_id_value;
            }
            if (!empty($day_value)) {
                $sql_conditions[] = "rs.day = :day";
                $params[':day'] = $day_value;
            }
            $req_start_time = $start_time_value;
            $req_end_time = (!empty($end_time_value) && $end_time_value > $req_start_time) ? $end_time_value : null;
            if ($req_start_time) {
                if ($req_end_time) {
                    $sql_conditions[] = "(rs.start_time < :req_end_time AND rs.end_time > :req_start_time)";
                    $params[':req_end_time'] = $req_end_time;
                    $params[':req_start_time'] = $req_start_time;
                } else {
                    $sql_conditions[] = "(:req_start_time >= rs.start_time)";
                    $params[':req_start_time'] = $req_start_time;
                }
            }
            if (!empty($room_name_value)) {
                $sql_conditions[] = "rd.room_name LIKE :room_name";
                $params[':room_name'] = '%' . $room_name_value . '%';
            }
            if (!empty($capacity_value)) {
                $sql_conditions[] = "rd.capacity >= :capacity";
                $params[':capacity'] = (int)$capacity_value;
            }
            if (!empty($room_size_value)) {
                $sql_conditions[] = "rd.room_size = :room_size";
                $params[':room_size'] = $room_size_value;
            }
            if ($exclude_plug_checked) {
                $sql_conditions[] = "rd.has_plug = FALSE";
            }
            if ($exclude_computer_checked) {
                $sql_conditions[] = "rd.has_computer = FALSE";
            }

            if (!empty($sql_conditions) && !empty($sql)) {
                $sql .= " WHERE " . implode(" AND ", $sql_conditions);
            }
            if (!empty($sql)) {
                $sql .= " ORDER BY rd.room_name, rs.start_time";
            }

            if (!empty($sql)) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll();
            } else {
                 $results = [];
            }

        } catch (PDOException $e) {
            $db_error = "Database Error: " . $e->getMessage();
            error_log($db_error);
            $results = [];
        } finally {
             $pdo = null;
        }
    }
    // =============================================
    // === END: จัดการ Form Submission (SEARCH)   ===
    // =============================================

} // End of: if ($_SERVER["REQUEST_METHOD"] == "POST")

// --- ส่วน HTML และ JavaScript ที่เหลือ ---
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Which room?</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* --- CSS ทั้งหมดเหมือนเดิม --- */
        /* --- General Styles --- */
        html,
        body {
            min-height: 100%;
            margin: 0;
            font-family: sans-serif;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        /* --- Main Container --- */
        .main-container {
            position: relative; /* Needed for absolute positioning of panels */
            width: 100%;
            width: 500px; /* Adjusted width */
            display: flex;
            justify-content: center;
             margin-bottom: 30px;
        }

        /* --- Main Form --- */
        form.main-form {
            background-color: white;
            padding: 40px;
            padding-top: 50px; /* Make space for icons */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
            position: relative; /* Needed for icon positioning */
        }

        h1 {
            text-align: center;
            margin-bottom: 15px;
            color: #333;
            margin-top: 0;
        }

        .description {
            text-align: center;
            color: #555;
            margin-bottom: 25px;
            font-size: 0.9em;
        }

        /* --- Form Groups and Elements --- */
        .form-group {
            margin-bottom: 15px;
        }
        .time-inputs-container {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .time-inputs-container .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="date"],
        input[type="time"],
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }

        /* --- Settings Icon (Right) --- */
        .settings-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5em;
            color: #555;
            cursor: pointer;
            transition: color 0.3s ease, transform 0.3s ease;
            z-index: 5; /* Ensure it's clickable */
        }
        .settings-icon:hover {
            color: #000;
        }
        .main-container.show-advanced .settings-icon {
             transform: rotate(90deg);
        }

        /* --- Admin Icon (Left) --- */
        .admin-icon {
            position: absolute;
            top: 15px;
            left: 15px; /* Position on the left */
            font-size: 1.5em;
            color: #555;
            cursor: pointer;
            transition: color 0.3s ease, transform 0.3s ease;
            z-index: 5; /* Ensure it's clickable */
        }
        .admin-icon:hover {
            color: #000;
        }
        .main-container.show-admin .admin-icon {
             transform: scale(1.1); /* Scale effect */
        }


        /* --- Advance Options Panel (Right Side) --- */
        .advanced-options {
            position: absolute;
            top: 0;
            left: 100%; /* Position to the right of the container */
            margin-left: 20px; /* Space from the form */
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 300px; /* Width of the advanced panel */
            box-sizing: border-box;
            z-index: 10;
            opacity: 0;
            transform: translateX(-20px); /* Start slightly to the left for slide-in */
            pointer-events: none;
            transition: opacity 0.3s ease-out, transform 0.4s ease-out;
        }
        .advanced-options h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.2em;
            color: #333;
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .features-container {
            padding-top: 5px;
        }
        .features-container label {
            font-weight: normal;
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
        }
        .features-container label:last-child {
            margin-bottom: 0;
        }
        .features-container input[type="checkbox"] {
             width: auto;
             margin-right: 5px;
             vertical-align: middle;
        }
        /* Show Advanced Options */
        .main-container.show-advanced .advanced-options {
            opacity: 1;
            transform: translateX(0); /* Slide into view */
            pointer-events: auto;
        }

        /* --- Admin Panel (Left Side) --- */
        .admin-panel {
            position: absolute;
            top: 0;
            right: 100%; /* Position to the left of the container */
            margin-right: 20px; /* Space from the form */
            background-color: #f8f9fa; /* Slightly different background */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 400px; /* Adjust width as needed */
            box-sizing: border-box;
            z-index: 10;
            opacity: 0;
            transform: translateX(20px); /* Start slightly to the right for slide-in */
            pointer-events: none;
            transition: opacity 0.3s ease-out, transform 0.4s ease-out;
        }
        .admin-panel h2 {
            margin-top: 0;
            margin-bottom: 10px; /* Reduced margin */
            font-size: 1.2em;
            color: #333;
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
         .admin-panel p { /* Style for description text */
             font-size: 0.8em;
             color: #666;
             margin-bottom: 15px;
             text-align: center;
         }
        .admin-panel button { /* Style buttons inside admin panel */
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }
        .admin-panel button:hover {
            background-color: #0056b3;
        }
        .admin-panel button.delete-btn { /* Style delete button differently */
             background-color: #dc3545;
        }
        .admin-panel button.delete-btn:hover {
             background-color: #c82333;
        }
         .admin-panel hr { /* Style for horizontal rule */
             margin: 20px 0;
             border: 0;
             border-top: 1px solid #ddd;
         }
         .admin-panel h3 { /* Style for Manual SQL heading */
             font-size: 1em;
             margin-bottom: 5px;
             text-align: center;
             color: #333;
         }
         .admin-panel p.sql-warning { /* Style for SQL warning */
             font-size: 0.8em;
             color: #dc3545;
             margin-bottom: 10px;
             text-align: center;
         }
         .admin-panel textarea { /* Style for manual SQL textarea */
            width: 100%;
            min-height: 100px;
            margin-top: 5px; /* Reduced margin */
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: monospace; /* Use monospace font for SQL */
            font-size: 0.9rem;
            resize: vertical; /* Allow vertical resizing */
        }
        /* Show Admin Panel */
        .main-container.show-admin .admin-panel {
            opacity: 1;
            transform: translateX(0); /* Slide into view */
            pointer-events: auto;
        }
        /* --- Admin Panel Message Area --- */
        .admin-panel .message-area {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9em;
            display: none; /* Hidden by default */
            word-wrap: break-word; /* Wrap long messages */
            border: 1px solid transparent;
        }
        .admin-panel .message-area.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            display: block;
        }
        .admin-panel .message-area.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            display: block;
        }

        /* --- Submitted Data Display --- */
        .submitted-data-container {
            width: 100%;
            max-width: 900px; /* Can be wider than the form */
            background-color: #e9f5e9;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            box-sizing: border-box;
            margin-bottom: 30px; /* Space below this section */
        }
        .submitted-data-container h2 { margin-top: 0; color: #155724; border-bottom: 1px solid #b1dfbb; padding-bottom: 10px; }
        .submitted-data-container p { margin: 8px 0; }
        .submitted-data-container strong { min-width: 150px; /* Adjust as needed */ display: inline-block; }

        /* --- Results Container and Table Styles --- */
        .results-container {
            width: 100%;
            max-width: 900px; /* Can be wider than the form */
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
            margin-top: 0; /* Remove top margin if submitted data is shown */
        }
        .results-container h2 { margin-top: 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px; text-align: center; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .results-table th, .results-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .results-table thead tr { background-color: #f2f2f2; border-bottom: 2px solid #ddd; }
        .results-table th { font-weight: bold; }
        .results-table td.center { text-align: center; }
        .results-table td.icon { font-size: 1.1em; }
        .no-results, .db-error { text-align: center; padding: 20px; color: #555; }
        .db-error { color: #dc3545; font-weight: bold; }
        .results-table th.time-col, .results-table td.time-col { width: 80px; text-align: center; }
        .book-button { background-color: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; transition: background-color 0.2s ease; }
        .book-button:hover { background-color: #0056b3; }
        .book-button:disabled { background-color: #cccccc; cursor: not-allowed; }
        .book-button.booked { /* Style for already booked button state */
             background-color: #28a745;
             cursor: default;
        }
    </style>
</head>

<body>

    <!-- Container หลัก -->
    <div class="main-container" id="mainContainer">

        <?php if ($isAdmin): // Display Admin elements only if user is admin ?>
        <!-- Admin Panel Icon (Left) -->
        <i class="fas fa-user-shield admin-icon" id="adminBtn" title="Admin Panel"></i>

        <!-- Admin Panel -->
        <div class="admin-panel" id="adminPanel">
             <h2>Admin Panel</h2>
             <p>ใช้ค่าจากฟอร์มค้นหาปัจจุบันในการ เพิ่ม/ลบ ข้อมูลสถานะห้อง</p>
             <button id="adminAddBtn">เพิ่มข้อมูลตามฟอร์ม</button>
             <button class="delete-btn" id="adminDeleteBtn">ลบข้อมูลตามฟอร์ม</button>

             <hr>

             <h3>Manual SQL Execution</h3>
             <p class="sql-warning"><strong>คำเตือน:</strong> การรันคำสั่ง SQL โดยตรงมีความเสี่ยงสูง!</p>
             <textarea id="manualSqlInput" placeholder="ป้อนคำสั่ง SQL ที่นี่..."></textarea>
             <button id="executeSqlBtn">Execute SQL</button>

             <!-- Result Message Area -->
             <div id="adminMessageArea" class="message-area"></div>
        </div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="post" action="" class="main-form" id="searchForm">
            <!-- Settings Icon (Right) -->
            <i class="fas fa-cog settings-icon" id="settingsBtn" title="Advance Options"></i>

            <h1>ค้นหาห้องว่าง</h1>
            <p class="description">เว็บไซต์นี้ช่วยค้นหาห้องตามตารางเรียน ในช่วงเวลาที่คุณต้องการ</p>

            <!-- Main Form Fields -->
            <div class="form-group">
                <label for="day">วันในสัปดาห์:</label>
                <select id="day" name="day">
                    <option value="" <?php if (empty($day_value)) echo 'selected'; ?>>-- ทุกวัน --</option>
                    <option value="Monday" <?php if ($day_value == 'Monday') echo 'selected'; ?>>Monday</option>
                    <option value="Tuesday" <?php if ($day_value == 'Tuesday') echo 'selected'; ?>>Tuesday</option>
                    <option value="Wednesday" <?php if ($day_value == 'Wednesday') echo 'selected'; ?>>Wednesday</option>
                    <option value="Thursday" <?php if ($day_value == 'Thursday') echo 'selected'; ?>>Thursday</option>
                    <option value="Friday" <?php if ($day_value == 'Friday') echo 'selected'; ?>>Friday</option>
                    <option value="Saturday" <?php if ($day_value == 'Saturday') echo 'selected'; ?>>Saturday</option>
                    <option value="Sunday" <?php if ($day_value == 'Sunday') echo 'selected'; ?>>Sunday</option>
                </select>
            </div>

            <div class="time-inputs-container">
                <div class="form-group">
                    <label for="start_time">เวลาเริ่ม:</label>
                    <input type="time" id="start_time" name="start_time" value="<?php echo htmlspecialchars($start_time_value); ?>" >
                </div>
                <div class="form-group">
                    <label for="end_time">เวลาจบ:</label>
                    <input type="time" id="end_time" name="end_time" value="<?php echo htmlspecialchars($end_time_value); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="status">สถานะ:</label>
                <select id="status" name="status" required>
                    <option value="available" <?php if ($status_value == 'available') echo 'selected'; ?>>Available</option>
                    <option value="occupied" <?php if ($status_value == 'occupied') echo 'selected'; ?>>Occupied</option>
                </select>
            </div>

            <!-- Hidden fields to pass Advanced Options values on form submission -->
            <input type="hidden" name="adv_room_id" id="hidden_adv_room_id">
            <input type="hidden" name="adv_room_name" id="hidden_adv_room_name">
            <input type="hidden" name="adv_capacity" id="hidden_adv_capacity">
            <input type="hidden" name="adv_room_size" id="hidden_adv_room_size">
            <input type="hidden" name="adv_has_plug" id="hidden_adv_has_plug">
            <input type="hidden" name="adv_has_computer" id="hidden_adv_has_computer">

            <button type="submit">ค้นหาห้อง</button>
        </form>

        <!-- Advance Options Panel (Right Side) -->
        <div class="advanced-options" id="advancedOptionsPanel">
             <h2>Advance Options</h2>
             <div class="form-group">
                 <label for="adv_room_id_visible">Room ID:</label>
                 <input type="text" id="adv_room_id_visible" name="room_id_visible" placeholder="ค้นหาตาม Room ID..." value="<?php echo htmlspecialchars($room_id_value); ?>">
             </div>
             <div class="form-group">
                 <label for="adv_room_name_visible">ชื่อห้อง:</label>
                 <input type="text" id="adv_room_name_visible" name="room_name_visible" placeholder="ค้นหาตามชื่อห้อง..." value="<?php echo htmlspecialchars($room_name_value); ?>">
             </div>
             <div class="form-group">
                 <label for="adv_capacity_visible">ความจุ (ขั้นต่ำ):</label>
                 <input type="number" id="adv_capacity_visible" name="capacity_visible" min="1" placeholder="เช่น 30" value="<?php echo htmlspecialchars($capacity_value); ?>">
             </div>
             <div class="form-group">
                 <label for="adv_room_size_visible">ขนาดห้อง:</label>
                 <select id="adv_room_size_visible" name="room_size_visible">
                     <option value="" <?php if ($room_size_value == '') echo 'selected'; ?>>-- ทุกขนาด --</option>
                     <option value="small" <?php if ($room_size_value == 'small') echo 'selected'; ?>>เล็ก (Small)</option>
                     <option value="medium" <?php if ($room_size_value == 'medium') echo 'selected'; ?>>กลาง (Medium)</option>
                     <option value="large" <?php if ($room_size_value == 'large') echo 'selected'; ?>>ใหญ่ (Large)</option>
                 </select>
             </div>
             <div class="form-group">
                 <label>สิ่งอำนวยความสะดวก:</label>
                 <div class="features-container">
                     <label for="adv_has_plug_visible">
                     <input type="checkbox" id="adv_has_plug_visible" <?php if ($exclude_plug_checked) echo 'checked'; ?>> ไม่เอาห้องที่มีปลั๊กไฟ
                     </label>
                     <label for="adv_has_computer_visible">
                     <input type="checkbox" id="adv_has_computer_visible" <?php if ($exclude_computer_checked) echo 'checked'; ?>> ไม่เอาห้องที่มีคอมพิวเตอร์
                     </label>
                 </div>
             </div>
        </div>

    </div> <!-- End main-container -->


    <!-- Display Submitted Data Section (Shown only after a search) -->
    <!-- <?php if ($submitted_data !== null && !isset($_POST['action'])): ?>
        <div class="submitted-data-container">
            <h2>ข้อมูลที่ใช้ค้นหาล่าสุด</h2>
            <p><strong>Day:</strong> <?php echo htmlspecialchars($day_value ?: 'Any'); ?></p>
            <p><strong>Start Time:</strong> <?php echo htmlspecialchars($start_time_value ?: 'N/A'); ?></p>
            <p><strong>End Time:</strong> <?php echo htmlspecialchars($end_time_value ?: 'N/A'); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($status_value)); ?></p>
            <?php if (!empty($room_id_value)): ?><p><strong>Room ID:</strong> <?php echo htmlspecialchars($room_id_value); ?></p><?php endif; ?>
            <?php if (!empty($room_name_value)): ?><p><strong>Room Name:</strong> <?php echo htmlspecialchars($room_name_value); ?></p><?php endif; ?>
            <?php if (!empty($capacity_value)): ?><p><strong>Min Capacity:</strong> <?php echo htmlspecialchars($capacity_value); ?></p><?php endif; ?>
            <?php if (!empty($room_size_value)): ?><p><strong>Room Size:</strong> <?php echo htmlspecialchars($room_size_value); ?></p><?php endif; ?>
            <p><strong>Exclude Plug:</strong> <?php echo $exclude_plug_checked ? 'Yes' : 'No'; ?></p>
            <p><strong>Exclude Computer:</strong> <?php echo $exclude_computer_checked ? 'Yes' : 'No'; ?></p>
        </div>
    <?php endif; ?> -->


    <!-- Display Database Search Results Section (Shown only after a search) -->
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])): ?>
        <div class="results-container">
            <h2>ผลการค้นหา</h2>
            <?php if ($db_error): ?>
                <p class="db-error">เกิดข้อผิดพลาดในการเชื่อมต่อหรือค้นหาข้อมูล: </p>
                <p class="db-error"><?php echo htmlspecialchars($db_error); ?></p>
            <?php elseif (empty($results)): ?>
                <p class="no-results">ไม่พบห้องที่ตรงกับเงื่อนไขที่คุณระบุ</p>
            <?php else: ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Room ID</th>
                            <th>ชื่อห้อง</th>
                            <th>วัน</th>
                            <th class="time-col">เวลาเริ่ม</th>
                            <th class="time-col">เวลาจบ</th>
                            <th>สถานะ</th>
                            <th class="center">ความจุ</th>
                            <th class="center icon" title="มีปลั๊กไฟ">🔌</th>
                            <th class="center icon" title="มีคอมพิวเตอร์">💻</th>
                            <th class="center">จอง</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['room_id']); ?></td>
                            <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                            <td><?php echo htmlspecialchars($room['day']); ?></td>
                            <td class="time-col"><?php echo htmlspecialchars(substr($room['start_time'], 0, 5)); ?></td>
                            <td class="time-col"><?php echo htmlspecialchars(substr($room['end_time'], 0, 5)); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($room['status'])); ?></td>
                            <td class="center"><?php echo htmlspecialchars($room['capacity'] ?? 'N/A'); ?></td>
                            <td class="center icon"><?php echo $room['has_plug'] ? '✔️' : '❌'; ?></td>
                            <td class="center icon"><?php echo $room['has_computer'] ? '✔️' : '❌'; ?></td>
                            <td class="center">
                                <?php if ($room['status'] == 'available'): ?>
                                    <button class="book-button"
                                            data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>"
                                            data-day="<?php echo htmlspecialchars($room['day']); ?>"
                                            data-start="<?php echo htmlspecialchars($room['start_time']); ?>"
                                            data-end="<?php echo htmlspecialchars($room['end_time']); ?>"
                                            onclick="bookRoom(this)">
                                        จอง
                                    </button>
                                <?php else: ?>
                                    <span>ไม่ว่าง</span> <!-- Indicate clearly if occupied -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <script>
        // --- DOM Elements ---
        const settingsButton = document.getElementById('settingsBtn');
        const mainContainer = document.getElementById('mainContainer');
        const searchForm = document.getElementById('searchForm');
        const advancedPanel = document.getElementById('advancedOptionsPanel');
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        const daySelect = document.getElementById('day');
        const statusSelect = document.getElementById('status');

        // --- Admin Panel Elements ---
        const adminButton = document.getElementById('adminBtn');
        const adminPanel = document.getElementById('adminPanel');
        const adminAddBtn = document.getElementById('adminAddBtn');
        const adminDeleteBtn = document.getElementById('adminDeleteBtn');
        const manualSqlInput = document.getElementById('manualSqlInput');
        const executeSqlBtn = document.getElementById('executeSqlBtn');
        const adminMessageArea = document.getElementById('adminMessageArea');

        // --- Advanced Options Input Elements ---
        const advRoomIdInput = document.getElementById('adv_room_id_visible');
        const advRoomNameInput = document.getElementById('adv_room_name_visible');
        const advCapacityInput = document.getElementById('adv_capacity_visible');
        const advRoomSizeSelect = document.getElementById('adv_room_size_visible');
        const advHasPlugCheckbox = document.getElementById('adv_has_plug_visible'); // Checkbox element
        const advHasComputerCheckbox = document.getElementById('adv_has_computer_visible'); // Checkbox element


        // --- Hidden Input Elements (for search form submission) ---
        const hiddenAdvRoomId = document.getElementById('hidden_adv_room_id');
        const hiddenAdvRoomName = document.getElementById('hidden_adv_room_name');
        const hiddenAdvCapacity = document.getElementById('hidden_adv_capacity');
        const hiddenAdvRoomSize = document.getElementById('hidden_adv_room_size');
        const hiddenAdvHasPlug = document.getElementById('hidden_adv_has_plug');
        const hiddenAdvHasComputer = document.getElementById('hidden_adv_has_computer');


        // --- Helper Function to Display Admin Messages ---
        function showAdminMessage(message, isSuccess) {
            if (!adminMessageArea) return; // Guard clause
            adminMessageArea.textContent = message;
            adminMessageArea.className = 'message-area ' + (isSuccess ? 'success' : 'error');
            adminMessageArea.style.display = 'block'; // Make sure it's visible
        }

        // --- Helper Function to Get Current Form Data for Admin Actions ---
        function getAdminFormData() {
            const formData = new FormData();
            // Main form fields
            formData.append('day', daySelect.value);
            formData.append('start_time', startTimeInput.value);
            formData.append('end_time', endTimeInput.value);
            formData.append('status', statusSelect.value);
            // Advanced options fields (use values from the visible inputs)
            formData.append('room_id', advRoomIdInput.value);
            formData.append('room_name', advRoomNameInput.value); // Include room name if needed for delete criteria
            formData.append('capacity', advCapacityInput.value); // Include capacity if needed
            formData.append('room_size', advRoomSizeSelect.value); // Include room size if needed
            // Note: Checkboxes are generally for *filtering* searches, not usually for add/delete criteria
            // unless you specifically want to delete based on features.
            return formData;
        }

        // --- Admin Action Handler (Add/Delete) ---
        function handleAdminAction(actionType) {
            const formData = getAdminFormData();
            formData.append('action', actionType); // 'admin_add' or 'admin_delete'

            // --- Confirmation ---
            let confirmMessage = "";
            const roomId = formData.get('room_id');
            const day = formData.get('day') || 'Any Day';
            const startTime = formData.get('start_time') || 'Any Start';
            const endTime = formData.get('end_time') || 'Any End';
            const status = formData.get('status');

            if (actionType === 'admin_add') {
                // Basic client-side validation for Add
                if (!roomId || !formData.get('day') || !formData.get('start_time') || !formData.get('end_time')) {
                     showAdminMessage('สำหรับ "เพิ่มข้อมูล" กรุณาระบุ Room ID, วัน, เวลาเริ่ม, และ เวลาจบ ในฟอร์ม', false);
                     return; // Stop if required fields are missing
                }
                 if (endTime <= startTime) {
                     showAdminMessage('เวลาจบต้องอยู่หลังเวลาเริ่ม', false);
                     return;
                 }
                confirmMessage = `ยืนยันการ **เพิ่ม** ข้อมูลสถานะห้อง?\nRoom ID: ${roomId}\nDay: ${day}\nTime: ${startTime} - ${endTime}\nStatus: ${status}`;
            } else if (actionType === 'admin_delete') {
                 // Warn if criteria are too broad (e.g., no Room ID or Day)
                 let criteriaSummary = `Criteria:\n`;
                 let hasSpecificCriteria = false;
                 if (roomId) { criteriaSummary += `- Room ID: ${roomId}\n`; hasSpecificCriteria = true; }
                 if (formData.get('day')) { criteriaSummary += `- Day: ${day}\n`; hasSpecificCriteria = true; }
                 // Add other fields to summary if they are used as criteria in admin_actions.php
                 if (formData.get('start_time')) { criteriaSummary += `- Start Time: ${startTime}\n`; }
                 if (formData.get('end_time')) { criteriaSummary += `- End Time: ${endTime}\n`; }
                 if (status) { criteriaSummary += `- Status: ${status}\n`; }
                 // if (formData.get('room_name')) { criteriaSummary += `- Room Name: ${formData.get('room_name')}\n`; hasSpecificCriteria = true;} // Example

                 if (!hasSpecificCriteria) {
                     confirmMessage = `**คำเตือน!** คุณกำลังจะลบข้อมูลโดยไม่มีเงื่อนไขเฉพาะเจาะจง (เช่น Room ID หรือ วัน) อาจส่งผลกระทบต่อหลายรายการ\n${criteriaSummary}\n\nยืนยันการ **ลบ** ข้อมูลสถานะห้องตามเงื่อนไขนี้หรือไม่?`;
                 } else {
                     confirmMessage = `ยืนยันการ **ลบ** ข้อมูลสถานะห้องตามเงื่อนไข?\n${criteriaSummary}`;
                 }
            } else {
                console.error("Invalid action type:", actionType);
                return; // Should not happen
            }

            // Show confirmation dialog
            if (!confirm(confirmMessage)) {
                showAdminMessage('ยกเลิกการดำเนินการ', false);
                return; // User cancelled
            }

            // --- Send Request ---
            showAdminMessage('กำลังดำเนินการ...', true); // Show processing message

            // *** เปลี่ยน URL เป็น main.php ***
            fetch('main.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Try to get more specific error from response body if possible
                    return response.json().then(errData => {
                        throw new Error(errData.message || `HTTP error! status: ${response.status}`);
                    }).catch(() => {
                        // Fallback if response is not JSON or empty
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json(); // Parse JSON response
            })
            .then(data => {
                showAdminMessage(data.message, data.success);
                // Optional: Automatically refresh search results after successful add/delete
                // if (data.success) {
                //    console.log("Action successful, refreshing search...");
                //    searchForm.submit(); // Resubmit the search form
                // }
            })
            .catch(error => {
                console.error('Error during admin action:', error);
                showAdminMessage('เกิดข้อผิดพลาด: ' + error.message, false);
            });
        }

        // --- Manual SQL Execution Handler ---
        function executeManualSql() {
            const sqlQuery = manualSqlInput.value.trim();
            if (!sqlQuery) {
                showAdminMessage('กรุณาป้อนคำสั่ง SQL', false);
                return;
            }

            // --- STRONG Confirmation ---
            const confirmation = confirm(`**คำเตือนความปลอดภัยร้ายแรง!**\nคุณกำลังจะรันคำสั่ง SQL ต่อไปนี้โดยตรง:\n\n${sqlQuery}\n\nการกระทำนี้อาจส่งผลกระทบอย่างรุนแรงต่อข้อมูลและไม่สามารถย้อนกลับได้! คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?`);

            if (!confirmation) {
                showAdminMessage('ยกเลิกการ Execute SQL', false);
                return; // User cancelled
            }

            // --- Send Request ---
            showAdminMessage('กำลัง Execute SQL...', true);
            const formData = new FormData();
            formData.append('action', 'execute_sql');
            formData.append('sql_query', sqlQuery);

            // *** เปลี่ยน URL เป็น main.php ***
            fetch('main.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                     // Try to get error text if possible
                     return response.text().then(text => {
                         throw new Error(`SQL Execution Failed: ${response.status} - ${text || 'Server error'}`);
                     });
                }
                return response.json(); // Parse JSON response
            })
            .then(data => {
                 // Display success message, potentially including affected rows or results preview
                 let message = data.message;
                 if (data.affected_rows !== undefined) {
                     message += ` (Affected Rows: ${data.affected_rows})`;
                 }
                 if (data.results_preview && data.results_preview.length > 0) {
                     message += `\nPreview (${data.results_preview.length}/${data.total_rows || data.results_preview.length} rows):\n` + JSON.stringify(data.results_preview, null, 2);
                 } else if (data.results_preview && data.results_preview.length === 0 && data.total_rows === 0) {
                     message += "\n(Query returned 0 rows)";
                 }
                 showAdminMessage(message, data.success);
                 manualSqlInput.value = ''; // Clear textarea after execution
            })
            .catch(error => {
                console.error('Error executing manual SQL:', error);
                showAdminMessage('เกิดข้อผิดพลาดในการ Execute SQL: ' + error.message, false);
            });
        }


        // --- Event Listeners ---

        // Listener for Settings Button (Right Panel)
        if (settingsButton) {
             settingsButton.addEventListener('click', () => {
                 mainContainer.classList.toggle('show-advanced');
                 // Update titles
                 settingsButton.title = mainContainer.classList.contains('show-advanced') ? "Close Advance Options" : "Advance Options";
                 if (adminButton) adminButton.title = "Admin Panel"; // Reset admin button title
                 if (adminMessageArea) adminMessageArea.style.display = 'none'; // Hide admin message area
             });
        }

        // Listener for Admin Button (Left Panel)
        if (adminButton) { // Check if admin button exists (only for admins)
            adminButton.addEventListener('click', () => {
                mainContainer.classList.toggle('show-admin');
                // Update titles
                adminButton.title = mainContainer.classList.contains('show-admin') ? "Close Admin Panel" : "Admin Panel";
                if (settingsButton) settingsButton.title = "Advance Options"; // Reset settings button title
                if (adminMessageArea) adminMessageArea.style.display = 'none'; // Hide message area when toggling panel
            });
        }

        // Listener for Admin Panel Buttons
        if (adminAddBtn) {
            adminAddBtn.addEventListener('click', () => handleAdminAction('admin_add'));
        }
        if (adminDeleteBtn) {
            adminDeleteBtn.addEventListener('click', () => handleAdminAction('admin_delete'));
        }
        if (executeSqlBtn) {
            executeSqlBtn.addEventListener('click', executeManualSql);
        }


        // Listener for Form Submission (Search)
        searchForm.addEventListener('submit', function(event) {
            // 1. Basic client-side time validation
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            if (startTime && endTime && endTime <= startTime) {
                alert('เวลาจบต้องอยู่หลังเวลาเริ่ม');
                event.preventDefault(); // Prevent form submission
                return;
            }

            // 2. Copy values from visible advanced options to hidden fields before submitting search
            hiddenAdvRoomId.value = advRoomIdInput.value;
            hiddenAdvRoomName.value = advRoomNameInput.value;
            hiddenAdvCapacity.value = advCapacityInput.value;
            hiddenAdvRoomSize.value = advRoomSizeSelect.value;
            // Get checkbox states directly
            hiddenAdvHasPlug.value = advHasPlugCheckbox.checked ? '1' : '';
            hiddenAdvHasComputer.value = advHasComputerCheckbox.checked ? '1' : '';
        });

        // --- Initialize Advanced Options Visibility on Page Load ---
        // If the page was loaded as a result of a search submission AND
        // any advanced options were used, show the advanced panel.
        <?php if ($submitted_data !== null && (
               !empty($room_id_value) ||
               !empty($room_name_value) ||
               !empty($capacity_value) ||
               !empty($room_size_value) ||
               $exclude_plug_checked ||
               $exclude_computer_checked
            )
        ): ?>
           if (mainContainer && !mainContainer.classList.contains('show-advanced')) {
               mainContainer.classList.add('show-advanced');
               if (settingsButton) {
                  settingsButton.title = "Close Advance Options";
               }
           }
        <?php endif; ?>

        // --- Booking Function ---
        function bookRoom(buttonElement) {
             const roomId = buttonElement.getAttribute('data-room-id');
             // Optional: Get other data if needed for confirmation or logic
             // const day = buttonElement.getAttribute('data-day');
             // const startTime = buttonElement.getAttribute('data-start');
             // const endTime = buttonElement.getAttribute('data-end');
             const roomName = buttonElement.closest('tr').querySelector('td:nth-child(2)').textContent; // Get room name from table row

             console.log("Booking requested for Room ID:", roomId);

             const confirmation = confirm(`คุณต้องการจองห้อง ${roomName} (${roomId}) หรือไม่?\n(การดำเนินการนี้จะทำเครื่องหมายว่าห้องถูกจอง)`);

             if (confirmation) {
                 buttonElement.disabled = true; // Disable button immediately
                 buttonElement.textContent = 'กำลังจอง...';

                 const formData = new FormData();
                 formData.append('action', 'book'); // Specify the action for the backend
                 formData.append('room_id', roomId);

                 // *** เปลี่ยน URL เป็น main.php *** (Booking ก็ใช้ main.php อยู่แล้ว)
                 fetch('main.php', {
                     method: 'POST',
                     body: formData
                 })
                 .then(response => {
                     if (!response.ok) {
                          // Try to parse error message from JSON if possible
                          return response.json().then(errData => {
                              throw new Error(errData.message || `HTTP error! status: ${response.status}`);
                          }).catch(() => {
                              throw new Error(`HTTP error! status: ${response.status}`);
                          });
                     }
                     return response.json(); // Parse success response
                 })
                 .then(data => {
                     if (data.success) {
                         alert('จองห้องสำเร็จ!');
                         // Update UI to reflect booking
                         buttonElement.textContent = 'จองแล้ว';
                         buttonElement.classList.add('booked'); // Add class for styling booked state
                         buttonElement.disabled = true; // Keep it disabled
                         // Optional: Change row appearance
                         // buttonElement.closest('tr').style.backgroundColor = '#e0ffe0';
                     } else {
                         // Booking failed, re-enable button and show error
                         alert('เกิดข้อผิดพลาดในการจอง: ' + (data.message || 'ไม่สามารถดำเนินการได้'));
                         buttonElement.disabled = false;
                         buttonElement.textContent = 'จอง';
                     }
                 })
                 .catch(error => {
                     console.error('Error during booking request:', error);
                     alert('เกิดข้อผิดพลาดในการสื่อสารกับเซิร์ฟเวอร์: ' + error.message);
                     // Re-enable button on communication error
                     buttonElement.disabled = false;
                     buttonElement.textContent = 'จอง';
                 });

             } else {
                 console.log("Booking cancelled by user.");
             }
        }

    </script>

</body>
</html>