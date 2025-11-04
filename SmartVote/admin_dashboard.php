<?php
require_once 'config/database.php';
require_once 'classes/UserAuth.php';
require_once 'classes/ElectionManager.php';

// Start session before creating UserAuth instance
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$auth = new UserAuth();
$electionManager = new ElectionManager();

// Get statistics
$all_users = $auth->getAllUsers();
$all_elections = $electionManager->getAllElections();
$recent_activity = $electionManager->getRecentVotingActivity(10);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Vote</title>
    <link rel="icon" type="image/png" href="logo/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #1c2143;
            min-height: 100vh;
        }

        .navbar {
            background: #43c3dd;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
        }

        .nav-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .nav-btn {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .logout-btn {
            background: #1c2143;
            color: white;
        }

        .logout-btn:hover {
            background: white;
            color: #1c2143;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #43c3dd, #004a94);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #82C2E3;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-family: 'Montserrat', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            color: #244357;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: black;
            font-size: 1.1rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .content-card {
            background: #244357;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .content-card h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            color: #F2FAFF;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 12px 24px;
            background: #82C2E3;
            color: #004A94;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
        }

        .btn:hover {
            background: #F2FAFF;
        }

        .btn-secondary {
            background: #43c3dd;
        }

        .btn-secondary:hover {
            background: #3ab0d1;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info {
            flex: 1;
        }

        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }

        .election-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .election-item:last-child {
            border-bottom: none;
        }

        .election-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-upcoming {
            background: #fff3cd;
            color: #856404;
        }

        .status-ended {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Smart Vote - Admin</div>
            <div class="nav-buttons">
                <a href="?logout=1" class="nav-btn logout-btn" onclick="return confirmLogout()">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($all_users); ?></div>
                <div class="stat-label">Total Voters</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($all_elections); ?></div>
                <div class="stat-label">Total Elections</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    $current_time = time();
                    $active_count = 0;
                    foreach ($all_elections as $election) {
                        $start_time = strtotime($election['start_date']);
                        $end_time = strtotime($election['end_date']);
                        if ($current_time >= $start_time && $current_time <= $end_time) {
                            $active_count++;
                        }
                    }
                    echo $active_count;
                ?></div>
                <div class="stat-label">Active Elections</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h3>Quick Actions</h3>
                <a href="admin_candidates.php" class="btn">Manage Candidates</a>
                <a href="admin_voters.php" class="btn">Manage Voters</a>
                <a href="admin_elections.php" class="btn">Manage Elections</a>
                <a href="election_report.php" class="btn">Statistical Report</a>
				<a href="generate_results.php" class="btn">View Winners</a>
                
            </div>

            <div class="content-card">
                <h3>Elections Overview</h3>
                <?php if (empty($all_elections)): ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">No elections found</p>
                <?php else: ?>
                    <?php foreach ($all_elections as $election): ?>
                        <?php
                        // Determine actual status based on current date/time
                        $current_time = time();
                        $start_time = strtotime($election['start_date']);
                        $end_time = strtotime($election['end_date']);
                        
                        if ($current_time < $start_time) {
                            $actual_status = 'upcoming';
                            $status_text = '⏰ Upcoming';
                        } elseif ($current_time >= $start_time && $current_time <= $end_time) {
                            $actual_status = 'active';
                            $status_text = '🟢 Active';
                        } else {
                            $actual_status = 'ended';
                            $status_text = '🔴 Ended';
                        }
                        ?>
                        <div class="election-item">
                            <div>
                                <strong style="color: #F2FAFF;"><?php echo htmlspecialchars($election['title']); ?></strong>
                                <br>
                                <small style="color: #F2FAFF;">
                                    <?php echo date('M j, Y', strtotime($election['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                                </small>
                            </div>
                            <span class="election-status status-<?php echo $actual_status; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>
