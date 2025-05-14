<?php

include '../components/connect.php'; // Kết nối đến cơ sở dữ liệu

session_start(); // Bắt đầu phiên làm việc

$admin_id = $_SESSION['admin_id']; // Lấy ID admin từ phiên làm việc

if (!isset($admin_id)) {
    header('location:admin_login.php'); // Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
}

// Lấy ID bàn từ URL
if (isset($_GET['id'])) {
    $table_id = $_GET['id'];
    $select_table = $conn->prepare("SELECT * FROM `tables` WHERE id = ?");
    $select_table->execute([$table_id]);
    $fetch_table = $select_table->fetch(PDO::FETCH_ASSOC);
}

// Xử lý cập nhật bàn
if (isset($_POST['update_table'])) {
    $table_number = $_POST['table_number'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    $update_table = $conn->prepare("UPDATE `tables` SET table_number = ?, capacity = ?, status = ? WHERE id = ?");
    $update_table->execute([$table_number, $capacity, $status, $table_id]);

    header('location:table.php'); // Chuyển hướng về trang quản lý bàn
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Table</title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Custom CSS file link -->
    <link rel="stylesheet" href="../css/admin_style.css">

</head>

<body>

    <?php include '../components/admin_header.php'; ?> <!-- Header cho admin -->

    <!-- Sửa bàn section starts -->

    <section class="edit-table">

        <h1 class="heading">Edit Table</h1>

        <form action="" method="POST" class="form-container">
            <input type="text" name="table_number" value="<?= htmlspecialchars($fetch_table['table_number']); ?>"
                required class="box" placeholder="Table Number">
            <input type="number" name="capacity" value="<?= htmlspecialchars($fetch_table['capacity']); ?>" required
                class="box" placeholder="Capacity">
            <select name="status" class="box">
                <option value="available" <?= $fetch_table['status'] == 'available' ? 'selected' : ''; ?>>Available
                </option>
                <option value="reserved" <?= $fetch_table['status'] == 'reserved' ? 'selected' : ''; ?>>Reserved</option>
            </select>
            <input type="submit" name="update_table" value="Update" class="btn">
        </form>

    </section>

    <!-- Sửa bàn section ends -->

    <!-- Custom JS file link -->
    <script src="../js/admin_script.js"></script>

</body>

</html>