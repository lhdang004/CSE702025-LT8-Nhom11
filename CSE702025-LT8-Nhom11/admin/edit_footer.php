<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:admin_login.php');
}

// Lấy thông tin footer từ cơ sở dữ liệu
$select_footer = $conn->prepare("SELECT * FROM `footer` LIMIT 1");
$select_footer->execute();
$footer_info = $select_footer->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['update_footer'])) {
    $email1 = filter_var($_POST['email1'], FILTER_SANITIZE_EMAIL);
    $email2 = filter_var($_POST['email2'], FILTER_SANITIZE_EMAIL);
    $opening_hours = htmlspecialchars(trim($_POST['opening_hours']));
    $address = htmlspecialchars(trim($_POST['address']));
    $phone1 = htmlspecialchars(trim($_POST['phone1']));
    $phone2 = htmlspecialchars(trim($_POST['phone2']));

    // Cập nhật thông tin footer
    $update_footer = $conn->prepare("UPDATE `footer` SET email1 = ?, email2 = ?, opening_hours = ?, address = ?, phone1 = ?, phone2 = ? WHERE id = 1");
    $update_footer->execute([$email1, $email2, $opening_hours, $address, $phone1, $phone2]);

    $message[] = 'Footer updated successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Footer</title>
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="../css/style.css"> <!-- Thêm CSS chính của bạn -->
</head>

<body>

    <?php include '../components/admin_header.php'; ?>

    <div class="container">
        <h1>Edit Footer</h1>

        <?php if (isset($message)): ?>
            <p class="message"><?= $message; ?></p>
        <?php endif; ?>

        <form action="" method="POST" class="footer-edit-form">
            <div class="form-group">
                <label for="email1">Email 1:</label>
                <input type="email" name="email1" id="email1"
                    value="<?= $footer_info ? htmlspecialchars($footer_info['email1']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="email2">Email 2:</label>
                <input type="email" name="email2" id="email2"
                    value="<?= $footer_info ? htmlspecialchars($footer_info['email2']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="opening_hours">Opening Hours:</label>
                <input type="text" name="opening_hours" id="opening_hours"
                    value="<?= $footer_info ? htmlspecialchars($footer_info['opening_hours']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" name="address" id="address"
                    value="<?= $footer_info ? htmlspecialchars($footer_info['address']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone1">Phone 1:</label>
                <input type="text" name="phone1" id="phone1"
                    value="<?= $footer_info ? htmlspecialchars($footer_info['phone1']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone2">Phone 2:</label>
                <input type="text" name="phone2" id="phone2"
                    value="<?= $footer_info ? htmlspecialchars($footer_info['phone2']) : ''; ?>" required>
            </div>
            <input type="submit" name="update_footer" value="Update Footer" class="btn">
        </form>
    </div>

    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #218838;
        }

        .message {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>

</body>

</html>