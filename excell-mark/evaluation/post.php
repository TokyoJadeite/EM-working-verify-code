<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$userId = $_SESSION['user_id'];
$success = '';

// Check if already submitted post-evaluation
$stmt = $pdo->prepare("SELECT id FROM evaluations WHERE user_id = ? AND type = 'post' LIMIT 1");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    $success = "You have already completed the Post-Evaluation. Thank you!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success) {
    // Save 5 questions
    $comments = $_POST['comments'] ?? '';
    for ($i = 1; $i <= 5; $i++) {
        $score = isset($_POST["q$i"]) ? (int)$_POST["q$i"] : 3;
        $pdo->prepare("INSERT INTO evaluations (user_id, type, question_id, score, comments) VALUES (?, 'post', ?, ?, ?)")->execute([$userId, $i, $score, $i == 1 ? $comments : null]);
    }
    $success = "Thank you for completing the Post-Evaluation. Your feedback is appreciated.";
}

$pageTitle = "Post-Evaluation";
include '../includes/header.php';
?>
<div style="max-width: 800px; margin: 2rem auto; padding: 2rem;" class="card">
    <h1 style="text-align: center; margin-bottom: 2rem; color: var(--color-recruiter);">ExcellMark Post-Evaluation</h1>
    
    <?php if ($success): ?>
        <div class="flash-message flash-success" style="text-align: center; font-size: 1.2rem;"><?= htmlspecialchars($success) ?></div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="/excell-mark/<?= $_SESSION['role'] === 'applicant' ? 'applicant/home.php' : $_SESSION['role'] . '/dashboard.php' ?>" class="btn btn-primary">Return to Dashboard</a>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); margin-bottom: 2rem; text-align: center;">Please rate your experience after testing the system. (1 = Strongly Disagree, 5 = Strongly Agree)</p>
        
        <form method="POST">
            <?php 
            $questions = [
                1 => "The system was easy to navigate and use.",
                2 => "The AI matching feature accurately suggested relevant jobs/applicants.",
                3 => "The document validation tool accurately caught expired documents.",
                4 => "The workload rebalancing and pipeline tracking helped manage tasks effectively.",
                5 => "Overall, the system meets the requirements for a modern recruitment platform."
            ];
            foreach ($questions as $id => $text): 
            ?>
            <div style="margin-bottom: 2rem;">
                <p style="margin-bottom: 0.5rem; font-weight: 500;"><?= $id ?>. <?= $text ?></p>
                <div style="display: flex; gap: 1.5rem;">
                    <?php for($j=1; $j<=5; $j++): ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="q<?= $id ?>" value="<?= $j ?>" required>
                        <?= $j ?>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="form-group">
                <label>Additional Comments or Suggestions (Optional)</label>
                <textarea name="comments" class="form-control" rows="4"></textarea>
            </div>
            
            <button type="submit" class="btn btn-recruiter" style="width: 100%; font-size: 1.1rem; padding: 1rem;">Submit Post-Evaluation</button>
        </form>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
