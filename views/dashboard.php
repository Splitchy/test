<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="?action=dashboard">
                <i class="fas fa-truck"></i> <?= SITE_NAME ?>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?= htmlspecialchars($current_user['first_name']) ?>!
                </span>
                <a href="?action=logout" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="list-group">
                    <a href="?action=dashboard" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    
                    <?php if ($current_user['role'] === ROLE_VENDOR): ?>
                        <a href="?action=pickup-requests" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle"></i> Create Pickup
                        </a>
                        <a href="?action=package-tracking" class="list-group-item list-group-item-action">
                            <i class="fas fa-search"></i> Track Packages
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($current_user['role'] === ROLE_DELIVERY_AGENT): ?>
                        <a href="?action=delivery-slips" class="list-group-item list-group-item-action">
                            <i class="fas fa-clipboard-list"></i> Delivery Slips
                        </a>
                        <a href="?action=scan-qr" class="list-group-item list-group-item-action">
                            <i class="fas fa-qrcode"></i> Scan QR
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($current_user['role'] === ROLE_ADMIN): ?>
                        <a href="?action=users" class="list-group-item list-group-item-action">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                        <a href="?action=cities" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt"></i> Manage Cities
                        </a>
                        <a href="?action=reports" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    <?php endif; ?>
                    
                    <a href="?action=profile" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-cog"></i> Profile
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <h2>Dashboard - <?= ucfirst(str_replace('_', ' ', $current_user['role'])) ?></h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <?php if ($current_user['role'] === ROLE_ADMIN): ?>
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Pickups</h5>
                                    <h3><?= $dashboardData['stats']['today_pickups'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Pickups</h5>
                                    <h3><?= $dashboardData['stats']['pending_pickups'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">In Transit</h5>
                                    <h3><?= $dashboardData['stats']['in_transit'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Today Delivered</h5>
                                    <h3><?= $dashboardData['stats']['today_delivered'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($current_user['role'] === ROLE_VENDOR): ?>
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Requests</h5>
                                    <h3><?= $dashboardData['stats']['today_requests'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Pending</h5>
                                    <h3><?= $dashboardData['stats']['pending_requests'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">In Transit</h5>
                                    <h3><?= $dashboardData['stats']['in_transit'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Delivered</h5>
                                    <h3><?= $dashboardData['stats']['total_delivered'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php else: // DELIVERY_AGENT ?>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Slips</h5>
                                    <h3><?= $dashboardData['stats']['pending_slips'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Active Slips</h5>
                                    <h3><?= $dashboardData['stats']['active_slips'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Today Delivered</h5>
                                    <h3><?= $dashboardData['stats']['today_delivered'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Completed</h5>
                                    <h3><?= $dashboardData['stats']['total_completed'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboardData['recent_requests'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Recipient</th>
                                                    <th>From</th>
                                                    <th>To</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dashboardData['recent_requests'] as $request): ?>
                                                    <tr>
                                                        <td>#<?= $request['id'] ?></td>
                                                        <td><?= htmlspecialchars($request['recipient_name']) ?></td>
                                                        <td><?= htmlspecialchars($request['pickup_city']) ?></td>
                                                        <td><?= htmlspecialchars($request['delivery_city']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= getStatusColor($request['status']) ?>">
                                                                <?= getStatusDisplayName($request['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= formatDate($request['created_at']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent activity.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>