<?php
// Pure sacred ambient vector cross background - no background photos or videos
$bgTheme = $activeNav ?? 'default';

$motifs = [
    'mission' => [
        'watermark' => '1 TIMOTHY 4:12 • EVANGELISM & OUTREACH',
        'class' => 'sacred-motif--mission',
    ],
    'gallery' => [
        'watermark' => 'FELLOWSHIP & PRAISE • LIFE AT MGLG',
        'class' => 'sacred-motif--gallery',
    ],
    'contact' => [
        'watermark' => 'ONE BODY IN CHRIST • CONNECT & GROW',
        'class' => 'sacred-motif--contact',
    ],
    'donations' => [
        'watermark' => 'GIVE WITH PURPOSE • 2 CORINTHIANS 9:7',
        'class' => 'sacred-motif--donations',
    ],
    'opportunities' => [
        'watermark' => 'HELP ONE ANOTHER GROW • GALATIANS 6:2',
        'class' => 'sacred-motif--opportunities',
    ],
    'quiz' => [
        'watermark' => 'GROW IN THE WORD • PSALM 119:105',
        'class' => 'sacred-motif--quiz',
    ],
    'about' => [
        'watermark' => '1 TIMOTHY 4:12 • SET THE EXAMPLE',
        'class' => 'sacred-motif--mission',
    ],
];

$activeMotif = $motifs[$bgTheme] ?? null;
?>
<div class="sacred-cross-bg sacred-cross-bg--<?php echo htmlspecialchars($bgTheme); ?>" aria-hidden="true" style="position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden;">
  <div class="sacred-cross-bg__glow" style="position:absolute; top:8%; right:5%; width:min(680px,80vw); height:min(680px,80vw); border-radius:50%; background:radial-gradient(circle, rgba(199,138,45,.18) 0%, rgba(31,97,74,.12) 45%, transparent 72%); filter:blur(40px);"></div>
  <div class="sacred-cross-bg__rays"></div>

  <!-- Centerpiece Radiant Christian Cross -->
  <div class="sacred-cross-bg__main-cross" style="position:absolute; top:12%; right:4%; width:min(400px,50vw); height:min(540px,70vw); opacity:.30;">
    <svg viewBox="0 0 240 320" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%; display:block;">
      <defs>
        <radialGradient id="crossAuraGrad" cx="50%" cy="32%" r="52%">
          <stop offset="0%" stop-color="#fff0d0" stop-opacity="0.9" />
          <stop offset="35%" stop-color="#c78a2d" stop-opacity="0.55" />
          <stop offset="70%" stop-color="#1f614a" stop-opacity="0.25" />
          <stop offset="100%" stop-color="#18241f" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="crossGoldGrad" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#fff8e7" />
          <stop offset="25%" stop-color="#e2af53" />
          <stop offset="65%" stop-color="#c78a2d" />
          <stop offset="100%" stop-color="#6e4210" />
        </linearGradient>
        <linearGradient id="crossEmeraldGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#7fc49f" />
          <stop offset="50%" stop-color="#1f614a" />
          <stop offset="100%" stop-color="#0f3b2c" />
        </linearGradient>
        <filter id="crossSoftGlow" x="-20%" y="-20%" width="140%" height="140%">
          <feGaussianBlur stdDeviation="8" result="blur" />
          <feComposite in="SourceGraphic" in2="blur" operator="over" />
        </filter>
      </defs>

      <!-- Sacred Aura & Halo Rings -->
      <circle cx="120" cy="104" r="82" stroke="url(#crossGoldGrad)" stroke-width="1.2" stroke-dasharray="8 6" opacity="0.4" />
      <circle cx="120" cy="104" r="62" stroke="url(#crossEmeraldGrad)" stroke-width="1.8" stroke-dasharray="3 3" opacity="0.6" />
      <circle cx="120" cy="104" r="44" fill="url(#crossAuraGrad)" />

      <!-- Radiating Divine Ray Lines -->
      <line x1="120" y1="10" x2="120" y2="310" stroke="url(#crossGoldGrad)" stroke-width="1" stroke-dasharray="4 8" opacity="0.3" />
      <line x1="20" y1="104" x2="220" y2="104" stroke="url(#crossGoldGrad)" stroke-width="1" stroke-dasharray="4 8" opacity="0.3" />
      <line x1="48" y1="32" x2="192" y2="176" stroke="url(#crossGoldGrad)" stroke-width="1" stroke-dasharray="3 7" opacity="0.22" />
      <line x1="192" y1="32" x2="48" y2="176" stroke="url(#crossGoldGrad)" stroke-width="1" stroke-dasharray="3 7" opacity="0.22" />

      <!-- Shadow & Glow Silhouette -->
      <path d="M 104 26 C 104 20 108 16 114 16 L 126 16 C 132 16 136 20 136 26 L 136 88 L 198 88 C 204 88 208 92 208 98 L 208 110 C 208 116 204 120 198 120 L 136 120 L 136 296 C 136 302 132 306 126 306 L 114 306 C 108 306 104 302 104 296 L 104 120 L 42 120 C 36 120 32 116 32 110 L 32 98 C 32 92 36 88 42 88 L 104 88 Z" fill="rgba(31,97,74,0.18)" filter="url(#crossSoftGlow)" />

      <!-- Primary Latin Cross Body -->
      <path d="M 107 28 C 107 24 110 21 114 21 L 126 21 C 130 21 133 24 133 28 L 133 91 L 195 91 C 199 91 202 94 202 98 L 202 110 C 202 114 199 117 195 117 L 133 117 L 133 294 C 133 298 130 301 126 301 L 114 301 C 110 301 107 298 107 294 L 107 117 L 45 117 C 41 117 38 114 38 110 L 38 98 C 38 94 41 91 45 91 L 107 91 Z" fill="url(#crossGoldGrad)" stroke="#ffffff" stroke-opacity="0.35" stroke-width="1.2" />

      <!-- Inner Emerald Filigree Inlay -->
      <path d="M 116 38 L 124 38 L 124 97 L 188 97 L 188 107 L 124 107 L 124 286 L 116 286 L 116 107 L 52 107 L 52 97 L 116 97 Z" fill="url(#crossEmeraldGrad)" />

      <!-- Center Star of Light -->
      <polygon points="120,95 128,104 120,113 112,104" fill="#ffffff" opacity="0.95" />
      <circle cx="120" cy="104" r="3" fill="#fdf4db" />
    </svg>
  </div>

  <!-- Secondary Floating Crosses -->
  <div class="sacred-cross-bg__secondary-cross-1" style="position:absolute; bottom:14%; left:3%; width:120px; height:160px; opacity:.16;">
    <svg viewBox="0 0 100 140" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%;">
      <circle cx="50" cy="46" r="30" stroke="#c78a2d" stroke-width="1.2" stroke-dasharray="4 4" opacity="0.45" />
      <path d="M 45 12 C 45 10 47 8 49 8 L 51 8 C 53 8 55 10 55 12 L 55 40 L 83 40 C 85 40 87 42 87 44 L 87 48 C 87 50 85 52 83 52 L 55 52 L 55 128 C 55 130 53 132 51 132 L 49 132 C 47 132 45 130 45 128 L 45 52 L 17 52 C 15 52 13 50 13 48 L 13 44 C 13 42 15 40 17 40 L 45 40 Z" fill="#c78a2d" opacity="0.85" />
    </svg>
  </div>

  <div class="sacred-cross-bg__secondary-cross-2" style="position:absolute; top:48%; left:42%; width:80px; height:110px; opacity:.09;">
    <svg viewBox="0 0 80 110" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%;">
      <circle cx="40" cy="38" r="24" stroke="#1f614a" stroke-width="1" opacity="0.35" />
      <path d="M 36 10 C 36 8 38 7 40 7 C 42 7 44 8 44 10 L 44 32 L 66 32 C 68 32 69 33 69 35 L 69 39 C 69 41 68 42 66 42 L 44 42 L 44 100 C 44 102 42 103 40 103 C 38 103 36 102 36 100 L 36 42 L 14 42 C 12 42 11 41 11 39 L 11 35 C 11 33 12 32 14 32 L 36 32 Z" fill="#1f614a" opacity="0.75" />
    </svg>
  </div>

  <?php if ($activeMotif) : ?>
    <div class="sacred-motif <?php echo htmlspecialchars($activeMotif['class']); ?>">
      <?php echo htmlspecialchars($activeMotif['watermark']); ?>
    </div>
  <?php endif; ?>
</div>
