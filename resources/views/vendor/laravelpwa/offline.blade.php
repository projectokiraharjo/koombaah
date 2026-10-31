<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline · Koombaah</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body {
            min-height: 100vh; display: grid; place-items: center; padding: 16px;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #fafafa; color: #0a0a0a;
        }
        .card {
            width: 100%; max-width: 380px; padding: 24px; text-align: center;
            background: #fff; border: 1px solid #e5e5e5; border-radius: 14px;
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
        }
        h1 { font-size: 18px; font-weight: 600; margin: 12px 0 6px; }
        p  { font-size: 14px; color: #737373; line-height: 1.5; }
        button {
            margin-top: 20px; height: 36px; padding: 0 16px; cursor: pointer;
            border: 0; border-radius: 8px; background: #171717; color: #fafafa;
            font-size: 14px; font-weight: 500;
        }
        button:hover { background: #262626; }
    </style>
</head>
<body>
    <div class="card">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h.01"/><path d="M8.5 16.43a5 5 0 0 1 7 0"/><path d="M5 12.86a10 10 0 0 1 5.17-2.69"/><path d="M19 12.86a10 10 0 0 0-2-1.43"/><path d="M2 8.82a15 15 0 0 1 4.18-2.65"/><path d="M22 8.82a15 15 0 0 0-11.29-3.76"/><path d="m2 2 20 20"/></svg>
        <h1>Tidak ada koneksi internet</h1>
        <p>Koombaah membutuhkan koneksi untuk memuat data terbaru. Periksa jaringanmu, lalu coba lagi.</p>
        <button onclick="location.reload()">Coba lagi</button>
    </div>
</body>
</html>