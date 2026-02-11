<?php
/**
 * Admin Dashboard - View Coaching Applications
 */
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Auto logout after 2 hours
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Database connection
define('HARUNFIT_SECURE', true);
require_once __DIR__ . '/../private/config/db_config.php';

try {
    $pdo = getSecureDBConnection();
    
    // Get all applications, newest first
    $stmt = $pdo->query("
        SELECT id, name, gender, age, contact_info, goals, submission_date, ip_address, contacted 
        FROM coaching_applications 
        ORDER BY submission_date DESC
    ");
    
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HarunFit</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        .welcome {
            color: #787878;
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #f28c38;
        }
        
        .stat-label {
            color: #787878;
            margin-top: 5px;
        }
        
        .applications-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .applications-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f5f5f5;
        }
        
        th {
            text-align: left;
            padding: 15px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-male {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-female {
            background: #fce4ec;
            color: #c2185b;
        }
        
        .no-applications {
            padding: 40px;
            text-align: center;
            color: #787878;
        }
        
        .date {
            color: #787878;
            font-size: 0.9rem;
        }
        
        .goals {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-text {
            font-size: 0.9rem;
            font-weight: 600;
        }

        input[type="checkbox"]:checked + .checkmark + .status-text {
            color: #43a047;
        }

        input[type="checkbox"]:not(:checked) + .checkmark + .status-text {
            color: #f28c38;
        }

        .contacted-checkbox {
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 10px;
            }
            
            .goals {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Admin Dashboard</h1>
            <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($applications); ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $today = date('Y-m-d');
                $todayCount = array_filter($applications, function($app) use ($today) {
                    return strpos($app['submission_date'], $today) === 0;
                });
                echo count($todayCount);
                ?>
            </div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $thisWeek = date('Y-m-d', strtotime('-7 days'));
                $weekCount = array_filter($applications, function($app) use ($thisWeek) {
                    return $app['submission_date'] >= $thisWeek;
                });
                echo count($weekCount);
                ?>
            </div>
            <div class="stat-label">This Week</div>
        </div>
    </div>
    
    <div class="applications-container">
        <div class="applications-header">
            Coaching Applications
        </div>
        
        <?php if (count($applications) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Contact</th>
                        <th>Goals</th>
                        <th>Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>#<?php echo $app['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($app['name']); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($app['gender']); ?>">
                                    <?php echo htmlspecialchars($app['gender']); ?>
                                </span>
                            </td>
                            <td><?php echo $app['age']; ?></td>
                            <td><?php echo htmlspecialchars($app['contact_info']); ?></td>
                            <td class="goals" title="<?php echo htmlspecialchars($app['goals']); ?>">
                                <?php echo $app['goals'] ? htmlspecialchars($app['goals']) : '-'; ?>
                            </td>
                            <td class="date">
                                <?php echo date('M j, Y g:i A', strtotime($app['submission_date'])); ?>
                            </td>
                            <td>
                                <label class="checkbox-container">
                                    <input type="checkbox" 
                                           class="contacted-checkbox" 
                                           data-id="<?php echo $app['id']; ?>"
                                           <?php echo $app['contacted'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="status-text"><?php echo $app['contacted'] ? 'Contacted' : 'Pending'; ?></span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-applications">
                No applications yet. Check back later!
            </div>
        <?php endif; ?>
    </div>

<script>
document.querySelectorAll('.contacted-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const appId = this.getAttribute('data-id');
        const isChecked = this.checked;
        const contacted = isChecked ? 1 : 0;
        
        console.log('Updating app ID:', appId, 'to contacted:', contacted);
        
        fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: parseInt(appId),
                contacted: contacted
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                const statusText = this.parentElement.querySelector('.status-text');
                if (isChecked) {
                    statusText.textContent = 'Contacted';
                    statusText.style.color = '#43a047';
                } else {
                    statusText.textContent = 'Pending';
                    statusText.style.color = '#f28c38';
                }
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
                this.checked = !isChecked;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error updating status');
            this.checked = !isChecked;
        });
    });
});
</script>

</body>
</html>