<footer class="footer">
  <?php include __DIR__ . '/site-links.php'; ?>
  <div>
    <strong>My Generation Loves God</strong>
    <span>Igniting a generation for Christ.</span>
    <span>P.O. Box 20100-200, Nairobi, Kenya</span>
  </div>
  <div class="footer-links">
    <a href="about.php">About</a>
    <a href="gallery.php">Gallery</a>
    <a href="contact.php">Contact</a>
    <a href="donate.php">Give</a>
  </div>
  <div class="footer-links" aria-label="Our links">
    <?php foreach ($siteLinks as $siteLink) : ?>
      <a href="<?php echo htmlspecialchars($siteLink['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($siteLink['label']); ?></a>
    <?php endforeach; ?>
  </div>
</footer>
