<?php

include 'components/connect.php';

session_start();

if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
} else {
   $user_id = '';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About Us</title>

   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">


</head>

<body>

   <!-- Header section starts -->
   <?php include 'components/user_header.php'; ?>
   <!-- Header section ends -->

   <div class="heading">
      <h3>About Us</h3>
      <p><a href="home.php">Home</a> <span> / About</span></p>
   </div>

   <!-- About section starts -->
   <section class="about">
      <div class="row">
         <div class="image">
            <img src="images/about-img.png" alt="About Us">
         </div>
         <div class="content">
            <h3>Tại sao lại chọn chúng tôi?</h3>
            <p>Nhà hàng sushi của chúng tôi hân hạnh mang đến cho bạn trải nghiệm
               ẩm thực tinh tế với các món sushi tuyệt hảo. Với đội ngũ đầu bếp tài
               ba, chúng tôi cam kết sử dụng nguyên liệu tươi ngon nhất, đảm bảo
               mỗi món ăn đều đạt đỉnh cao của hương vị và nghệ thuật. Hãy đến và
               thưởng thức không gian ấm cúng, thân thiện cùng dịch vụ chuyên
               nghiệp của chúng tôi. Chúng tôi luôn sẵn sàng chào đón và mang đến
               cho bạn những khoảnh khắc ẩm thực khó quên. 🍣✨</p>
            <a href="menu.php" class="btn">Our Menu</a>
         </div>
      </div>
   </section>
   <!-- About section ends -->

   <!-- Steps section starts -->
   <section class="steps">
      <h1 class="title">Simple Steps</h1>
      <div class="box-container">
         <div class="box">
            <img src="images/step-1.png" alt="Choose Orders" />
            <h3>Choose Orders</h3>
            <p>Đặt món sushi tươi ngon dễ dàng và nhanh chóng với dịch vụ của chúng tôi. 🍣🚀</p>
         </div>
         <div class="box">
            <img src="images/step-2.png" alt="Fast Delivery" />
            <h3>Fast Delivery</h3>
            <p>Đặt món sushi nhanh chóng và dễ dàng! Chúng tôi giao hàng tươi ngon. 🍣🚀</p>
         </div>
         <div class="box">
            <img src="images/step-3.png" alt="Enjoy Food" />
            <h3>Enjoy Food</h3>
            <p>Hãy thưởng thức món sushi ngon tại nhà hàng của chúng tôi và trải nghiệm! 🍣✨</p>
         </div>
      </div>
   </section>
   <!-- Steps section ends -->

   <!-- Reviews section starts -->
   <?php
   // Mảng chứa đường dẫn đến các ảnh
   $images = [
      'images/pic-1.png',
      'images/pic-2.png',
      'images/pic-3.png',
      'images/pic-4.png',
      'images/pic-5.png',

   ];

   // Lấy 5 đánh giá ngẫu nhiên
   $select_reviews = $conn->prepare("SELECT r.rating, r.comment, u.name FROM `reviews` r JOIN `users` u ON r.user_id = u.id ORDER BY RAND() LIMIT 5");
   $select_reviews->execute();
   $reviews = $select_reviews->fetchAll(PDO::FETCH_ASSOC);
   ?>

   <section class="reviews">
      <h2>User Reviews</h2>
      <div class="swiper reviews-slider">
         <div class="swiper-wrapper">
            <?php if (count($reviews) > 0): ?>
               <?php foreach ($reviews as $review): ?>
                  <div class="swiper-slide">
                     <div class="review-box">
                        <img src="<?= $images[array_rand($images)]; ?>" alt="Review Image"
                           style="width: 50%; height: auto; border-radius: 5px; margin-bottom: 10px;">
                        <p><strong><?= htmlspecialchars($review['name']); ?></strong></p>
                        <p>Rating: <?= str_repeat('⭐', $review['rating']); ?></p>
                        <p><?= htmlspecialchars($review['comment']); ?></p>
                     </div>
                  </div>
               <?php endforeach; ?>
            <?php else: ?>
               <p>No reviews available.</p>
            <?php endif; ?>
         </div>
         <div class="swiper-pagination"></div>
      </div>
   </section>
   <!-- Reviews section ends -->

   <!-- Footer section starts -->
   <?php include 'components/footer.php'; ?>
   <!-- Footer section ends -->

   <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
   <script src="js/script.js"></script>

   <script>
      var swiper = new Swiper(".reviews-slider", {
         loop: true,
         grabCursor: true,
         spaceBetween: 20,
         pagination: {
            el: ".swiper-pagination",
            clickable: true,
         },
         breakpoints: {
            0: {
               slidesPerView: 1,
            },
            700: {
               slidesPerView: 2,
            },
            1024: {
               slidesPerView: 3,
            },
         },
      });
   </script>

</body>

</html>