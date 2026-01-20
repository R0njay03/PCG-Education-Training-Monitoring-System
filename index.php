<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Dashboard';

$db = new Database();
$conn = $db->getConnection();

// Check if database connection is successful
if (!$conn) {
    include 'includes/header.php';
    echo '<div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Database Connection Error</h4>
        <p>The system cannot connect to the database. Please ensure:</p>
        <ul>
            <li>MySQL is running in XAMPP</li>
            <li>The database has been created</li>
            <li>Database credentials are correct in <code>config/database.php</code></li>
        </ul>
        <hr>
        <p class="mb-0">
            <a href="setup.php" class="btn btn-primary">Run Database Setup</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </p>
    </div>';
    include 'includes/footer.php';
    exit();
}

// Get statistics
$stats = [];

// Total students
$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$stats['total_students'] = $stmt->fetch()['total'] ?? 0;

// Total training programs
$stmt = $conn->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'active'");
$stats['total_programs'] = $stmt->fetch()['total'] ?? 0;

// Ongoing trainings
$stmt = $conn->query("SELECT COUNT(*) as total FROM student_training WHERE status = 'ongoing'");
$stats['ongoing_trainings'] = $stmt->fetch()['total'] ?? 0;

// Completed trainings (this month)
$stmt = $conn->query("SELECT COUNT(*) as total FROM student_training WHERE status = 'completed' AND MONTH(completion_date) = MONTH(CURRENT_DATE()) AND YEAR(completion_date) = YEAR(CURRENT_DATE())");
$stats['completed_this_month'] = $stmt->fetch()['total'] ?? 0;

// Training by type
$training_by_type = [];
$types = ['local', 'us', 'asian_other', 'unit'];
foreach ($types as $type) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM student_training st
        JOIN training_programs tp ON st.training_program_id = tp.id
        WHERE tp.training_type = ? AND st.status IN ('enrolled', 'ongoing')
    ");
    $stmt->execute([$type]);
    $training_by_type[$type] = $stmt->fetch()['total'] ?? 0;
}

// Recent training activities
$stmt = $conn->query("
    SELECT st.*, s.service_number, s.last_name, s.first_name, s.rank,
           tp.program_name, tp.training_type
    FROM student_training st
    JOIN students s ON st.student_id = s.id
    JOIN training_programs tp ON st.training_program_id = tp.id
    ORDER BY st.created_at DESC
    LIMIT 10
");
$recent_activities = $stmt->fetchAll() ?? [];

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
        <p class="text-muted">Training Monitoring Overview</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-white-50">Active Students</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_students']); ?></h2>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-white-50">Training Programs</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_programs']); ?></h2>
                    </div>
                    <i class="bi bi-book fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-white-50">Ongoing Trainings</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['ongoing_trainings']); ?></h2>
                    </div>
                    <i class="bi bi-arrow-repeat fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-white-50">Completed (This Month)</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['completed_this_month']); ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Training by Type -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Training by Type</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="training/local.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-geo-alt-fill text-primary"></i> Local Training</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $training_by_type['local']; ?></span>
                    </a>
                    <a href="training/us.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-flag-fill text-danger"></i> U.S Training</span>
                        <span class="badge bg-danger rounded-pill"><?php echo $training_by_type['us']; ?></span>
                    </a>
                    <a href="training/asian_other.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-globe text-success"></i> Asian & Other Countries</span>
                        <span class="badge bg-success rounded-pill"><?php echo $training_by_type['asian_other']; ?></span>
                    </a>
                    <a href="training/unit.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-building text-warning"></i> Unit Training</span>
                        <span class="badge bg-warning rounded-pill"><?php echo $training_by_type['unit']; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Training Activities</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Program</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_activities)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No recent activities</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td>
                                    <small><?php echo htmlspecialchars($activity['rank'] . ' ' . $activity['last_name'] . ', ' . $activity['first_name']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($activity['program_name']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = [
                                        'enrolled' => 'bg-info',
                                        'ongoing' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'dropped' => 'bg-danger',
                                        'failed' => 'bg-dark'
                                    ];
                                    $class = $badge_class[$activity['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($activity['status']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

