<?php
/**
 * Jobs API Endpoint
 * Handles job-related API requests
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single job
                $job_id = (int)$_GET['id'];
                $sql = "SELECT j.*, u.name as agent_name 
                        FROM jobs j 
                        JOIN users u ON j.agent_id = u.id 
                        WHERE j.id = ?";
                $job = db_fetch($sql, [$job_id]);
                
                if ($job) {
                    json_response(['success' => true, 'job' => $job]);
                } else {
                    json_response(['success' => false, 'error' => 'Job not found'], 404);
                }
            } else {
                // List jobs (with filters)
                $where = ["j.status = 'active'"];
                $params = [];
                
                if (isset($_GET['category'])) {
                    $where[] = "j.category = ?";
                    $params[] = $_GET['category'];
                }
                
                if (isset($_GET['location'])) {
                    $where[] = "j.location LIKE ?";
                    $params[] = '%' . $_GET['location'] . '%';
                }
                
                $where_clause = implode(' AND ', $where);
                $sql = "SELECT j.*, u.name as agent_name 
                        FROM jobs j 
                        JOIN users u ON j.agent_id = u.id 
                        WHERE $where_clause 
                        ORDER BY j.created_at DESC 
                        LIMIT 50";
                
                $jobs = db_fetch_all($sql, $params);
                json_response(['success' => true, 'jobs' => $jobs]);
            }
            break;
            
        case 'POST':
            // Create job (agent only)
            require_auth();
            require_role([ROLE_AGENT, ROLE_ADMIN]);
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "INSERT INTO jobs (agent_id, title, description, category, location, 
                    salary_min, salary_max, job_type, deadline, requirements) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            db_query($sql, [
                $_SESSION['user_id'],
                $data['title'],
                $data['description'],
                $data['category'],
                $data['location'],
                $data['salary_min'],
                $data['salary_max'],
                $data['job_type'],
                $data['deadline'],
                $data['requirements'] ?? ''
            ]);
            
            $job_id = db_last_id();
            log_activity($_SESSION['user_id'], 'create_job', "Created job: {$data['title']}");
            
            json_response(['success' => true, 'job_id' => $job_id]);
            break;
            
        case 'PUT':
            // Update job
            require_auth();
            require_role([ROLE_AGENT, ROLE_ADMIN]);
            
            $job_id = (int)$_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "UPDATE jobs SET title = ?, description = ?, category = ?, 
                    location = ?, salary_min = ?, salary_max = ?, job_type = ?, 
                    deadline = ?, requirements = ?, status = ? 
                    WHERE id = ? AND agent_id = ?";
            
            db_query($sql, [
                $data['title'],
                $data['description'],
                $data['category'],
                $data['location'],
                $data['salary_min'],
                $data['salary_max'],
                $data['job_type'],
                $data['deadline'],
                $data['requirements'] ?? '',
                $data['status'] ?? 'active',
                $job_id,
                $_SESSION['user_id']
            ]);
            
            log_activity($_SESSION['user_id'], 'update_job', "Updated job ID: $job_id");
            json_response(['success' => true]);
            break;
            
        case 'DELETE':
            // Delete job
            require_auth();
            require_role([ROLE_AGENT, ROLE_ADMIN]);
            
            $job_id = (int)$_GET['id'];
            $sql = "DELETE FROM jobs WHERE id = ? AND agent_id = ?";
            db_query($sql, [$job_id, $_SESSION['user_id']]);
            
            log_activity($_SESSION['user_id'], 'delete_job', "Deleted job ID: $job_id");
            json_response(['success' => true]);
            break;
            
        default:
            json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    json_response(['success' => false, 'error' => 'Server error'], 500);
}
