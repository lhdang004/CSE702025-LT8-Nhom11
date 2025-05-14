<footer class="footer">
   <section class="grid">
      <?php
      // Lấy thông tin footer từ cơ sở dữ liệu
      $select_footer = $conn->prepare("SELECT * FROM `footer` LIMIT 1");
      $select_footer->execute();
      $footer_info = $select_footer->fetch(PDO::FETCH_ASSOC);


      if ($footer_info) {
         ?>
         <div class="box">
            <img src="images/email-icon.png" alt="">
            <h3>Our Email</h3>
            <a
               href="mailto:<?= htmlspecialchars($footer_info['email1']); ?>"><?= htmlspecialchars($footer_info['email1']); ?></a>
            <a
               href="mailto:<?= htmlspecialchars($footer_info['email2']); ?>"><?= htmlspecialchars($footer_info['email2']); ?></a>
         </div>

         <div class="box">
            <img src="images/clock-icon.png" alt="">
            <h3>Opening Hours</h3>
            <p><?= htmlspecialchars($footer_info['opening_hours']); ?></p>
         </div>

         <div class="box">
            <img src="images/map-icon.png" alt="">
            <h3>Our Address</h3>
            <a href="#"><?= htmlspecialchars($footer_info['address']); ?></a>
         </div>

         <div class="box">
            <img src="images/phone-icon.png" alt="">
            <h3>Our Number</h3>
            <a
               href="tel:<?= htmlspecialchars($footer_info['phone1']); ?>"><?= htmlspecialchars($footer_info['phone1']); ?></a>
            <a
               href="tel:<?= htmlspecialchars($footer_info['phone2']); ?>"><?= htmlspecialchars($footer_info['phone2']); ?></a>
         </div>
         <?php
      } else {
         echo '<p>No footer information available.</p>';
      }
      ?>
   </section>

   <div class="credit">Nhom11</div>
</footer>