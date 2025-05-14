<?php
include '../components/connect.php'; // Connect to the database

session_start(); // Start the session

$admin_id = $_SESSION['admin_id']; // Get admin ID from the session

if (!isset($admin_id)) {
    header('location:admin_login.php'); // If not logged in, redirect to the login page
}

// Handle adding a new table
if (isset($_POST['add_table'])) {
    $table_number = $_POST['table_number'];
    $capacity = $_POST['capacity'];

    $insert_table = $conn->prepare("INSERT INTO `tables` (table_number, capacity, status) VALUES (?, ?, 'available')");
    $insert_table->execute([$table_number, $capacity]);
    $message[] = 'New table has been added!';
}

// Handle deleting a table
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_table = $conn->prepare("DELETE FROM `tables` WHERE id = ?");
    $delete_table->execute([$delete_id]);
    header('location:table.php'); // Redirect back to the table management page
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tables</title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Custom CSS file link -->
    <link rel="stylesheet" href="../css/admin_style.css">

</head>

<body>

    <?php include '../components/admin_header.php'; ?> <!-- Header for admin -->

    <!-- Manage tables section starts -->

    <section class="manage-tables">

        <h1 class="heading">Manage Tables</h1>

        <form action="" method="POST">
            <input type="text" name="table_number" placeholder="Table Number" required class="box">
            <input type="number" name="capacity" placeholder="Capacity" required class="box">
            <input type="submit" name="add_table" value="Add Table" class="btn">
        </form>

        <div class="box-container">

            <?php
            $select_tables = $conn->prepare("SELECT * FROM `tables`");
            $select_tables->execute();
            if ($select_tables->rowCount() > 0) {
                while ($fetch_table = $select_tables->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <div class="box">
                        <p> Table Number: <span><?= $fetch_table['table_number']; ?></span> </p>
                        <p> Capacity: <span><?= $fetch_table['capacity']; ?></span> </p>
                        <p> Status: <span><?= $fetch_table['status']; ?></span> </p>
                        <a href="edit_table.php?id=<?= $fetch_table['id']; ?>" class="btn">Edit</a>
                        <a href="table.php?delete=<?= $fetch_table['id']; ?>" class="delete-btn"
                            onclick="return confirm('Are you sure you want to delete this table?');">Delete</a>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="empty">No tables found!</p>';
            }
            ?>

        </div>

    </section>

    <!-- Manage tables section ends -->

    <!-- Custom JS file link -->
    <script src="../js/admin_script.js"></script>

</body>

</html>