<?php

include 'components/connect.php';

session_start();

if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
} else {
   $user_id = '';
   header('location:home.php');
}
;

if (isset($_POST['submit'])) {

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_SPECIAL_CHARS);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_SPECIAL_CHARS);
   $method = $_POST['method'];
   $method = filter_var($method, FILTER_SANITIZE_SPECIAL_CHARS);
   $dining_option = $_POST['dining_option'];
   $dining_option = filter_var($dining_option, FILTER_SANITIZE_SPECIAL_CHARS);
   $address = "";
   $table_id = 0;

   // If home delivery, get address
   if ($dining_option == 'delivery') {
      $address = $_POST['address'];
      $address = filter_var($address, FILTER_SANITIZE_SPECIAL_CHARS);
   }
   // If dine-in, get table_id
   else if ($dining_option == 'dine_in' && isset($_POST['table_id'])) {
      $table_id = $_POST['table_id'];
      $table_id = filter_var($table_id, FILTER_SANITIZE_NUMBER_INT);
   }

   $total_products = $_POST['total_products'];
   $total_price = $_POST['total_price'];

   $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $check_cart->execute([$user_id]);

   if ($check_cart->rowCount() > 0) {

      // Check if delivery option is selected but no address provided
      if ($dining_option == 'delivery' && $address == '') {
         $message[] = 'Please add your delivery address!';
      }
      // Check if dine-in option is selected but no table selected
      else if ($dining_option == 'dine_in' && $table_id == 0) {
         $message[] = 'Please select a table for dining!';
      } else {
         try {
            $conn->beginTransaction();

            // Insert order
            $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, dining_option) VALUES(?,?,?,?,?,?,?,?,?)");
            $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $total_price, $dining_option]);
            $order_id = $conn->lastInsertId();

            // If dine-in option and a table is selected
            if ($dining_option == 'dine_in' && $table_id > 0) {
               // Check if the table is already reserved by this user
               $check_existing_reservation = $conn->prepare("
                  SELECT * FROM `reservations` 
                  WHERE user_id = ? AND table_id = ?
               ");
               $check_existing_reservation->execute([$user_id, $table_id]);

               // Table is not already reserved by this user
               if ($check_existing_reservation->rowCount() == 0) {
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
               }
               // Table is already reserved by this user - we'll just reference it in the order
               else {
                  // Link the existing reservation to this order
                  $update_reservation = $conn->prepare("
                     UPDATE `reservations` 
                     SET order_id = ? 
                     WHERE user_id = ? AND table_id = ?
                  ");
                  $update_reservation->execute([$order_id, $user_id, $table_id]);
               }
            }

            // Delete cart items
            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart->execute([$user_id]);

            $conn->commit();
            $message[] = 'Order placed successfully!';
         } catch (PDOException $e) {
            $conn->rollBack();
            $message[] = 'System error: ' . $e->getMessage();
         }
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
   <title>checkout</title>

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

   <section class="checkout">

      <h1 class="title">order summary</h1>

      <form action="" method="post">

         <div class="cart-items">
            <h3>cart items</h3>
            <?php
            $grand_total = 0;
            $cart_items[] = '';
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if ($select_cart->rowCount() > 0) {
               while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                  $cart_items[] = $fetch_cart['name'] . ' (' . $fetch_cart['price'] . ' x ' . $fetch_cart['quantity'] . ') - ';
                  $total_products = implode($cart_items);
                  $grand_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
                  ?>
                  <p><span class="name"><?= $fetch_cart['name']; ?></span><span class="price">$<?= $fetch_cart['price']; ?> x
                        <?= $fetch_cart['quantity']; ?></span></p>
                  <?php
               }
            } else {
               echo '<p class="empty">your cart is empty!</p>';
            }
            ?>
            <p class="grand-total"><span class="name">grand total :</span><span
                  class="price">$<?= $grand_total; ?></span></p>
            <a href="cart.php" class="btn">view cart</a>
         </div>

         <input type="hidden" name="total_products" value="<?= $total_products; ?>">
         <input type="hidden" name="total_price" value="<?= $grand_total; ?>" value="">
         <input type="hidden" name="name" value="<?= $fetch_profile['name'] ?>">
         <input type="hidden" name="number" value="<?= $fetch_profile['number'] ?>">
         <input type="hidden" name="email" value="<?= $fetch_profile['email'] ?>">

         <div class="dining-options">
            <h3>Choose dining option</h3>
            <div class="option-container">
               <div class="option-btn active" data-option="dine_in">
                  <i class="fas fa-utensils"></i>
                  <p>Dine in restaurant</p>
               </div>

            </div>

            <input type="hidden" name="dining_option" id="dining-option-field" value="dine_in">

            <!-- Table selection for dine-in option -->
            <div class="table-selection active">
               <h3>Your Reserved Tables</h3>
               <?php if (count($reserved_tables) > 0): ?>
                  <div class="table-grid">
                     <?php foreach ($reserved_tables as $table): ?>
                        <div class="table-card" data-table-id="<?= $table['id']; ?>">
                           <?php if ($table['order_id']): ?>
                              <div class="badge" title="Table used in an order"><i class="fas fa-check"></i></div>
                           <?php endif; ?>
                           <h4>Table <?= $table['table_number']; ?></h4>
                           <p>Capacity: <?= $table['capacity']; ?> people</p>
                           <p>Reserved: <?= date('M d, Y H:i', strtotime($table['reservation_time'])); ?></p>
                        </div>
                     <?php endforeach; ?>
                  </div>
                  <input type="hidden" name="table_id" id="selected-table" value="">
               <?php else: ?>
                  <div class="no-tables-message">
                     <p>You don't have any reserved tables.</p>
                     <a href="reserve_table.php" class="reserve-button">
                        <i class="fas fa-calendar-plus"></i> Reserve a Table
                     </a>
                  </div>
               <?php endif; ?>
            </div>

            <!-- Address for delivery option -->


            <h3>payment method</h3>
            <select name="method" class="box" required>
               <option value="" disabled selected>select payment method --</option>
               <option value="cash on delivery">cash on delivery</option>
               <option value="credit card">credit card</option>
               <option value="paytm">paytm</option>
               <option value="paypal">paypal</option>
            </select>
            <input type="submit" value="place order" class="btn"
               style="width:100%; background:var(--red); color:var(--white);" name="submit">
         </div>

      </form>

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
      const deliveryAddress = document.querySelector('.delivery-address');

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
               deliveryAddress.classList.remove('active');
            } else {
               tableSelection.classList.remove('active');
               deliveryAddress.classList.add('active');
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

      // Prevent form submission if required fields are not filled
      document.querySelector('form').addEventListener('submit', function (e) {
         const diningOption = diningOptionField.value;

         if (diningOption === 'dine_in' && selectedTableField.value === '' && document.querySelectorAll('.table-card').length > 0) {
            e.preventDefault();
            alert('Please select a table for dining.');
         } else if (diningOption === 'delivery' && document.querySelector('input[name="address"]').value === '') {
            e.preventDefault();
            alert('Please add your delivery address.');
         }
      });
   </script>

</body>

</html>