<?php

include 'components/connect.php';

session_start();

if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
} else {
   $user_id = '';
   header('location:home.php');
   exit();
}

if (isset($_POST['submit'])) {

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_SPECIAL_CHARS);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_SPECIAL_CHARS);
   $method = $_POST['method'];
   $method = filter_var($method, FILTER_SANITIZE_SPECIAL_CHARS);
   $dining_option = 'dine_in'; // Chỉ cho phép ăn tại nhà hàng
   $table_id = $_POST['table_id']; // Lấy table_id từ form

   $total_products = $_POST['total_products'];
   $total_price = $_POST['total_price'];

   $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $check_cart->execute([$user_id]);

   if ($check_cart->rowCount() > 0) {
      try {
         $conn->beginTransaction();

         // Modified: Change 'table_number' to 'table_id'
         $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, total_products, total_price, dining_option, table_id) VALUES(?,?,?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $email, $method, $total_products, $total_price, $dining_option, $table_id]);
         $order_id = $conn->lastInsertId();

         // Check if table is available
         $check_table = $conn->prepare("SELECT * FROM `tables` WHERE id = ? AND status = 'available'");
         $check_table->execute([$table_id]);

         if ($check_table->rowCount() > 0) {
            // Update table status to reserved
            $update_table = $conn->prepare("UPDATE `tables` SET status = 'reserved' WHERE id = ?");
            $update_table->execute([$table_id]);

            // Insert reservation with current timestamp
            $insert_reservation = $conn->prepare("INSERT INTO `reservations`(user_id, table_id, name, phone, reservation_time, order_id) VALUES(?,?,?,?,NOW(),?)");
            $insert_reservation->execute([$user_id, $table_id, $name, $number, $order_id]);
         } else {
            $conn->rollBack();
            $message[] = 'The selected table is no longer available!';
            goto end_processing;
         }

         // Delete cart items
         $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart->execute([$user_id]);

         $conn->commit();
         $message[] = 'Order placed successfully!';
      } catch (PDOException $e) {
         $conn->rollBack();
         // Add error logging
         error_log('Order processing error: ' . $e->getMessage());
         $message[] = 'System error: ' . $e->getMessage();
      }
   } else {
      $message[] = 'Your cart is empty';
   }
}
end_processing:

// Get user's reserved tables
$select_reserved_tables = $conn->prepare("
   SELECT t.*, r.reservation_time, r.id as reservation_id, r.order_id 
   FROM `tables` t 
   JOIN `reservations` r ON t.id = r.table_id 
   WHERE r.user_id = ? AND t.status = 'reserved'
   ORDER BY r.reservation_time DESC
");
$select_reserved_tables->execute([$user_id]);
$reserved_tables = $select_reserved_tables->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
</head>

<body>
   <!-- header section starts  -->
   <?php include 'components/user_header.php'; ?>
   <!-- header section ends -->

   <div class="heading">
      <h3>checkout</h3>
      <p><a href="home.php">home</a> <span> / checkout</span></p>
   </div>

   <section class="orders">

      <h1 class="heading">Orders</h1>

      <div class="box-container">

         <?php
         $select_orders = $conn->prepare("SELECT * FROM `orders`");
         $select_orders->execute();
         if ($select_orders->rowCount() > 0) {
            while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
               ?>
               <div class="box">
                  <p> User ID: <span><?= $fetch_orders['user_id']; ?></span> </p>
                  <p> Placed On: <span><?= $fetch_orders['placed_on']; ?></span> </p>
                  <p> Name: <span><?= $fetch_orders['name']; ?></span> </p>
                  <p> Payment Method: <span><?= $fetch_orders['method']; ?></span> </p>
                  <p> Total Products: <span><?= $fetch_orders['total_products']; ?></span> </p>
                  <p> Total Price: <span>$<?= $fetch_orders['total_price']; ?>/-</span> </p>
                  <p> Payment Status: <span
                        style="color: <?= ($fetch_orders['payment_status'] == 'pending') ? 'red' : 'green'; ?>"><?= htmlspecialchars($fetch_orders['payment_status']); ?></span>
                  </p>

                  <?php if ($fetch_orders['dining_option'] == 'dine_in'): ?>
                     <?php
                     // Lấy tên bàn từ bảng reservations
                     $select_table = $conn->prepare("SELECT t.table_number FROM `reservations` r JOIN `tables` t ON r.table_id = t.id WHERE r.order_id = ?");
                     $select_table->execute([$fetch_orders['id']]);
                     $fetch_table = $select_table->fetchAll(PDO::FETCH_ASSOC);
                     if (count($fetch_table) > 0): // Kiểm tra xem có bàn nào không
                        ?>
                        <p> Table Number: <span><?= implode(', ', array_column($fetch_table, 'table_number')); ?></span> </p>
                     <?php else: ?>
                        <p> Table Number: <span>Not assigned</span> </p>
                     <?php endif; ?>
                  <?php endif; ?>


               </div>
               <?php
            }
         } else {
            echo '<p class="empty">No orders placed yet!</p>';
         }
         ?>

      </div>

   </section>

   <!-- footer section starts  -->
   <?php include 'components/footer.php'; ?>
   <!-- footer section ends -->

   <!-- custom js file link  -->
   <script src="js/script.js"></script>

   <script>
      // Toggle between dining options
      const optionBtns = document.querySelectorAll('.option-btn');
      const diningOptionField = document.getElementById('dining-option-field');
      const tableSelection = document.querySelector('.table-selection');

      optionBtns.forEach(btn => {
         btn.addEventListener('click', function () {
            // Remove active class from all buttons
            optionBtns.forEach(b => b.classList.remove('active'));

            // Add active class to clicked button
            this.classList.add('active');

            // Update hidden field with selected option
            const option = this.dataset.option;
            diningOptionField.value = option;

            // Show/hide relevant sections
            if (option === 'dine_in') {
               tableSelection.classList.add('active');
            }
         });
      });

      // Table selection
      const tableCards = document.querySelectorAll('.table-card');
      const selectedTableField = document.getElementById('selected-table');

      tableCards.forEach(card => {
         card.addEventListener('click', function () {
            // Remove selected class from all cards
            tableCards.forEach(c => c.classList.remove('selected'));

            // Add selected class to clicked card
            this.classList.add('selected');

            // Update hidden field with selected table ID
            selectedTableField.value = this.dataset.tableId;
         });
      });
   </script>
</body>

</html>