<?php
session_start();
$error = "";

// เชื่อมต่อฐานข้อมูล
$host = "localhost";
$dbname = "classroom_schedule";
$dbuser = "root";
$dbpass = "";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    // ควรใช้ error logging แทนการแสดง error จริงใน production
    error_log("Database connection failed: " . $conn->connect_error);
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่อีกครั้ง"); // แสดงข้อความทั่วไปให้ผู้ใช้
}

// ตั้งค่า character set สำหรับการเชื่อมต่อ
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // อาจจะไม่ใช่ critical error แต่ควร log ไว้
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ควร sanitize input ก่อนใช้งาน
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST["password"]; // รหัสผ่านไม่ต้อง sanitize แต่ต้อง hash
    $remember = isset($_POST["remember"]);

    // --- Security Improvement: Password Hashing ---
    // ดึงเฉพาะ hash รหัสผ่านและ role จากฐานข้อมูล
    // $stmt = $conn->prepare("SELECT password_hash, role FROM users WHERE username = ?");
    // if ($stmt === false) {
    //     error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    //     die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
    // }
    // $stmt->bind_param("s", $username);
    // $stmt->execute();
    // $result = $stmt->get_result();

    // if ($result->num_rows === 1) {
    //     $row = $result->fetch_assoc();
    //     // ตรวจสอบรหัสผ่านที่ hash ไว้
    //     if (password_verify($password, $row['password_hash'])) {
    //         // รหัสผ่านถูกต้อง
    //         session_regenerate_id(true); // ป้องกัน session fixation
    //         $_SESSION["username"] = $username;
    //         $_SESSION["role"] = $row["role"];
    //         $_SESSION['loggedin_time'] = time(); // ตั้งเวลา login สำหรับ session timeout

    //         if ($remember) {
    //             // สร้าง token สำหรับ remember me แทนการเก็บ username ตรงๆ
    //             $selector = bin2hex(random_bytes(16));
    //             $validator = bin2hex(random_bytes(32));
    //             $hashed_validator = password_hash($validator, PASSWORD_DEFAULT);
    //             $expires = time() + (86400 * 30); // 30 days

    //             // เก็บ token ใน database (ต้องสร้างตาราง auth_tokens)
    //             // $sql_insert_token = "INSERT INTO auth_tokens (selector, hashed_validator, userid, expires) VALUES (?, ?, ?, FROM_UNIXTIME(?))";
    //             // $stmt_token = $conn->prepare($sql_insert_token);
    //             // ดึง userid จาก $row หรือ query ใหม่
    //             // $stmt_token->bind_param("ssii", $selector, $hashed_validator, $userid, $expires);
    //             // $stmt_token->execute();
    //             // $stmt_token->close();

    //             // ตั้งค่า cookie
    //             setcookie("remember_user_selector", $selector, $expires, "/", "", isset($_SERVER['HTTPS']), true); // Secure and HttpOnly flags
    //             setcookie("remember_user_validator", $validator, $expires, "/", "", isset($_SERVER['HTTPS']), true); // Secure and HttpOnly flags

    //         } else {
    //              // ลบ cookie ถ้ามีอยู่ (กรณี login ใหม่แล้วไม่ติ๊ก remember)
    //              setcookie("remember_user_selector", "", time() - 3600, "/");
    //              setcookie("remember_user_validator", "", time() - 3600, "/");
    //              // ลบ token เก่าใน database ด้วย
    //         }

    //         header("Location: main.php"); // ส่งไป main.php เสมอ
    //         exit();

    //     } else {
    //         // รหัสผ่านไม่ถูกต้อง
    //         $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    //     }
    // } else {
    //     // ไม่พบชื่อผู้ใช้
    //     $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    // }
    // $stmt->close();
    // --- End Security Improvement ---


    // --- โค้ดเดิม (ไม่ปลอดภัย ไม่ควรใช้ password แบบ plain text) ---
    // ดึง role มาด้วย
    $stmt = $conn->prepare("SELECT role FROM users WHERE username = ? AND password = ?");
    if ($stmt === false) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
    }
    $stmt->bind_param("ss", $username, $password); // **คำเตือน:** เก็บและเปรียบเทียบรหัสผ่านแบบ plain text ไม่ปลอดภัยอย่างยิ่ง!
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        session_regenerate_id(true); // ป้องกัน session fixation
        $_SESSION["username"] = $username;
        $_SESSION["role"] = $row["role"]; // เก็บ role ไว้ใน session
        $_SESSION['loggedin_time'] = time(); // ตั้งเวลา login สำหรับ session timeout

        if ($remember) {
            // **คำเตือน:** การเก็บ username ใน cookie โดยตรงไม่ปลอดภัย ควรใช้ token
            setcookie("remember_user", $username, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true); // เพิ่ม HttpOnly flag
        } else {
             // ลบ cookie ถ้ามีอยู่ (กรณี login ใหม่แล้วไม่ติ๊ก remember)
             setcookie("remember_user", "", time() - 3600, "/");
        }

        // ส่งไปหน้า main.php เสมอ ไม่ว่าจะ role ไหน
        header("Location: main.php");
        exit();

    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }

    $stmt->close();
    // --- End โค้ดเดิม ---
}

// --- Auto login จาก cookie (ปรับปรุง) ---
// if (!isset($_SESSION["username"]) && isset($_COOKIE["remember_user_selector"]) && isset($_COOKIE["remember_user_validator"])) {
//     $selector = $_COOKIE['remember_user_selector'];
//     $validator = $_COOKIE['remember_user_validator'];

//     // ค้นหา token ใน database
//     // $sql_find_token = "SELECT auth_tokens.*, users.username, users.role FROM auth_tokens JOIN users ON auth_tokens.userid = users.id WHERE selector = ? AND expires >= NOW()";
//     // $stmt_find = $conn->prepare($sql_find_token);
//     // $stmt_find->bind_param("s", $selector);
//     // $stmt_find->execute();
//     // $result_token = $stmt_find->get_result();

//     // if ($result_token->num_rows === 1) {
//     //     $token_data = $result_token->fetch_assoc();
//     //     // ตรวจสอบ validator
//     //     if (password_verify($validator, $token_data['hashed_validator'])) {
//     //         // Token ถูกต้อง, ทำการ login
//     //         session_regenerate_id(true);
//     //         $_SESSION["username"] = $token_data['username'];
//     //         $_SESSION["role"] = $token_data['role'];
//     //         $_SESSION['loggedin_time'] = time();

//     //         // (Optional) สร้าง token ใหม่เพื่อให้ใช้งานได้ต่อ (rolling token)
//     //         // ... (สร้าง validator ใหม่, update database, ตั้ง cookie ใหม่) ...

//     //         header("Location: main.php");
//     //         exit();
//     //     } else {
//     //         // Validator ไม่ตรง, อาจมีการพยายามขโมย cookie -> ลบ token ออกจาก db และ cookie
//     //         // $sql_delete_token = "DELETE FROM auth_tokens WHERE selector = ?";
//     //         // $stmt_delete = $conn->prepare($sql_delete_token);
//     //         // $stmt_delete->bind_param("s", $selector);
//     //         // $stmt_delete->execute();
//     //         // $stmt_delete->close();
//     //         setcookie("remember_user_selector", "", time() - 3600, "/");
//     //         setcookie("remember_user_validator", "", time() - 3600, "/");
//     //     }
//     // }
//     // $stmt_find->close();
// }
// --- End Auto login (ปรับปรุง) ---


// --- Auto login จาก cookie (แบบเดิม - ไม่ปลอดภัย) ---
if (!isset($_SESSION["username"]) && isset($_COOKIE["remember_user"])) {
    $remembered_username = $_COOKIE["remember_user"];

    // **สำคัญ:** ควรดึง role จากฐานข้อมูล ไม่ใช่ hardcode เป็น 'user'
    // การ hardcode เป็น 'user' อาจทำให้ admin ที่เคยติ๊ก remember me ถูก login เป็น user ธรรมดา
    $stmt_role = $conn->prepare("SELECT role FROM users WHERE username = ?");
    if ($stmt_role) {
        $stmt_role->bind_param("s", $remembered_username);
        $stmt_role->execute();
        $result_role = $stmt_role->get_result();
        if ($result_role->num_rows === 1) {
            $user_data = $result_role->fetch_assoc();
            session_regenerate_id(true);
            $_SESSION["username"] = $remembered_username;
            $_SESSION["role"] = $user_data["role"]; // ใช้ role ที่ดึงมาจาก DB
            $_SESSION['loggedin_time'] = time();
            header("Location: main.php"); // ส่งไป main.php
            exit();
        } else {
            // ไม่พบ user หรือ user ถูกลบ -> ลบ cookie ที่ไม่ถูกต้อง
             setcookie("remember_user", "", time() - 3600, "/");
        }
        $stmt_role->close();
    } else {
        error_log("Prepare failed for auto-login role check: (" . $conn->errno . ") " . $conn->error);
        // อาจจะแค่ลบ cookie หรือแสดงข้อผิดพลาด
         setcookie("remember_user", "", time() - 3600, "/");
    }
}
// --- End Auto login (แบบเดิม) ---


$conn->close();
?>

<!-- ต่อด้วย HTML ฟอร์ม login -->
<!DOCTYPE html> <!-- ควรใส่ DOCTYPE -->
<html lang="th"> <!-- เพิ่ม lang attribute -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- เพิ่ม viewport -->
    <title>ลงชื่อเข้าใช้ - WhichRoom</title> <!-- เพิ่ม title -->
    <style>
        /* (CSS เหมือนเดิม) */
        body {
            font-family: sans-serif; /* เพิ่ม font พื้นฐาน */
            margin: 0;
            /* แก้ไข URL รูปภาพให้ถูกต้อง */
            background-image: url('https://i.imgur.com/hrSAsdC.png');
            background-size: cover;
            background-position: center;
            min-height: 100vh; /* ใช้ min-height แทน height */
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box; /* ช่วยในการจัดการ padding/border */
        }

        .login-box {
            background: rgba(255, 255, 255, 0.9); /* เพิ่มความโปร่งใสนิดหน่อย */
            padding: 30px 40px; /* ปรับ padding */
            border-radius: 8px; /* ปรับมุม */
            width: 320px; /* ปรับความกว้าง */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* เพิ่มเงา */
            box-sizing: border-box;
        }

        .login-box h2 {
            text-align: center;
            margin-top: 0; /* เอา margin ด้านบนออก */
            margin-bottom: 25px;
            color: #333; /* สีเข้มขึ้น */
            font-weight: 600; /* ปรับความหนา */
        }

        .input-group { /* จัดกลุ่ม label และ input */
             margin-bottom: 15px;
        }

        .login-box label {
            display: block; /* ทำให้ label อยู่บรรทัดเดียวกับ checkbox */
            font-size: 14px;
            color: #555;
            margin-bottom: 5px; /* ระยะห่างจาก input */
            cursor: pointer; /* ทำให้รู้ว่าคลิกได้ */
        }

        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 12px 15px; /* ปรับ padding */
            /* margin: 8px 0; เอา margin ออก ใช้ .input-group แทน */
            border: 1px solid #ccc; /* เพิ่ม border */
            border-radius: 5px;
            outline: none;
            box-sizing: border-box; /* รวม padding/border ใน width */
            font-size: 16px; /* ปรับขนาด font */
            transition: border-color 0.3s; /* เพิ่ม transition */
        }
        .login-box input[type="text"]:focus,
        .login-box input[type="password"]:focus {
             border-color: #0093ff; /* เปลี่ยนสี border ตอน focus */
        }


        .remember-me { /* จัด checkbox กับ label */
            display: flex;
            align-items: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .remember-me input[type="checkbox"] {
             margin-right: 8px; /* ระยะห่างระหว่าง checkbox กับ label */
             cursor: pointer;
        }
         .remember-me label {
             margin-bottom: 0; /* เอา margin ล่างของ label ในนี้ออก */
             font-size: 14px;
             color: #555;
         }


        .login-box input[type="submit"] {
            width: 100%;
            padding: 12px; /* ปรับ padding */
            margin-top: 10px; /* ลด margin */
            background-color: #007bff; /* เปลี่ยนสี */
            color: #ffffff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px; /* ปรับขนาด font */
            transition: background-color 0.3s; /* เพิ่ม transition */
        }
        .login-box input[type="submit"]:hover {
             background-color: #0056b3; /* สีเข้มขึ้นเมื่อ hover */
        }

        .error {
            color: #dc3545; /* สีแดงมาตรฐาน */
            background-color: #f8d7da; /* พื้นหลังสีแดงอ่อน */
            border: 1px solid #f5c6cb; /* ขอบสีแดง */
            padding: 10px 15px; /* ปรับ padding */
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>ลงชื่อเข้าใช้</h2>
        <?php if (!empty($error)) { echo "<p class='error'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>"; } // ใช้ htmlspecialchars ป้องกัน XSS ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); // ป้องกัน XSS ?>">
            <div class="input-group">
                 <label for="username">ชื่อผู้ใช้:</label> <!-- เพิ่ม label และ for attribute -->
                 <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; // แสดงค่าเดิมถ้า login ไม่ผ่าน ?>">
            </div>
            <div class="input-group">
                 <label for="password">รหัสผ่าน:</label> <!-- เพิ่ม label และ for attribute -->
                 <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
            </div>
            <div class="remember-me">
                 <input type="checkbox" id="remember" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                 <label for="remember">จำฉันไว้ในระบบ</label> <!-- ใช้ for attribute -->
            </div>
            <input type="submit" value="เข้าสู่ระบบ">
        </form>
    </div>
</body>
</html>
