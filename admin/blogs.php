<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Blog Management";

// Handle blog deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('blogs.php', 'Blog deleted successfully', 'success');
    } else {
        redirect('blogs.php', 'Failed to delete blog', 'danger');
    }
}

// Handle blog creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $slug = sanitize($_POST['slug']);
    $content = $_POST['content']; // CKEditor content, no sanitize to preserve HTML
    $kit_note = $_POST['kit_note'];
    $main_points = json_encode($_POST['main_points'] ?? []); // Store as JSON
    $second_description = $_POST['second_description'];
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    $adminId = getUserId();

    // Handle multiple image uploads
    $image_fields = ['main_image', 'second_main_image', 'gallery_image_1', 'gallery_image_2'];
    $image_paths = [];

    foreach ($image_fields as $field) {
        $image_paths[$field] = null;
        if (!empty($_FILES[$field]['name'])) {
            $upload = uploadFile($_FILES[$field], '../uploads/blog_images/');
            if ($upload['success']) {
                $image_paths[$field] = $upload['file_path'];
            } else {
                redirect('blogs.php', $upload['error'], 'danger');
            }
        }
    }

    if (isset($_POST['blog_id']) && $_POST['blog_id'] !== '') {
        // Update existing blog
        $blogId = (int)$_POST['blog_id'];
        
        // Preserve existing images if no new upload
        $stmt = $pdo->prepare("SELECT main_image, second_main_image, gallery_image_1, gallery_image_2 FROM blogs WHERE id = ?");
        $stmt->execute([$blogId]);
        $existingImages = $stmt->fetch(PDO::FETCH_ASSOC);

        foreach ($image_fields as $field) {
            if (empty($image_paths[$field])) {
                $image_paths[$field] = $existingImages[$field] ?? null;
            }
        }

        $sql = "UPDATE blogs SET title = ?, slug = ?, content = ?, main_image = ?, second_main_image = ?, gallery_image_1 = ?, gallery_image_2 = ?, kit_note = ?, main_points = ?, second_description = ?, meta_title = ?, meta_description = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $params = [
            $title, $slug, $content, 
            $image_paths['main_image'], $image_paths['second_main_image'], 
            $image_paths['gallery_image_1'], $image_paths['gallery_image_2'], 
            $kit_note, $main_points, $second_description, 
            $meta_title, $meta_description, $blogId
        ];

        if ($stmt->execute($params)) {
            redirect('blogs.php', 'Blog updated successfully', 'success');
        } else {
            redirect('blogs.php', 'Failed to update blog', 'danger');
        }
    } else {
        // Create new blog
        $sql = "INSERT INTO blogs (title, slug, content, main_image, second_main_image, gallery_image_1, gallery_image_2, kit_note, main_points, second_description, meta_title, meta_description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $params = [
            $title, $slug, $content, 
            $image_paths['main_image'], $image_paths['second_main_image'], 
            $image_paths['gallery_image_1'], $image_paths['gallery_image_2'], 
            $kit_note, $main_points, $second_description, 
            $meta_title, $meta_description, $adminId
        ];

        if ($stmt->execute($params)) {
            redirect('blogs.php', 'Blog created successfully', 'success');
        } else {
            redirect('blogs.php', 'Failed to create blog', 'danger');
        }
    }
}

// Get pagination data
$pagination = getPagination('blogs');
$currentPage = max(1, (int)$pagination['currentPage']);
$perPage = (int)$pagination['perPage'];
$offset = ($currentPage - 1) * $perPage;

// Get blogs for current page
$stmt = $pdo->prepare("SELECT b.*, a.username as author FROM blogs b JOIN admins a ON b.created_by = a.id ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$blogs = $stmt->fetchAll();

// Get blog data for editing
$editBlog = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
    $stmt->execute([$editId]);
    $editBlog = $stmt->fetch();
    if ($editBlog) {
        $editBlog['main_points'] = json_decode($editBlog['main_points'] ?? '[]', true);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Blog Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#blogModal">Add New Blog</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Author</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blogs as $blog): ?>
                        <tr>
                            <td><?php echo $blog['id']; ?></td>
                            <td><?php echo $blog['title']; ?></td>
                            <td><?php echo $blog['slug']; ?></td>
                            <td><?php echo $blog['author']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($blog['created_at'])); ?></td>
                            <td>
                                <a href="?edit=<?php echo $blog['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $blog['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($currentPage < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Blog Modal -->
<div class="modal fade" id="blogModal" tabindex="-1" aria-labelledby="blogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blogModalLabel"><?php echo $editBlog ? 'Edit Blog' : 'Add New Blog'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="blog_id" value="<?php echo $editBlog ? $editBlog['id'] : ''; ?>">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $editBlog ? htmlspecialchars($editBlog['title']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo $editBlog ? htmlspecialchars($editBlog['slug']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo $editBlog ? htmlspecialchars($editBlog['meta_title']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo $editBlog ? htmlspecialchars($editBlog['meta_description']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo $editBlog ? htmlspecialchars($editBlog['content']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="second_description" class="form-label">Second Description</label>
                        <textarea class="form-control" id="second_description" name="second_description" rows="5"><?php echo $editBlog ? htmlspecialchars($editBlog['second_description']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="kit_note" class="form-label">Note- Thought</label>
                        <textarea class="form-control" id="kit_note" name="kit_note" rows="5"><?php echo $editBlog ? htmlspecialchars($editBlog['kit_note']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Main Points (Up to 5)</label>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><?php echo $i; ?></span>
                                <input type="text" class="form-control" name="main_points[]" value="<?php echo $editBlog && isset($editBlog['main_points'][$i-1]) ? htmlspecialchars($editBlog['main_points'][$i-1]) : ''; ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mb-3">
                        <label for="main_image" class="form-label">Main Image</label>
                        <input type="file" class="form-control" id="main_image" name="main_image">
                        <?php if ($editBlog && $editBlog['main_image']): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editBlog['main_image']); ?>" alt="Main image" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="second_main_image" class="form-label">Second Main Image</label>
                        <input type="file" class="form-control" id="second_main_image" name="second_main_image">
                        <?php if ($editBlog && $editBlog['second_main_image']): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editBlog['second_main_image']); ?>" alt="Second main image" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="gallery_image_1" class="form-label">Gallery Image 1</label>
                        <input type="file" class="form-control" id="gallery_image_1" name="gallery_image_1">
                        <?php if ($editBlog && $editBlog['gallery_image_1']): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editBlog['gallery_image_1']); ?>" alt="Gallery image 1" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="gallery_image_2" class="form-label">Gallery Image 2</label>
                        <input type="file" class="form-control" id="gallery_image_2" name="gallery_image_2">
                        <?php if ($editBlog && $editBlog['gallery_image_2']): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editBlog['gallery_image_2']); ?>" alt="Gallery image 2" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                   
                   
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CKEditor Integration -->
<!-- CKEditor 4 -->
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script>
    // Initialize CKEditor for content, kit_note, and second_description
    CKEDITOR.replace('content', {
        height: 300
    });
    CKEDITOR.replace('kit_note', {
        height: 200
    });
    CKEDITOR.replace('second_description', {
        height: 200
    });

    // Auto-generate slug from title input
    document.getElementById('title').addEventListener('input', function () {
        const title = this.value;
        const slug = title.toLowerCase()
                          .replace(/[^a-z0-9]+/g, '-')
                          .replace(/(^-|-$)/g, '');
        document.getElementById('slug').value = slug;
    });

    // Automatically show modal for editing if in edit mode
    <?php if (isset($_GET['edit'])): ?>
        document.addEventListener('DOMContentLoaded', function () {
            var blogModal = new bootstrap.Modal(document.getElementById('blogModal'));
            blogModal.show();
        });
    <?php endif; ?>
</script>


<?php include '../includes/footer.php'; ?>