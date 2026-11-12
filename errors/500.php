<?php
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$home = $base === '' ? '/' : $base . '/';
$css = ($base === '' ? '' : $base) . '/assets/css/style.css';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Something went wrong · japjapTV</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            background: radial-gradient(circle at 20% 0%, #271014, #08090b 45%);
            color: #f4f5f7; font-family: Inter, Segoe UI, Arial, sans-serif; padding: 2rem;
        }
        .box { max-width: 560px; text-align: center; }
        .brand { font-size: 1.6rem; font-weight: 800; letter-spacing: -.04em; margin-bottom: 1.5rem; }
        .brand span { color: #ff3b47; }
        .code { font-size: clamp(4rem, 15vw, 7rem); font-weight: 900; line-height: 1; color: #ff3b47; letter-spacing: -.08em; }
        h1 { margin: 1rem 0 .75rem; font-size: 1.6rem; }
        p { color: #a6acb8; line-height: 1.7; margin: 0 auto 1.75rem; max-width: 44ch; }
        .btn {
            display: inline-flex; align-items: center; gap: .5rem; padding: .85rem 1.3rem;
            border-radius: 999px; background: #ff3b47; color: white; text-decoration: none;
            font-weight: 700; font-size: .92rem;
        }
        .btn:hover { background: #ff5964; }
    </style>
</head>
<body>
    <main class="box">
        <div class="brand">japjap<span>TV</span></div>
        <div class="code">500</div>
        <h1>The stream hit a snag.</h1>
        <p>Something went wrong while loading this page. The error has been logged. Please try again in a moment or return to the channel hub.</p>
        <a class="btn" href="<?= htmlspecialchars($home, ENT_QUOTES, 'UTF-8') ?>">Back to japjapTV</a>
    </main>
</body>
</html>
