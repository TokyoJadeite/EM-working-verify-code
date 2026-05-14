<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('applicant');

$applicantId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $docType = $_POST['doc_type'];
    $file = $_FILES['document'];
    
    // Server-side validation
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed with error code: " . $file['error'];
    } elseif (!in_array($file['type'], $allowedTypes)) {
        $error = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
    } elseif ($file['size'] > $maxSize) {
        $error = "File size exceeds 10MB limit.";
    } else {
        // Create directory if not exists
        $uploadDir = __DIR__ . '/../uploads/documents/' . $applicantId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "", basename($file['name'], ".$extension")) . ".$extension";
        $destPath = $uploadDir . $newFilename;
        
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $dbPath = 'uploads/documents/' . $applicantId . '/' . $newFilename;
            
            $stmt = $pdo->prepare("INSERT INTO documents (applicant_id, file_name, file_path, doc_type) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$applicantId, $file['name'], $dbPath, $docType])) {
                $success = "Document uploaded successfully.";
            } else {
                $error = "Database error while saving document record.";
            }
        } else {
            $error = "Failed to move uploaded file.";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM documents WHERE applicant_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$applicantId]);
$documents = $stmt->fetchAll();

$pageTitle = "My Documents";
include '../includes/header.php';
include '../includes/nav-applicant.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1>My Documents</h1>
    </div>

    <?php if ($error): ?><div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
        <div class="card">
            <div class="card-header">
                <h3>Upload New Document</h3>
            </div>
            <form method="POST" enctype="multipart/form-data" onsubmit="const fileInput = document.getElementById('document'); return validateFileSize(fileInput, 10);">
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="doc_type" class="form-control" required>
                        <option value="Resume">Resume / CV</option>
                        <option value="Government ID">Government ID</option>
                        <option value="Certification">Certification</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select File (Max 10MB, PDF/JPG/PNG)</label>
                    <input type="file" name="document" id="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required style="padding-top: 0.5rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-upload"></i> Upload File</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Uploaded Files</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>File Name</th>
                            <th>Uploaded On</th>
                            <th>AI Validation Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($documents) === 0): ?>
                            <tr><td colspan="4">No documents uploaded yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($doc['doc_type']) ?></strong></td>
                            <td><a href="/excell-mark/<?= $doc['file_path'] ?>" target="_blank" style="color: var(--color-admin); text-decoration: underline;"><?= htmlspecialchars($doc['file_name']) ?></a></td>
                            <td><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></td>
                            <td>
                                <?php if ($doc['is_validated']): ?>
                                    <span class="badge badge-low"><i class="fa-solid fa-check-circle"></i> Checked</span>
                                <?php else: ?>
                                    <span class="badge badge-mod">Pending Scan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
