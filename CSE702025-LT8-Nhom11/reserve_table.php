<?php
include 'components/connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location:home.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = [];

// Handle table reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table_id'], $_POST['name'], $_POST['phone'], $_POST['reservation_time'])) {
    $table_id = filter_var($_POST['table_id'], FILTER_SANITIZE_NUMBER_INT);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_SPECIAL_CHARS);
    $reservation_time = $_POST['reservation_time'];

    $check_table = $conn->prepare("SELECT * FROM `tables` WHERE id = ? AND status = 'available'");
    $check_table->execute([$table_id]);

    if ($check_table->rowCount() > 0) {
        try {
            $conn->beginTransaction();

            $update_table = $conn->prepare("UPDATE `tables` SET status = 'reserved' WHERE id = ? AND status = 'available'");
            $result = $update_table->execute([$table_id]);

            if ($result && $update_table->rowCount() > 0) {
                $insert_reservation = $conn->prepare("INSERT INTO `reservations` (user_id, table_id, name, phone, reservation_time) VALUES (?, ?, ?, ?, ?)");
                $insert_reservation->execute([$user_id, $table_id, $name, $phone, $reservation_time]);

                $conn->commit();

                $_SESSION['reservation_success'] = true;
                header("Location: reserve_table.php");
                exit;
            } else {
                $conn->rollBack();
                $message[] = 'The table has been reserved by someone else!';
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $message[] = 'System error: ' . $e->getMessage();
        }
    } else {
        $message[] = 'The table has already been reserved!';
    }
}

// Handle delete reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
    $reservation_id = filter_var($_POST['reservation_id'], FILTER_SANITIZE_NUMBER_INT);
    $table_id = filter_var($_POST['table_id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $conn->beginTransaction();

        // Check if the reservation belongs to the current user
        $check_reservation = $conn->prepare("SELECT * FROM `reservations` WHERE id = ? AND user_id = ?");
        $check_reservation->execute([$reservation_id, $user_id]);

        if ($check_reservation->rowCount() > 0) {
            // Delete the reservation
            $delete_reservation = $conn->prepare("DELETE FROM `reservations` WHERE id = ? AND user_id = ?");
            $delete_reservation->execute([$reservation_id, $user_id]);

            // Update table status back to available
            $update_table = $conn->prepare("UPDATE `tables` SET status = 'available' WHERE id = ?");
            $update_table->execute([$table_id]);

            $conn->commit();
            $message[] = 'Reservation cancelled successfully!';
        } else {
            $conn->rollBack();
            $message[] = 'Unauthorized action or reservation not found!';
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $message[] = 'System error: ' . $e->getMessage();
    }
}

if (isset($_SESSION['reservation_success']) && $_SESSION['reservation_success'] === true) {
    $message[] = 'Table reservation successful!';
    unset($_SESSION['reservation_success']);
}

$select_tables = $conn->prepare("SELECT * FROM `tables` WHERE `status` = 'available'");
$select_tables->execute();

$select_reserved_tables = $conn->prepare("
    SELECT r.*, t.table_number, t.capacity 
    FROM `reservations` r 
    INNER JOIN `tables` t ON r.table_id = t.id 
    WHERE r.user_id = ?
    ORDER BY r.reservation_time DESC
");
$select_reserved_tables->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Table</title>
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .delete-btn {
            background-color: #ff5252;
            color: #fff;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background-color: #ff0000;
        }

        .reserved-tables table {
            width: 100%;
        }
    </style>
</head>

<body>

    <?php include 'components/user_header.php'; ?>

    <div class="heading">
        <h3>Reserve Table</h3>
        <p><a href="home.php">Home</a> <span> / Reserve Table</span></p>
    </div>

    <section class="reserve-table">

        <?php
        if (!isset($message) || !is_array($message)) {
            $message = [];
        }

        if (!empty($message)): ?>
            <div class="message">
                <?php foreach ($message as $msg): ?>
                    <p><?= $msg; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="form-container">
            <label for="table">Choose a table:</label>
            <select name="table_id" required class="box">
                <?php if ($select_tables->rowCount() > 0): ?>
                    <?php while ($table = $select_tables->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?= htmlspecialchars($table['id']); ?>">Table Number
                            <?= htmlspecialchars($table['table_number']); ?> (Capacity:
                            <?= htmlspecialchars($table['capacity']); ?>)
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="">No available tables</option>
                <?php endif; ?>
            </select>
            <input type="text" name="name" placeholder="Enter your name" required class="box">
            <input type="text" name="phone" placeholder="Enter your phone number" required class="box">
            <input type="datetime-local" name="reservation_time" required class="box">
            <input type="submit" value="Reserve Table" class="btn" <?= ($select_tables->rowCount() == 0) ? 'disabled' : ''; ?>>
        </form>

    </section>

    <section class="reserved-tables">
        <h3>Your booked table</h3>
        <?php if ($select_reserved_tables->rowCount() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Table Number</th>
                        <th>Capacity</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Reservation Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reserved_table = $select_reserved_tables->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($reserved_table['table_number']); ?></td>
                            <td><?= htmlspecialchars($reserved_table['capacity']); ?></td>
                            <td><?= htmlspecialchars($reserved_table['name']); ?></td>
                            <td><?= htmlspecialchars($reserved_table['phone']); ?></td>
                            <td><?= htmlspecialchars($reserved_table['reservation_time']); ?></td>
                            <td>
                                <form action="" method="POST"
                                    onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                    <input type="hidden" name="reservation_id" value="<?= $reserved_table['id']; ?>">
                                    <input type="hidden" name="table_id" value="<?= $reserved_table['table_id']; ?>">
                                    <button type="submit" name="delete_reservation" class="delete-btn">
                                        <i class="fas fa-trash"></i> Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 250%; padding-left: 38%;">You have not reserved any tables.</p>
        <?php endif; ?>
    </section>

    <?php include 'components/footer.php'; ?>

    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <script src="js/script.js"></script>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

</body>

</html>