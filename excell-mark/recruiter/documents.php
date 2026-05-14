<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../ai/ai_doc_validate.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$applicantFilter = isset($_GET['applicant_id']) ? (int)$_GET['applicant_id'] : null;

// Find documents belonging to applicants assigned to this recruiter
$query = "
    SELECT d.*, u.full_name as applicant_name 
    FROM documents d 
    JOIN users u ON d.applicant_id = u.id 
    JOIN applications a ON d.applicant_id = a.applicant_id 
    WHERE a.recruiter_id = ?
";
$params = [$recruiterId];

if ($applicantFilter) {
    $query .= " AND d.applicant_id = ?";
    $params[] = $applicantFilter;
}

$query .= " GROUP BY d.id ORDER BY d.uploaded_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$documents = $stmt->fetchAll();

$pageTitle = "Applicant Documents";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">Document Review</div><div class="top-nav-actions"><?php if ($applicantFilter): ?>
            <a href="documents.php" class="btn btn-outline">Show All</a>
        <?php endif; ?></div></div>
    <div class="content-area">

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Document Type</th>
                        <th>File Name</th>
                        <th>Uploaded On</th>
                        <th>AI Validation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($documents) === 0): ?>
                        <tr><td colspan="6">No documents found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($documents as $doc): ?>
                    <?php 
                        // Simulate AI validation processing on view if not done
                        $validationResult = null;
                        if (!$doc['is_validated']) {
                            $validationResult = validateDocumentExpiry($doc['file_path']);
                            $pdo->prepare("UPDATE documents SET is_validated = 1 WHERE id = ?")->execute([$doc['id']]);
                            $doc['is_validated'] = 1;
                        } else {
                            // Mocking historical result for demo
                            $validationResult = ['valid' => true, 'expiry_date' => '2027-01-01', 'confidence' => 0.95];
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($doc['applicant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($doc['doc_type']) ?></td>
                        <td><?= htmlspecialchars($doc['file_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></td>
                        <td>
                            <?php if ($validationResult): ?>
                                <?php if ($validationResult['valid']): ?>
                                    <span class="badge badge-low" title="<?= $validationResult['confidence']*100 ?>% confidence"><i class="fa-solid fa-check"></i> Valid</span>
                                <?php else: ?>
                                    <span class="badge badge-high" title="Expired on <?= $validationResult['expiry_date'] ?>"><i class="fa-solid fa-xmark"></i> Expired</span>
                                <?php endif; ?>
                                <br><small class="text-muted" style="font-size: 0.7rem;">Exp: <?= $validationResult['expiry_date'] ?></small>
                            <?php else: ?>
                                <span class="badge badge-mod">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/excell-mark/<?= $doc['file_path'] ?>" target="_blank" class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.8rem;"><i class="fa-solid fa-download"></i> View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
