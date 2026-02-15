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
    <title>Patient Dashboard - DORA Hospital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header .logo { font-size: 1.5rem; font-weight: bold; }
        .header .user-info { display: flex; align-items: center; gap: 1rem; }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover { background: rgba(255,255,255,0.3); }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .welcome-banner {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .welcome-banner h1 { color: #333; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .welcome-banner p { color: #666; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .action-card .icon { font-size: 3rem; margin-bottom: 1rem; }
        .action-card h3 { color: #333; margin-bottom: 0.5rem; }
        .action-card p { color: #666; font-size: 0.9rem; }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 1rem; text-align: center; }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">🏥 DORA Hospital</div>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($patient_name); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($patient_name); ?>! 👋</h1>
            <p>Manage your appointments and view medical records.</p>
        </div>

        <div class="quick-actions">
            <a href="index.html" class="action-card">
                <div class="icon">🎫</div>
                <h3>Generate Token</h3>
                <p>Get OPD token for walk-in or prebooked visit</p>
            </a>
            <a href="token_status.html" class="action-card">
                <div class="icon">📊</div>
                <h3>Token Status</h3>
                <p>Check your current queue position</p>
            </a>
            <a href="lab_queue.html" class="action-card">
                <div class="icon">🧪</div>
                <h3>Booking Services</h3>
                <p>Book your doctors and lab</p>
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
