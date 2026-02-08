<?php
$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';

// Require admin authentication
require_role(ROLE_ADMIN);

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $user_id = (int)$_POST['user_id'];
        $new_status = sanitize_input($_POST['status']);
        
        // Prevent admin from blocking themselves
        if ($user_id !== $_SESSION['user_id']) {
            $update_sql = "UPDATE users SET status = ? WHERE id = ?";
            db_query($update_sql, [$new_status, $user_id]);
            $_SESSION['success'] = 'User status updated successfully';
            log_activity($_SESSION['user_id'], 'user_status_update', "Updated user #$user_id status to $new_status");
        } else {
            $_SESSION['error'] = 'You cannot change your own status';
        }
    }
    redirect('/admin/manage_users.php');
}

// Handle user deletion
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (verify_csrf_token($_GET['token'])) {
        $user_id = (int)$_GET['delete'];
        
        // Prevent admin from deleting themselves
        if ($user_id !== $_SESSION['user_id']) {
            $delete_sql = "DELETE FROM users WHERE id = ?";
            db_query($delete_sql, [$user_id]);
            $_SESSION['success'] = 'User deleted successfully';
            log_activity($_SESSION['user_id'], 'user_delete', "Deleted user #$user_id");
        } else {
            $_SESSION['error'] = 'You cannot delete your own account';
        }
    }
    redirect('/admin/manage_users.php');
}

// Get filters
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : 'all';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];

if ($role_filter !== 'all') {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as count FROM users $where_clause";
$total_items = db_fetch($count_sql, $params)['count'];
$pagination = get_pagination($total_items, $per_page, $page);

// Get users
$sql = "SELECT id, name, email, role, phone, status, created_at,
               (SELECT COUNT(*) FROM applications WHERE user_id = users.id) as application_count,
               (SELECT COUNT(*) FROM jobs WHERE agent_id = users.id) as job_count
        FROM users
        $where_clause
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$users = db_fetch_all($sql, $params);

// Get role counts
$role_counts = [
    'all' => db_fetch("SELECT COUNT(*) as count FROM users", [])['count'],
    'user' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'user'", [])['count'],
    'agent' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'agent'", [])['count'],
    'admin' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'", [])['count'],
];
?>

<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-users me-2"></i>Manage Users</h2>
            <p class="text-muted">View and manage all platform users</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Name or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Users</option>
                        <option value="agent" <?php echo $role_filter === 'agent' ? 'selected' : ''; ?>>Agents</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Role Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3><?php echo $role_counts['all']; ?></h3>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3><?php echo $role_counts['user']; ?></h3>
                    <p class="mb-0">Job Seekers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3><?php echo $role_counts['agent']; ?></h3>
                    <p class="mb-0">Agents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3><?php echo $role_counts['admin']; ?></h3>
                    <p class="mb-0">Administrators</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No users found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php
                                        $role_colors = ['user' => 'info', 'agent' => 'success', 'admin' => 'warning'];
                                        $color = $role_colors[$user['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($user['role']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'user'): ?>
                                            <?php echo $user['application_count']; ?> applications
                                        <?php elseif ($user['role'] === 'agent'): ?>
                                            <?php echo $user['job_count']; ?> jobs
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_date($user['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="status" value="blocked">
                                                        <button type="submit" name="update_status" class="btn btn-warning" 
                                                                title="Block User">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="status" value="active">
                                                        <button type="submit" name="update_status" class="btn btn-success" 
                                                                title="Unblock User">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <a href="?delete=<?php echo $user['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                                                   title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
