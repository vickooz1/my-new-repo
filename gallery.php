<?php
require_once 'db.php';

function galleryCategory(string $caption, ?string $storedCategory = null): string
{
    $allowedCategories = ['worship', 'fellowship', 'outreach', 'events', 'meetings', 'other'];
    if (in_array($storedCategory, $allowedCategories, true)) return $storedCategory;
    $caption = strtolower($caption);
    if (preg_match('/worship|praise|prayer|kesh(a|e)/', $caption)) return 'worship';
    if (preg_match('/outreach|evangel|mission|serve|community/', $caption)) return 'outreach';
    if (preg_match('/event|conference|camp|crusade|summit/', $caption)) return 'events';
    if (preg_match('/fellowship|meeting|hangout|bible study|gather/', $caption)) return 'fellowship';
    return 'other';
}

  function galleryImageUrl(string $imagePath): string
  {
    return ltrim(str_replace('\\', '/', $imagePath), '/');
  }

  function localGalleryFiles(): array
  {
    $directory = __DIR__ . '/gallery_uploads';
    if (!is_dir($directory)) return [];

    $files = glob($directory . '/*.{jpg,jpeg,png,webp,gif,mp4,webm,ogv}', GLOB_BRACE) ?: [];
    usort($files, static fn(string $left, string $right): int => filemtime($right) <=> filemtime($left));
    return array_map(static function (string $file): array {
      return [
        'image_path' => 'gallery_uploads/' . basename($file),
        'caption' => '',
        'category' => 'other',
        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
      ];
    }, $files);
  }

  function galleryIsVideo(string $path): bool
  {
    return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogv', 'ogg'], true);
  }

$gallery = [];
try {
    $pdo = getDbConnection();
    $gallery = $pdo->query('SELECT image_path, caption, category, created_at FROM gallery ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $exception) {
  $gallery = localGalleryFiles();
  if (!$gallery) {
    $galleryError = 'The gallery is temporarily unavailable.';
  }
}

  if (!$gallery && empty($galleryError)) {
    $gallery = localGalleryFiles();
  }
?>
<!doctype html>
<html lang="en">
<head><link rel="icon" type="image/png" href="assets/mglg-favicon.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gallery | MGLG</title>
  <link rel="stylesheet" href="theme.css">
  <link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav">
  <style>
    .gallery-intro { margin-bottom:42px; }
    .gallery-filters { display:flex; flex-wrap:wrap; gap:8px; margin:-18px 0 28px; }
    .gallery-filter { border:1px solid var(--line); border-radius:999px; background:transparent; color:var(--green); padding:9px 13px; font:800 .72rem Inter,ui-sans-serif,sans-serif; cursor:pointer; }
    .gallery-filter:hover, .gallery-filter.is-active { background:var(--green); border-color:var(--green); color:#fff; }
    .full-gallery { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; }
    .full-gallery figure { margin:0; overflow:hidden; border:1px solid var(--line); border-radius:16px; background:var(--card); box-shadow:0 8px 24px rgba(22,49,38,.06); }
    .full-gallery figure[hidden] { display:none; }
    .gallery-open { display:block; aspect-ratio:1 / .82; overflow:hidden; background:var(--mint); }
    .gallery-open img, .gallery-open video { width:100%; height:100%; object-fit:cover; transition:transform .35s ease; }
    .gallery-open:hover img, .gallery-open:focus-visible img { transform:scale(1.06); }
    .full-gallery figcaption { padding:14px 16px 4px; color:var(--ink); font-weight:650; }
    .gallery-actions { display:flex; flex-wrap:wrap; gap:10px; padding:8px 16px 16px; }
    .gallery-download, .gallery-share { color:var(--green); font-size:.78rem; font-weight:700; text-decoration:none; }
    .gallery-share { border:0; padding:0; background:transparent; font:700 .78rem Inter,ui-sans-serif,sans-serif; cursor:pointer; }
    .gallery-download:hover, .gallery-share:hover { color:var(--brown); text-decoration:underline; }
    .gallery-empty { grid-column:1 / -1; padding:30px; border:1px dashed var(--line); color:var(--muted); text-align:center; }
    .lightbox { position:fixed; inset:0; z-index:1000; display:grid; place-items:center; padding:24px; background:rgba(12,27,21,.88); opacity:0; pointer-events:none; transition:opacity .2s ease; }
    .lightbox.is-open { opacity:1; pointer-events:auto; }
    .lightbox img { max-width:min(100%, 1100px); max-height:78vh; border-radius:8px; box-shadow:0 24px 80px rgba(0,0,0,.45); }
    .lightbox-close { position:absolute; top:18px; right:22px; border:0; border-radius:50%; width:42px; height:42px; background:#fff; color:var(--ink); font-size:1.7rem; cursor:pointer; }
    .lightbox-caption { position:absolute; bottom:22px; max-width:80vw; color:#fff; text-align:center; }
    @media (max-width:850px) { .full-gallery { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width:520px) { .full-gallery { grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="page-shell">
    <header class="page-header"><a class="brand" href="dashboard.php">My Generation Loves God</a><?php $activeNav = 'gallery'; include 'site-nav.php'; ?></header>
    <main class="page-main">
      <div class="eyebrow eyebrow--xs">Life at MGLG</div>
      <h1 class="page-title--xs">Moments that move us.</h1>
      <p class="lead lead--xs gallery-intro">A glimpse of worship, fellowship, outreach, and life together in the MGLG community.</p>
      <div class="gallery-filters" aria-label="Filter gallery photos">
        <button class="gallery-filter is-active" type="button" data-filter="all">All photos</button>
        <button class="gallery-filter" type="button" data-filter="worship">Worship</button>
        <button class="gallery-filter" type="button" data-filter="fellowship">Fellowship</button>
        <button class="gallery-filter" type="button" data-filter="outreach">Outreach</button>
        <button class="gallery-filter" type="button" data-filter="events">Events</button>
        <button class="gallery-filter" type="button" data-filter="meetings">Meetings</button>
        <button class="gallery-filter" type="button" data-filter="other">Other</button>
      </div>
      <section class="full-gallery" aria-label="MGLG photo gallery">
        <?php if (!empty($galleryError)) : ?><p class="gallery-empty"><?php echo htmlspecialchars($galleryError); ?></p><?php endif; ?>
        <?php if (!$gallery && empty($galleryError)) : ?><p class="gallery-empty">Photos from our community will appear here soon.</p><?php endif; ?>
        <?php foreach ($gallery as $index => $photo) : ?>
          <figure data-category="<?php echo galleryCategory((string) ($photo['caption'] ?? ''), $photo['category'] ?? null); ?>">
            <?php $imageUrl = galleryImageUrl((string) $photo['image_path']); ?>
            <?php if (galleryIsVideo($imageUrl)) : ?>
              <div class="gallery-open"><video controls preload="metadata"><source src="<?php echo htmlspecialchars($imageUrl); ?>"><span>Your browser does not support video playback.</span></video></div>
            <?php else : ?>
              <a class="gallery-open" href="<?php echo htmlspecialchars($imageUrl); ?>" data-caption="<?php echo htmlspecialchars($photo['caption'] ?: 'MGLG community moment'); ?>">
                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($photo['caption'] ?: 'MGLG community moment'); ?>">
              </a>
            <?php endif; ?>
            <?php if ($photo['caption']) : ?><figcaption><?php echo htmlspecialchars($photo['caption']); ?></figcaption><?php endif; ?>
            <div class="gallery-actions">
              <a class="gallery-download" href="<?php echo htmlspecialchars($imageUrl); ?>" download>Download <?php echo galleryIsVideo($imageUrl) ? 'video' : 'photo'; ?></a>
              <button class="gallery-share" type="button" data-share-url="<?php echo htmlspecialchars($imageUrl); ?>" data-share-title="<?php echo htmlspecialchars($photo['caption'] ?: 'MGLG community moment'); ?>">Share link</button>
            </div>
          </figure>
        <?php endforeach; ?>
      </section>
    </main>
    <?php include 'site-footer.php'; ?>
  </div>
  <div class="lightbox" id="lightbox" aria-hidden="true" role="dialog" aria-label="Photo preview"><button class="lightbox-close" type="button" aria-label="Close photo preview">×</button><img src="" alt=""><p class="lightbox-caption"></p></div>
  <script>
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = lightbox.querySelector('img');
    const lightboxCaption = lightbox.querySelector('.lightbox-caption');
    const closeLightbox = () => { lightbox.classList.remove('is-open'); lightbox.setAttribute('aria-hidden', 'true'); };
    document.querySelectorAll('.gallery-open').forEach((link) => link.addEventListener('click', (event) => {
      event.preventDefault(); lightboxImage.src = link.href; lightboxImage.alt = link.dataset.caption; lightboxCaption.textContent = link.dataset.caption; lightbox.classList.add('is-open'); lightbox.setAttribute('aria-hidden', 'false');
    }));
    lightbox.addEventListener('click', (event) => { if (event.target === lightbox || event.target.closest('.lightbox-close')) closeLightbox(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeLightbox(); });
    document.querySelectorAll('.gallery-filter').forEach((button) => button.addEventListener('click', () => {
      const filter = button.dataset.filter;
      document.querySelectorAll('.gallery-filter').forEach((item) => item.classList.toggle('is-active', item === button));
      document.querySelectorAll('.full-gallery figure[data-category]').forEach((photo) => { photo.hidden = filter !== 'all' && photo.dataset.category !== filter; });
    }));
    document.querySelectorAll('.gallery-share').forEach((button) => button.addEventListener('click', async () => {
      const url = new URL(button.dataset.shareUrl, window.location.href).href;
      const title = button.dataset.shareTitle || 'MGLG community moment';
      try {
        if (navigator.share) {
          await navigator.share({ title, url });
          return;
        }
        await navigator.clipboard.writeText(url);
        button.textContent = 'Link copied';
        window.setTimeout(() => { button.textContent = 'Share link'; }, 1800);
      } catch (error) {
        if (error.name !== 'AbortError') window.prompt('Copy this photo link:', url);
      }
    }));
  </script>
  <?php include 'site-cross-background.php'; ?>
  <script src="theme.js?v=20260929-light-only"></script>
</body>
</html>
