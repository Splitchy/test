<?php
$pageTitle = 'Admin Panel';
$pageDescription = 'Administrative dashboard for managing the website and users.';

// Get some basic stats
$stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users");
$stmt->execute();
$userCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products");
$stmt->execute();
$productCount = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders");
$stmt->execute();
$orderCount = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as setting_count FROM settings");
$stmt->execute();
$settingCount = $stmt->fetchColumn();
?>

<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-tools me-2"></i>Admin Panel
                        </h1>
                        <p class="text-muted">Manage your website settings, users, and content.</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-danger">Administrator Access</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Users</h4>
                                <h2 class="mb-0"><?php echo $userCount; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Products</h4>
                                <h2 class="mb-0"><?php echo $productCount; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Orders</h4>
                                <h2 class="mb-0"><?php echo $orderCount; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Settings</h4>
                                <h2 class="mb-0"><?php echo $settingCount; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-cog fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Management Tools -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tools me-2"></i>Management Tools
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                        <h5>User Management</h5>
                                        <p class="text-muted">Manage user accounts, roles, and permissions.</p>
                                        <a href="/admin/users" class="btn btn-primary">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Users
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-box fa-3x text-success mb-3"></i>
                                        <h5>Product Management</h5>
                                        <p class="text-muted">Add, edit, and manage products and inventory.</p>
                                        <a href="/admin/products" class="btn btn-success">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Products
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-shopping-cart fa-3x text-warning mb-3"></i>
                                        <h5>Order Management</h5>
                                        <p class="text-muted">View and process customer orders.</p>
                                        <a href="/admin/orders" class="btn btn-warning">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Orders
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-cog fa-3x text-info mb-3"></i>
                                        <h5>Site Settings</h5>
                                        <p class="text-muted">Configure website settings and preferences.</p>
                                        <a href="/admin/settings" class="btn btn-info">
                                            <i class="fas fa-arrow-right me-2"></i>Site Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/admin/users/add" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Add New User
                            </a>
                            <a href="/admin/products/add" class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Add New Product
                            </a>
                            <a href="/admin/backup" class="btn btn-outline-warning">
                                <i class="fas fa-download me-2"></i>Backup Database
                            </a>
                            <a href="/admin/logs" class="btn btn-outline-info">
                                <i class="fas fa-file-alt me-2"></i>View Logs
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-server me-2"></i>System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>PHP Version:</strong><br><?php echo PHP_VERSION; ?></p>
                        <p><strong>Server Software:</strong><br><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        <p><strong>Installation Date:</strong><br>
                        <?php 
                        if (file_exists('.installed')) {
                            echo date('F j, Y g:i A', filemtime('.installed'));
                        } else {
                            echo 'Unknown';
                        }
                        ?>
                        </p>
                        <p><strong>Site URL:</strong><br><?php echo SITE_URL; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Recent Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute();
                        $recentUsers = $stmt->fetchAll();
                        ?>
                        
                        <?php if ($recentUsers): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $recentUser): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($recentUser['name']); ?></td>
                                                <td><?php echo htmlspecialchars($recentUser['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo ucfirst($recentUser['role']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $recentUser['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($recentUser['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($recentUser['created_at'])); ?></td>
                                                <td>
                                                    <a href="/admin/users/edit/<?php echo $recentUser['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>No users found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>