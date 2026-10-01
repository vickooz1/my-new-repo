<?php $activeNav = $activeNav ?? ''; ?>
<nav class="page-nav page-nav--desktop" aria-label="Primary navigation">
  <a class="<?php echo $activeNav === 'home' ? 'is-active' : ''; ?>" href="dashboard.php">Home</a>
  <a class="<?php echo $activeNav === 'about' ? 'is-active' : ''; ?>" href="about.php">About</a>
  <a class="<?php echo $activeNav === 'mission' ? 'is-active' : ''; ?>" href="what-we-do.php">Mission</a>
  <a class="<?php echo $activeNav === 'gallery' ? 'is-active' : ''; ?>" href="gallery.php">Gallery</a>
  <a class="<?php echo $activeNav === 'contact' ? 'is-active' : ''; ?>" href="contact.php">Contact</a>
  <a class="<?php echo $activeNav === 'donations' ? 'is-active' : ''; ?>" href="donate.php">Donations</a>
  <a class="<?php echo $activeNav === 'quiz' ? 'is-active' : ''; ?>" href="bible-quiz.php">Bible quiz</a>
  <a class="<?php echo $activeNav === 'opportunities' ? 'is-active' : ''; ?>" href="opportunities.php">Opportunities</a>
</nav>
<details class="mobile-nav">
  <summary>Menu</summary>
  <nav aria-label="Mobile navigation">
    <a class="<?php echo $activeNav === 'home' ? 'is-active' : ''; ?>" href="dashboard.php">Home</a>
    <a class="<?php echo $activeNav === 'about' ? 'is-active' : ''; ?>" href="about.php">About</a>
    <a class="<?php echo $activeNav === 'mission' ? 'is-active' : ''; ?>" href="what-we-do.php">Mission</a>
    <a class="<?php echo $activeNav === 'gallery' ? 'is-active' : ''; ?>" href="gallery.php">Gallery</a>
    <a class="<?php echo $activeNav === 'contact' ? 'is-active' : ''; ?>" href="contact.php">Contact</a>
    <a class="<?php echo $activeNav === 'donations' ? 'is-active' : ''; ?>" href="donate.php">Donations</a>
    <a class="<?php echo $activeNav === 'quiz' ? 'is-active' : ''; ?>" href="bible-quiz.php">Bible quiz</a>
    <a class="<?php echo $activeNav === 'opportunities' ? 'is-active' : ''; ?>" href="opportunities.php">Opportunities</a>
  </nav>
</details>
