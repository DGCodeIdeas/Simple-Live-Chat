<?php

/**
 * Aether Single Entry Point
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// Check if it's an API request
if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
    header('Content-Type: application/json');
    $app = new Application();
    $app->run();
    exit;
}

// Otherwise, serve the SPA/PWA main interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aether Social PWA</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind Play CDN (for development as per blueprint strategy) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        .bg-backdrop-blur {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
        .chat-bubble {
            max-width: 75%;
            border-radius: 1.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
        }
        .chat-bubble-sent {
            background: linear-gradient(to right, #6366f1, #818cf8);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 0.25rem;
        }
        .chat-bubble-received {
            background: #f3f4f6;
            color: #1f2937;
            align-self: flex-start;
            border-bottom-left-radius: 0.25rem;
        }
        #chat-feed {
            height: calc(100vh - 160px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Auth Screen -->
        <div id="auth-screen" class="flex-grow flex items-center justify-center p-4">
            <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
                <h1 class="text-3xl font-bold text-center mb-8 text-indigo-600">Aether</h1>
                <div id="auth-forms">
                    <form id="login-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="you@example.com" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white p-3 rounded-lg font-semibold hover:bg-indigo-700 transition">Login</button>
                        <p class="text-center text-sm text-gray-600">Don't have an account? <a href="#" id="show-register" class="text-indigo-600 hover:underline">Register</a></p>
                    </form>

                    <form id="register-form" class="space-y-4 hidden">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="username" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="johndoe" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="you@example.com" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white p-3 rounded-lg font-semibold hover:bg-indigo-700 transition">Register</button>
                        <p class="text-center text-sm text-gray-600">Already have an account? <a href="#" id="show-login" class="text-indigo-600 hover:underline">Login</a></p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Chat Screen -->
        <div id="chat-screen" class="hidden flex-grow flex flex-col md:flex-row h-screen">
            <!-- Sidebar -->
            <aside class="w-full md:w-80 bg-white border-r flex flex-col">
                <div class="p-4 border-b flex justify-between items-center bg-indigo-600 text-white">
                    <span class="font-bold text-xl">Aether</span>
                    <button id="logout-btn" class="text-xs bg-indigo-500 px-2 py-1 rounded hover:bg-indigo-400">Logout</button>
                </div>
                <div class="flex-grow overflow-y-auto">
                    <div id="conversation-list" class="divide-y">
                        <!-- Conversations injected here -->
                    </div>
                </div>
                <div class="p-4 bg-gray-50 border-t flex items-center">
                    <div id="current-user-avatar" class="w-10 h-10 rounded-full bg-indigo-100 mr-3 overflow-hidden">
                        <img src="/assets/img/default.png" alt="Avatar">
                    </div>
                    <div>
                        <p id="current-username" class="font-semibold text-sm">Username</p>
                        <p class="text-xs text-green-500">Online</p>
                    </div>
                </div>
            </aside>

            <!-- Chat Area -->
            <main class="flex-grow flex flex-col bg-white">
                <header id="chat-header" class="p-4 border-b flex items-center justify-between">
                    <div class="flex items-center">
                        <div id="active-chat-avatar" class="w-10 h-10 rounded-full bg-gray-200 mr-3"></div>
                        <div>
                            <h2 id="active-chat-title" class="font-bold text-lg leading-tight">Select a chat</h2>
                            <p id="active-chat-status" class="text-xs text-gray-500"></p>
                        </div>
                    </div>
                </header>

                <div id="chat-feed" class="flex-grow p-4 space-y-4">
                    <!-- Messages injected here -->
                </div>

                <footer class="p-4 border-t">
                    <form id="message-form" class="flex space-x-2">
                        <input type="text" id="message-input" class="flex-grow p-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700 transition">Send</button>
                    </form>
                </footer>
            </main>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
<?php
// End of index.php
