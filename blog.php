<?php
require_once 'includes/db.php'; // Your database connection
$pdo = getDatabase(); // Assuming this function returns your PDO connection

// Fetch all blogs
$stmt = $pdo->prepare("SELECT id, title, slug, main_image, kit_note, created_at FROM blogs ORDER BY created_at DESC");
$stmt->execute();
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .blog-card {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .blog-card:hover {
            transform: translateY(-5px);
        }
        .blog-img {
            max-height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h2 class="mb-4 text-center">Latest Blog Posts</h2>
        <div class="row">
            <?php foreach ($blogs as $blog): ?>
                <div class="col-md-4 mb-4">
                    <div class="card blog-card">
                        <?php if ($blog['main_image']): ?>
                            <img src="<?php echo htmlspecialchars($blog['main_image']); ?>" class="card-img-top blog-img" alt="Blog Image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($blog['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($blog['kit_note'], 0, 100)); ?>...</p>
                            <a href="blog-detail.php?slug=<?php echo urlencode($blog['slug']); ?>" class="btn btn-primary btn-sm">Read More</a>
                        </div>
                        <div class="card-footer text-muted text-end">
                            <?php echo date('M d, Y', strtotime($blog['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
