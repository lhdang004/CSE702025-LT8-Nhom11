<?php
include 'components/connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('location:home.php');
}

if (isset($_POST['submit_review'])) {
    $user_id = $_SESSION['user_id'];
    $rating = filter_var($_POST['rating'], FILTER_SANITIZE_NUMBER_INT);
    $comment = htmlspecialchars(trim($_POST['comment']));

    // Chèn đánh giá vào cơ sở dữ liệu
    $insert_review = $conn->prepare("INSERT INTO `reviews` (user_id, rating, comment) VALUES (?, ?, ?)");
    $insert_review->execute([$user_id, $rating, $comment]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review</title>
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/style.css">

    <style>

    </style>
</head>

<body>

    <?php include 'components/user_header.php'; ?>

    <div class="container">
        <h1>Write a Review</h1>

        <?php if (isset($message)): ?>
            <p class="message"><?= $message; ?>
                <button class="close-btn" onclick="this.parentElement.style.display='none';">X</button>
            </p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="rating">Rating:</label>
                <select name="rating" id="rating" required>
                    <option value="" disabled selected>Select Rating</option>
                    <option value="1">1 Star</option>
                    <option value="2">2 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="5">5 Stars</option>
                </select>
            </div>
            <div class="form-group">
                <label for="comment">Comment:</label>
                <textarea name="comment" id="comment" rows="5" required></textarea>
            </div>
            <input type="submit" name="submit_review" value="Submit Review" class="btn">
        </form>
    </div>

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

    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
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