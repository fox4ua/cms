<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сервис временно недоступен</title>
    <style>
        body{margin:0;font-family:Arial,sans-serif;background:#f5f7fb;color:#172033;display:flex;min-height:100vh;align-items:center;justify-content:center}.box{max-width:620px;background:#fff;border:1px solid #e5e9f2;border-radius:14px;padding:32px;box-shadow:0 12px 40px rgba(20,35,60,.08)}h1{margin:0 0 12px;font-size:28px}.code{display:inline-block;margin-top:18px;padding:6px 10px;border-radius:8px;background:#eef2f7;color:#4b5b73;font-size:13px}
    </style>
</head>
<body>
    <main class="box">
        <h1>Сервис временно недоступен</h1>
        <p><?= esc($reason) ?></p>
        <p>Попробуйте обновить страницу позже или обратитесь к администратору.</p>
        <span class="code">Код: <?= esc($code) ?></span>
    </main>
</body>
</html>
