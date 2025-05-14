<?php
include '../components/connect.php'; // Connect to the database

session_start(); // Start the session

$admin_id = $_SESSION['admin_id']; // Get admin ID from the session

if (!isset($admin_id)) {
    header('location:admin_login.php'); // If not logged in, redirect to the login page
}

// Handle reservation deletion
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    // Update table status to "available" before deleting the reservation
    $update_table_status = $conn->prepare("UPDATE `tables` SET status = 'available' WHERE id = (SELECT table_id FROM `reservations` WHERE id = ?)");
    $update_table_status->execute([$delete_id]);

    // Delete the reservation
    $delete_reservation = $conn->prepare("DELETE FROM `reservations` WHERE id = ?");
    $delete_reservation->execute([$delete_id]);

    header('location:manage_reservations.php'); // Redirect back to the reservation management page
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Custom CSS file link -->
    <link rel="stylesheet" href="../css/admin_style.css">

</head>

<body>

    <?php include '../components/admin_header.php'; ?> <!-- Header for admin -->

    <!-- Reservation management section starts -->

    <section class="reservations">

        <h1 class="heading">Manage Reservations</h1>

        <div class="box-container">

            <?php
            // Query to get reservation information and table numbers
            $select_reservations = $conn->prepare("SELECT r.*, t.table_number FROM `reservations` r JOIN `tables` t ON r.table_id = t.id");
            $select_reservations->execute();
            if ($select_reservations->rowCount() > 0) {
                while ($fetch_reservation = $select_reservations->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <div class="box">
                        <p> Table Number: <span><?= $fetch_reservation['table_number']; ?></span> </p>
                        <p> Name: <span><?= $fetch_reservation['name']; ?></span> </p>
                        <p> Phone Number: <span><?= $fetch_reservation['phone']; ?></span> </p>
                        <p> Reservation Time: <span><?= $fetch_reservation['reservation_time']; ?></span> </p>
                        <a href="manage_reservations.php?delete=<?= $fetch_reservation['id']; ?>" class="delete-btn"
                            onclick="return confirm('Are you sure you want to delete this reservation?');">Delete</a>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="empty">No reservations found!</p>';
            }
            ?>

        </div>

    </section>

    <!-- Reservation management section ends -->

    <!-- Custom JS file link -->
    <script src="../js/admin_script.js"></script>

</body>

</html>