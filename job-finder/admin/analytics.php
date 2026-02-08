<?php
$page_title = 'Analytics Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Require admin authentication
require_role(ROLE_ADMIN);

// Get overall statistics
$stats = [
    'total_users' => db_fetch("SELECT COUNT(*) as count FROM users", [])['count'],
    'active_users' => db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'", [])['count'],
    'total_jobs' => db_fetch("SELECT COUNT(*) as count FROM jobs", [])['count'],
    'active_jobs' => db_fetch("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'", [])['count'],
    'total_applications' => db_fetch("SELECT COUNT(*) as count FROM applications", [])['count'],
    'pending_applications' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'", [])['count'],
    'accepted_applications' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'", [])['count'],
    'total_agents' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'agent'", [])['count'],
];

// Get recent activity
$recent_activity = db_fetch_all(
    "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10",
    []
);

// Get top categories
$top_categories = db_fetch_all(
    "SELECT category, COUNT(*) as job_count FROM jobs GROUP BY category ORDER BY job_count DESC LIMIT 5",
    []
);

// Get recent users
$recent_users = db_fetch_all(
    "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5",
    []
);

// Get application stats by status
$application_stats = db_fetch_all(
    "SELECT status, COUNT(*) as count FROM applications GROUP BY status",
    []
);

// Get monthly growth data (last 6 months)
$monthly_data = db_fetch_all(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        'users' as type
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6",
    []
);
?>

<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</h2>
            <p class="text-muted">Platform statistics and insights</p>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Users</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                            <small><?php echo $stats['active_users']; ?> active</small>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Jobs</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_jobs']); ?></h2>
                            <small><?php echo $stats['active_jobs']; ?> active</small>
                        </div>
                        <i class="fas fa-briefcase fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Applications</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_applications']); ?></h2>
                            <small><?php echo $stats['pending_applications']; ?> pending</small>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Agents</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_agents']); ?></h2>
                            <small>Employers</small>
                        </div>
                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Application Status Chart -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Application Status Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($application_stats)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($application_stats as $stat): ?>
                                        <?php
                                        $percentage = ($stat['count'] / $stats['total_applications']) * 100;
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'verified' => 'info',
                                            'accepted' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $status_colors[$stat['status']] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($stat['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($stat['count']); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-<?php echo $color; ?>" 
                                                         style="width: <?php echo $percentage; ?>%">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No application data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Categories -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Top Job Categories</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_categories)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Jobs</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_categories as $cat): ?>
                                        <?php $percentage = ($cat['job_count'] / $stats['total_jobs']) * 100; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cat['category']); ?></td>
                                            <td><?php echo number_format($cat['job_count']); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: <?php echo $percentage; ?>%">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No category data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Users</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_users)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <?php
                                            $role_colors = ['user' => 'info', 'agent' => 'success', 'admin' => 'warning'];
                                            $color = $role_colors[$user['role']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($user['role']); ?></span>
                                            <br>
                                            <small class="text-muted"><?php echo time_ago($user['created_at']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No users yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_activity)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                            <?php if ($activity['description']): ?>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo time_ago($activity['created_at']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No activity logged yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Database:</strong> MySQL</p>
                            <p><strong>Application Version:</strong> 1.0.0</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Success Rate:</strong> 
                                <?php 
                                $success_rate = $stats['total_applications'] > 0 
                                    ? ($stats['accepted_applications'] / $stats['total_applications']) * 100 
                                    : 0;
                                echo number_format($success_rate, 1); 
                                ?>%
                            </p>
                            <p><strong>Avg. Applications per Job:</strong> 
                                <?php 
                                $avg = $stats['total_jobs'] > 0 
                                    ? $stats['total_applications'] / $stats['total_jobs'] 
                                    : 0;
                                echo number_format($avg, 1); 
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
