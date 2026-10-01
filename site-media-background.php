<?php
$siteMediaPhotos = glob(__DIR__ . '/assets/backgrounds/mglg-bg-*.jpeg') ?: [];
sort($siteMediaPhotos, SORT_NATURAL);
$siteMediaPhotoUrls = array_map(
    static fn (string $photo): string => 'assets/backgrounds/' . basename($photo),
    $siteMediaPhotos
);
$siteMediaPhotoUrls = $siteMediaPhotoUrls ?: ['assets/backgrounds/mglg-bg-01.jpeg'];
$siteMediaUseVideo = !isset($siteMediaUseVideo) || $siteMediaUseVideo;
?>
<div class="site-media-background" aria-hidden="true">
  <img class="site-media-background__photo" src="<?php echo htmlspecialchars($siteMediaPhotoUrls[0], ENT_QUOTES, 'UTF-8'); ?>" alt="">
  <?php if ($siteMediaUseVideo) : ?>
    <video class="site-media-background__video" autoplay muted loop playsinline preload="metadata" poster="<?php echo htmlspecialchars($siteMediaPhotoUrls[0], ENT_QUOTES, 'UTF-8'); ?>">
      <source src="assets/backgrounds/mglg-video.mp4" type="video/mp4">
    </video>
  <?php endif; ?>
</div>
<script>
  (() => {
    const photo = document.querySelector('.site-media-background__photo');
    const photos = <?php echo json_encode($siteMediaPhotoUrls, JSON_UNESCAPED_SLASHES); ?>;
    if (!photo || photos.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    let current = 0;
    window.setInterval(() => {
      current = (current + 1) % photos.length;
      photo.classList.add('is-changing');
      window.setTimeout(() => {
        photo.src = photos[current];
        photo.classList.remove('is-changing');
      }, 450);
    }, 9000);
  })();
</script>
