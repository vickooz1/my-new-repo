<?php
$logoPath = 'WhatsApp_Image_2026-06-11_at_10.19.34_AM-removebg-preview.png';
if (file_exists($logoPath)) {
    echo '<img src="' . $logoPath . '" alt="My Generation Loves God Logo" style="width: 120px; height: auto; margin-bottom: 16px; border-radius: 16px;" />';
}
