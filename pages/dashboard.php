<?php
$pageTitle = 'Dashboard';
$pageDescription = 'Your personal dashboard with account overview and quick actions.';
?>

<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-0">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                        <p class="text-muted">Here's what's happening with your account today.</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Last login: <?php echo date('M j, Y g:i A'); ?></small>
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
                                <h4 class="card-title">Account Status</h4>
                                <p class="card-text">
                                    <span class="badge bg-light text-dark"><?php echo ucfirst($user['status']); ?></span>
                                </p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-check fa-2x"></i>
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
                                <h4 class="card-title">Role</h4>
                                <p class="card-text">
                                    <span class="badge bg-light text-dark"><?php echo ucfirst($user['role']); ?></span>
                                </p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-id-badge fa-2x"></i>
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
                                <h4 class="card-title">Member Since</h4>
                                <p class="card-text"><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-alt fa-2x"></i>
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
                                <h4 class="card-title">Activities</h4>
                                <p class="card-text">0 recent</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-bar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Account Information -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Name:</strong>
                                <p><?php echo htmlspecialchars($user['name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Role:</strong>
                                <p>
                                    <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <p>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Member Since:</strong>
                                <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Last Updated:</strong>
                                <p><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="text-end">
                            <a href="/profile" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </a>
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
                            <a href="/profile" class="btn btn-outline-primary">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                            <a href="/settings" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i>Account Settings
                            </a>
                            <?php if ($user['role'] === 'admin'): ?>
                                <a href="/admin" class="btn btn-outline-warning">
                                    <i class="fas fa-tools me-2"></i>Admin Panel
                                </a>
                            <?php endif; ?>
                            <a href="/support" class="btn btn-outline-info">
                                <i class="fas fa-question-circle me-2"></i>Get Help
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Site Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Site Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Site Name:</strong><br><?php echo SITE_NAME; ?></p>
                        <p><strong>Admin Contact:</strong><br><?php echo ADMIN_EMAIL; ?></p>
                        <p><strong>Vendor Contact:</strong><br><?php echo VENDOR_EMAIL; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No recent activity to display.</p>
                            <small>Your activities will appear here once you start using the platform.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>