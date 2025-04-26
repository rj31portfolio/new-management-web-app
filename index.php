<?php
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10 text-center">
            <h1 class="display-3 fw-bold mb-4 text-primary animate__animated animate__fadeInDown">Digi Digital Solution</h1>
            <p class="lead mb-5 fs-4 text-muted animate__animated animate__fadeInUp">Streamline your projects, connect with clients, and boost productivity with our all-in-one management system</p>
            
            <div class="row g-4">
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-lg rounded-4 animate__animated animate__zoomIn" style="transition: transform 0.3s; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                        <div class="card-body p-4">
                            <i class="bi bi-gear-fill display-4 text-primary mb-3"></i>
                            <h5 class="card-title fw-bold fs-5">Admin Dashboard</h5>
                            <p class="card-text text-muted small">Oversee projects and manage clients</p>
                            <a href="admin/login.php" class="btn btn-primary btn-sm rounded-pill px-3 mt-2 shadow-sm">Admin Portal</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-lg rounded-4 animate__animated animate__zoomIn" style="transition: transform 0.3s; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                        <div class="card-body p-4">
                            <i class="bi bi-person-circle display-4 text-primary mb-3"></i>
                            <h5 class="card-title fw-bold fs-5">Client Hub</h5>
                            <p class="card-text text-muted small">Track projects and notifications</p>
                            <a href="client/login.php" class="btn btn-primary btn-sm rounded-pill px-3 mt-2 shadow-sm">Client Portal</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-lg rounded-4 animate__animated animate__zoomIn" style="transition: transform 0.3s; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                        <div class="card-body p-4">
                            <i class="bi bi-people-fill display-4 text-primary mb-3"></i>
                            <h5 class="card-title fw-bold fs-5">Employee Portal</h5>
                            <p class="card-text text-muted small">Submit reports and tasks</p>
                            <a href="employee/login.php" class="btn btn-primary btn-sm rounded-pill px-3 mt-2 shadow-sm">Employee Login</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-lg rounded-4 animate__animated animate__zoomIn" style="transition: transform 0.3s; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                        <div class="card-body p-4">
                            <i class="bi bi-person-badge-fill display-4 text-primary mb-3"></i>
                            <h5 class="card-title fw-bold fs-5">HR Management</h5>
                            <p class="card-text text-muted small">Manage employees and payroll</p>
                            <a href="hr/login.php" class="btn btn-primary btn-sm rounded-pill px-3 mt-2 shadow-sm">HR Portal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons and Animate.css for enhanced visuals -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<!-- Custom CSS for additional styling -->
<style>
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
}
.btn-primary {
    background: linear-gradient(45deg, #007bff, #00d4ff);
    border: none;
    transition: all 0.3s ease;
}
.btn-primary:hover {
    background: linear-gradient(45deg, #0056b3, #0096cc);
    transform: scale(1.05);
}
.text-primary {
    color: #007bff !important;
}
.small {
    font-size: 0.85rem;
}
</style>

<?php include 'includes/footer.php'; ?>