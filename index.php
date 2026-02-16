<?php require_once 'config/headers.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatReactor PWA</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff0055">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body class="dark-theme">
    <div id="app">
        <!-- Auth Screen -->
        <div id="auth-screen" class="screen">
            <div class="auth-container">
                <h1>ChatReactor</h1>
                <div id="auth-forms">
                    <form id="login-form">
                        <input type="text" name="username" placeholder="Username" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="submit">Login</button>
                        <p>Don't have an account? <a href="#" id="show-register">Register</a></p>
                    </form>
                    <form id="register-form" style="display: none;">
                        <input type="text" name="username" placeholder="Username" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="submit">Register</button>
                        <p>Already have an account? <a href="#" id="show-login">Login</a></p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Chat Screen -->
        <div id="chat-screen" class="screen" style="display: none;">
            <header>
                <div class="logo">ChatReactor</div>
                <div class="user-info">
                    <span id="current-username"></span>
                    <button id="logout-btn">Logout</button>
                </div>
            </header>
            <main>
                <aside id="sidebar">
                    <h3>Online Users</h3>
                    <ul id="user-list"></ul>
                </aside>
                <section id="chat-area">
                    <div id="messages"></div>
                    <form id="message-form">
                        <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
                        <button type="submit">Send</button>
                    </form>
                </section>
            </main>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>
