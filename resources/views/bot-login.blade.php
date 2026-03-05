<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход в админку</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
            text-align: center;
        }
        .loader {
            border: 4px solid var(--tg-theme-secondary-bg-color, #f3f3f3);
            border-top: 4px solid var(--tg-theme-button-color, #3498db);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="loadingUI">
        <div class="loader"></div>
        <p style="font-family: sans-serif; font-size: 14px;">Подключение...</p>
    </div>

    <form id="authForm" method="POST" action="/admin-auth" style="display: none;">
        @csrf
        <input type="hidden" name="initData" id="initDataInput">
    </form>

    <script>
        // Немедленно сообщаем Telegram, что мы начали загрузку, чтобы убрать системный индикатор
        if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
        }

        function doAuth() {
            try {
                const tg = window.Telegram ? window.Telegram.WebApp : null;
                if (tg && tg.initData) {
                    document.getElementById('initDataInput').value = tg.initData;
                    document.getElementById('authForm').submit();
                    return true;
                }
                return false;
            } catch (err) {
                console.error(err);
                return false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Если данные уже есть, отправляем сразу
            if (!doAuth()) {
                // Если нет, пробуем еще пару секунд (иногда initData подгружается с задержкой)
                let attempts = 0;
                const iv = setInterval(() => {
                    attempts++;
                    if (doAuth() || attempts > 10) {
                        clearInterval(iv);
                        if (attempts > 10) {
                            document.getElementById('loadingUI').innerHTML = 
                                '<div style="color:#ef4444; padding: 20px;">' +
                                '<b>Ошибка:</b> Данные авторизации не получены.<br>' +
                                '<small>Пожалуйста, перезайдите в меню /adminmenu</small>' +
                                '</div>';
                        }
                    }
                }, 200);
            }
        });
    </script>
</body>
</html>
