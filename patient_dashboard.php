<?php
session_start();

if(!isset($_SESSION['patient_id'])){
    header("Location: patient_login.html");
    exit();
}

$patient_name = $_SESSION['patient_name'] ?? 'Patient';
$patient_id = $_SESSION['patient_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MediNova Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #0a4d68 0%, #088395 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-symbol {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #fff 0%, #e0f7fa 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .logo-text .logo-title {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .logo-text .logo-subtitle {
            font-size: 0.7rem;
            opacity: 0.9;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .welcome-banner {
            background: white;
            padding: 2rem 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border-left: 5px solid #088395;
        }

        .welcome-banner h1 {
            color: #0a4d68;
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-banner p {
            color: #666;
            font-size: 0.95rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .action-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(8,131,149,0.15);
            border-color: #088395;
        }

        .action-card .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 2rem;
        }

        .action-card h3 {
            color: #0a4d68;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .action-card p {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.2rem;
            }
            .user-info { justify-content: center; }
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .welcome-banner { padding: 1.5rem; }
            .welcome-banner h1 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <div class="logo-symbol">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 3H15V9H21V15H15V21H9V15H3V9H9V3Z" fill="#0a4d68" stroke="#088395" stroke-width="1.5"/>
                </svg>
            </div>
            <div class="logo-text">
                <div class="logo-title">MediNova Hospital</div>
                <div class="logo-subtitle">Smart Healthcare</div>
            </div>
        </div>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($patient_name); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($patient_name); ?>! 👋</h1>
            <p>Manage your appointments and access hospital services</p>
        </div>

        <div class="quick-actions">
            <a href="index.html" class="action-card">
                <div class="icon">🎫</div>
                <h3>Generate Token</h3>
                <p>Get token for walk-in or prebooked visit</p>
            </a>
            <a href="token_status.html" class="action-card">
                <div class="icon">📊</div>
                <h3>Token Status</h3>
                <p>Check your current queue position</p>
            </a>
            <a href="home.html" class="action-card">
                <div class="icon">🏠</div>
                <h3>Home</h3>
                <p>Back to hospital homepage</p>
            </a>
        </div>
    </div>
</body>
</html>
