<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: certificate_of_live_birth.php');
    exit;
}

// Handle login submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Simple authentication (replace with your actual authentication logic)
        // For now, using default admin credentials
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['full_name'] = 'Admin User';

            header('Location: certificate_of_live_birth.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Civil Registry Records Management System</title>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* --- 1. Reset & Layout --- */
        :root {
            --bg-color: #e0e5ec;
            --primary: #0d6efd;
            --secondary: #6c757d;
            --white: #ffffff;
            --shadow: rgba(0, 0, 0, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #dfe6e9;
        }

        .login-card {
            display: flex;
            width: 950px;
            max-width: 95%;
            height: 600px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        /* --- 2. Left Panel (The 3D Stage) --- */
        .left-panel {
            flex: 1.2;
            background: linear-gradient(135deg, #051f3a, #0d3a5f);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            perspective: 800px;
        }

        .left-logo {
            width: 80px;
            height: 80px;
            filter: drop-shadow(0 5px 10px rgba(0,0,0,0.3));
        }

        .stage-header {
            position: absolute;
            top: 35px;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            z-index: 10;
        }

        .stage-title {
            color: rgba(255,255,255,0.9);
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .scene-container {
            width: 100%;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* Shared Scene Styles */
        .scene {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .scene.active {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }

        .caption {
            margin-top: 50px;
            color: white;
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 1px;
            text-shadow: 0 5px 10px rgba(0,0,0,0.3);
        }

        /* --- 3. GIF Animation Container --- */
        .gif-container {
            position: relative;
            width: 180px;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gif-image {
            width: 150px;
            height: 150px;
            object-fit: contain;
            filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
            animation: smoothFloat 3s ease-in-out infinite;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth floating animation */
        @keyframes smoothFloat {
            0%, 100% {
                transform: translateY(0px) scale(1);
                filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
            }
            50% {
                transform: translateY(-15px) scale(1.05);
                filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.4));
            }
        }

        /* Glow effect for GIFs */
        .gif-glow {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
            animation: glowPulse 3s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes glowPulse {
            0%, 100% {
                opacity: 0.3;
                transform: scale(0.8);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }

        /* --- Right Panel (Form) --- */
        .right-panel {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .header h1 { font-size: 2.2rem; color: #2d3436; margin-bottom: 10px; }
        .header p { color: #636e72; margin-bottom: 40px; }

        .input-group { position: relative; margin-bottom: 25px; }
        .input-group label {
            position: absolute; left: 15px; top: -10px; background: white; padding: 0 5px;
            font-size: 0.85rem; color: #0d6efd; font-weight: 600; z-index: 3;
        }
        .input-group input {
            width: 100%; padding: 15px; border: 2px solid #e0e5ec; border-radius: 10px; font-size: 1rem; transition: 0.3s;
        }
        .input-group input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            padding-right: 50px !important;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #636e72;
            cursor: pointer;
            padding: 8px 10px;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #0d6efd;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #0d6efd, #0b5ed7);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .alert-info {
            background: #e8f4fd;
            color: #0c5460;
            border: 1px solid #b8daff;
        }

        .default-creds {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #95a5a6;
        }

        .default-creds strong {
            color: #e74c3c;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-card {
                flex-direction: column;
                height: auto;
                width: 90%;
            }

            .left-panel {
                min-height: 300px;
            }

            .right-panel {
                padding: 40px 30px;
            }

            .left-logo {
                width: 50px;
                height: 50px;
            }

            .stage-header {
                flex-direction: column;
                gap: 10px;
                top: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="login-card">

        <!-- LEFT: 3D Animation Carousel -->
        <div class="left-panel">
            <div class="stage-header">
                <img src="../assets/img/LOGO1.png" alt="Baggao Logo" class="left-logo">
                <div class="stage-title">Civil Registry System</div>
            </div>

            <div class="scene-container">

                <!-- 1. Birth Certificate -->
                <div class="scene active">
                    <div class="gif-container">
                        <div class="gif-glow"></div>
                        <img src="../assets/img/mother.gif" alt="Birth Certificate" class="gif-image">
                    </div>
                    <div class="caption">Birth Certificate</div>
                </div>

                <!-- 2. Marriage Certificate -->
                <div class="scene">
                    <div class="gif-container">
                        <div class="gif-glow"></div>
                        <img src="../assets/img/wedding.gif" alt="Marriage Certificate" class="gif-image">
                    </div>
                    <div class="caption">Marriage Certificate</div>
                </div>

                <!-- 3. Record Search -->
                <div class="scene">
                    <div class="gif-container">
                        <div class="gif-glow"></div>
                        <img src="../assets/img/search.gif" alt="Searching Records" class="gif-image">
                    </div>
                    <div class="caption">Searching Records</div>
                </div>

                <!-- 4. Death Certificate -->
                <div class="scene">
                    <div class="gif-container">
                        <div class="gif-glow"></div>
                        <img src="../assets/img/inheritance.gif" alt="Death Certificate" class="gif-image">
                    </div>
                    <div class="caption">Death Certificate</div>
                </div>

                <!-- 5. Certificate Printing -->
                <div class="scene">
                    <div class="gif-container">
                        <div class="gif-glow"></div>
                        <img src="../assets/img/document.gif" alt="Issuing Certificate" class="gif-image">
                    </div>
                    <div class="caption">Issuing Certificate</div>
                </div>

            </div>
        </div>

        <!-- RIGHT: Login Form -->
        <div class="right-panel">
            <div class="header">
                <h1>Welcome Back</h1>
                <p>Please enter your credentials to access the Civil Registry system.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i data-lucide="alert-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i data-lucide="info"></i>
                <div>
                    <strong>Security Notice:</strong> For your protection, you will be automatically logged out after <strong>30 minutes</strong> of inactivity.
                </div>
            </div>

            <form method="POST" action="" id="loginForm">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           placeholder="Enter your username" required autofocus>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password"
                               placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i data-lucide="log-in"></i> Sign In
                </button>
            </form>

            <div class="default-creds">
                Default credentials: <strong>admin / admin123</strong><br>
                <small>Change password after first login!</small>
            </div>
        </div>

    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Cycle Animations
        const scenes = document.querySelectorAll('.scene');
        let index = 0;

        function cycleScenes() {
            scenes[index].classList.remove('active');
            index = (index + 1) % scenes.length;
            scenes[index].classList.add('active');
        }

        setInterval(cycleScenes, 2500);

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                password.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }

            // Re-initialize Lucide icons to update the changed icon
            lucide.createIcons();
        });

        // Disable button on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Signing In...';
            lucide.createIcons();
        });
    </script>
</body>
</html>
