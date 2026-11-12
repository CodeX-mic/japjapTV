<?php
/**
 * Generate original, non-official placeholder artwork for japjapTV.
 *
 * These are generic text marks — never reproductions of broadcaster logos.
 * Usage: php tools/generate_logos.php
 */

declare(strict_types=1);

$seed = require __DIR__ . '/seed-data.php';
$root = dirname(__DIR__);
$logoDir = $root . '/assets/images/logos';
$imageDir = $root . '/assets/images';

foreach ([$logoDir, $imageDir] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "Could not create {$dir}\n");
        exit(1);
    }
}

function svg_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function accent_is_dark(string $hex): bool
{
    $hex = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return false;
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return (($r * 0.299) + ($g * 0.587) + ($b * 0.114)) < 48;
}

function channel_logo_svg(string $mark, string $accent, string $name): string
{
    $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#ff3b47';
    $safeMark = svg_escape($mark);
    $safeName = svg_escape($name);
    $length = mb_strlen($mark);
    $size = match (true) {
        $length <= 2 => 154,
        $length === 3 => 126,
        $length === 4 => 102,
        default => 82,
    };
    $letterSpacing = match (true) {
        $length <= 2 => '-4',
        $length === 3 => '-3',
        default => '-1.5',
    };
    $lightAccent = accent_is_dark($accent) ? '#ff5b66' : $accent;

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" role="img" aria-label="{$safeName} placeholder logo">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0b0f17"/>
      <stop offset="0.58" stop-color="#111722"/>
      <stop offset="1" stop-color="#090c12"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.22" cy="0.12" r="0.86">
      <stop offset="0" stop-color="{$lightAccent}" stop-opacity="0.5"/>
      <stop offset="0.55" stop-color="{$accent}" stop-opacity="0.09"/>
      <stop offset="1" stop-color="{$accent}" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="mark" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#ffffff"/>
      <stop offset="1" stop-color="#e7ebf3"/>
    </linearGradient>
    <clipPath id="round"><rect width="512" height="512" rx="104"/></clipPath>
  </defs>
  <g clip-path="url(#round)">
    <rect width="512" height="512" fill="url(#bg)"/>
    <rect width="512" height="512" fill="url(#glow)"/>
    <circle cx="430" cy="90" r="152" fill="none" stroke="{$lightAccent}" stroke-opacity="0.18" stroke-width="2"/>
    <circle cx="430" cy="90" r="106" fill="none" stroke="{$lightAccent}" stroke-opacity="0.12" stroke-width="1.5"/>
    <path d="M-30 454C104 390 178 468 300 414c74-33 144-24 244 12v120H-30Z" fill="{$accent}" fill-opacity="0.12"/>
    <path d="M-30 496C120 438 200 520 338 456c70-33 130-22 206 8v82H-30Z" fill="{$lightAccent}" fill-opacity="0.08"/>
    <rect x="38" y="36" width="64" height="5" rx="2.5" fill="{$lightAccent}" fill-opacity="0.9"/>
    <circle cx="54" cy="72" r="9" fill="{$accent}"/>
    <circle cx="54" cy="72" r="18" fill="none" stroke="{$accent}" stroke-opacity="0.32"/>
    <text x="256" y="292" text-anchor="middle" font-family="Segoe UI, Inter, Arial, sans-serif" font-size="{$size}" font-weight="800" letter-spacing="{$letterSpacing}" fill="url(#mark)">{$safeMark}</text>
    <text x="256" y="451" text-anchor="middle" font-family="Segoe UI, Inter, Arial, sans-serif" font-size="23" font-weight="600" letter-spacing="5" fill="#ffffff" fill-opacity="0.34">JAPJAPTV</text>
    <rect x="0.75" y="0.75" width="510.5" height="510.5" rx="103.25" fill="none" stroke="#ffffff" stroke-opacity="0.08" stroke-width="1.5"/>
  </g>
</svg>
SVG;
}

$generated = 0;
foreach ($seed['channels'] as $channel) {
    $mark = (string) ($channel['mark'] ?? '');
    if ($mark === '') {
        $words = preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', $channel['name']) ?? '') ?: [];
        $mark = '';
        foreach ($words as $word) {
            if ($word !== '') {
                $mark .= strtoupper($word[0]);
            }
            if (mb_strlen($mark) >= 3) {
                break;
            }
        }
        $mark = $mark !== '' ? $mark : 'TV';
    }

    $svg = channel_logo_svg($mark, (string) ($channel['accent'] ?? '#ff3b47'), (string) $channel['name']);
    $path = $logoDir . '/' . $channel['slug'] . '.svg';
    if (file_put_contents($path, $svg . "\n", LOCK_EX) === false) {
        fwrite(STDERR, "Could not write {$path}\n");
        exit(1);
    }
    $generated++;
}

$placeholder = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" role="img" aria-label="Channel logo placeholder">
  <defs>
    <linearGradient id="pbg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#141a25"/>
      <stop offset="1" stop-color="#090c12"/>
    </linearGradient>
    <linearGradient id="pmark" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#ff5b66"/>
      <stop offset="1" stop-color="#c40712"/>
    </linearGradient>
  </defs>
  <rect width="512" height="512" rx="104" fill="url(#pbg)"/>
  <circle cx="256" cy="256" r="132" fill="none" stroke="#ffffff" stroke-opacity="0.08" stroke-width="2"/>
  <path d="M216 182c0-13 14-21 25-15l121 69c12 7 12 24 0 31l-121 69c-11 7-25-1-25-15V182Z" fill="url(#pmark)"/>
  <text x="256" y="452" text-anchor="middle" font-family="Segoe UI, Inter, Arial, sans-serif" font-size="22" font-weight="600" letter-spacing="5" fill="#ffffff" fill-opacity="0.3">JAPJAPTV</text>
</svg>
SVG;
file_put_contents($imageDir . '/placeholder.svg', $placeholder . "\n", LOCK_EX);

$markSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128" role="img" aria-label="japjapTV logo">
  <defs>
    <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#ff5964"/>
      <stop offset="1" stop-color="#d40712"/>
    </linearGradient>
  </defs>
  <rect width="128" height="128" rx="32" fill="#0b0f17"/>
  <rect x="10" y="10" width="108" height="108" rx="26" fill="url(#lg)"/>
  <path d="M51 43c0-5.2 5.6-8.4 10.1-5.8l30.5 18c4.4 2.6 4.4 9 0 11.6l-30.5 18c-4.5 2.6-10.1-.6-10.1-5.8V43Z" fill="#ffffff"/>
</svg>
SVG;
file_put_contents($imageDir . '/logo-mark.svg', $markSvg . "\n", LOCK_EX);

$favicon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
  <defs>
    <linearGradient id="f" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#ff5964"/>
      <stop offset="1" stop-color="#d40712"/>
    </linearGradient>
  </defs>
  <rect width="64" height="64" rx="16" fill="#0b0f17"/>
  <rect x="4" y="4" width="56" height="56" rx="14" fill="url(#f)"/>
  <path d="M26 22c0-2.6 2.8-4.2 5-2.9l15.3 9c2.2 1.3 2.2 4.5 0 5.8l-15.3 9c-2.2 1.3-5-.3-5-2.9V22Z" fill="#fff"/>
</svg>
SVG;
file_put_contents($imageDir . '/favicon.svg', $favicon . "\n", LOCK_EX);

// PNG favicons and social cover (GD + FreeType if available).
$gdReady = extension_loaded('gd') && function_exists('imagecreatetruecolor');
if ($gdReady) {
    $icon = imagecreatetruecolor(180, 180);
    imageantialias($icon, true);
    $bg = imagecolorallocate($icon, 11, 15, 23);
    imagefilledrectangle($icon, 0, 0, 180, 180, $bg);
    for ($diameter = 168; $diameter > 0; $diameter -= 2) {
        $t = 1 - ($diameter / 168);
        $r = (int) (255 - (255 - 212) * $t);
        $g = (int) (89 - (89 - 7) * $t);
        $b = (int) (100 - (100 - 18) * $t);
        $color = imagecolorallocate($icon, $r, $g, $b);
        imagefilledellipse($icon, 90, 90, $diameter, $diameter, $color);
    }
    $white = imagecolorallocate($icon, 255, 255, 255);
    imagefilledpolygon($icon, [72, 56, 72, 124, 132, 90], 3, $white);
    imagepng($icon, $imageDir . '/apple-touch-icon.png');
    $small = imagescale($icon, 32, 32, IMG_BILINEAR_FIXED);
    if ($small !== false) {
        imagepng($small, $imageDir . '/favicon-32.png');
        imagedestroy($small);
    }
    imagedestroy($icon);

    $fontBold = 'C:/Windows/Fonts/segoeuib.ttf';
    $fontRegular = 'C:/Windows/Fonts/segoeui.ttf';
    if (function_exists('imagettftext') && is_file($fontBold)) {
        $w = 1200;
        $h = 630;
        $cover = imagecreatetruecolor($w, $h);
        imageantialias($cover, true);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $r = (int) (8 + 8 * $t);
            $g = (int) (10 + 6 * $t);
            $b = (int) (15 + 12 * $t);
            $line = imagecolorallocate($cover, $r, $g, $b);
            imageline($cover, 0, $y, $w, $y, $line);
        }

        // Soft red glow, built from concentric transparent-ish ellipses.
        for ($i = 32; $i >= 1; $i--) {
            $radiusX = 180 + $i * 11;
            $radiusY = 180 + $i * 11;
            $alpha = 118 - $i * 3;
            if ($alpha < 0) {
                continue;
            }
            $glow = imagecolorallocatealpha($cover, 180, 18, 30, max(0, min(127, $alpha)));
            imagefilledellipse($cover, 1040, 60, $radiusX * 2, $radiusY * 2, $glow);
        }

        $grid = imagecolorallocatealpha($cover, 255, 255, 255, 120);
        for ($x = 0; $x <= $w; $x += 60) {
            imageline($cover, $x, 0, $x, $h, $grid);
        }
        for ($y = 0; $y <= $h; $y += 60) {
            imageline($cover, 0, $y, $w, $y, $grid);
        }

        $white = imagecolorallocate($cover, 245, 247, 250);
        $muted = imagecolorallocate($cover, 166, 173, 184);
        $red = imagecolorallocate($cover, 255, 59, 71);
        $redDark = imagecolorallocate($cover, 170, 12, 22);

        // Logo tile: dark square, red offset backdrop, white play mark.
        $tile = imagecolorallocate($cover, 20, 25, 35);
        imagefilledrectangle($cover, 96, 60, 204, 168, $redDark);
        imagefilledrectangle($cover, 80, 76, 188, 184, $tile);
        imagefilledpolygon($cover, [118, 104, 118, 156, 164, 130], 3, $white);

        imagettftext($cover, 78, 0, 224, 172, $white, $fontBold, 'japjap');
        imagettftext($cover, 78, 0, 490, 172, $red, $fontBold, 'TV');
        imagettftext($cover, 34, 0, 82, 282, $white, $fontBold, 'Your channels. One streaming hub.');
        imagettftext($cover, 22, 0, 82, 338, $muted, $fontRegular, 'Philippine and international livestreams, curated from official sources.');
        imagettftext($cover, 21, 0, 82, 382, $muted, $fontRegular, 'No restreams. No unauthorized IPTV.');
        imagettftext($cover, 21, 0, 82, 416, $muted, $fontRegular, 'Official destinations whenever embedding is not permitted.');

        imagefilledellipse($cover, 92, 476, 18, 18, $red);
        imagettftext($cover, 24, 0, 112, 486, $white, $fontBold, '27 verified livestreams at launch');
        imagettftext($cover, 20, 0, 82, 556, $muted, $fontRegular, 'japjapTV  ·  Official sources only  ·  Availability can change');

        imagepng($cover, $imageDir . '/og-cover.png');
        imagedestroy($cover);
    }
}

printf("Generated %d channel SVGs, placeholder, site marks, favicons%s.\n", $generated, is_file($imageDir . '/og-cover.png') ? ', and og-cover.png' : '');
