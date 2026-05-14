<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$userId = $_SESSION['user_id'];
$success = '';

// Check if already submitted pre-evaluation
$stmt = $pdo->prepare("SELECT id FROM evaluations WHERE user_id = ? AND type = 'pre' LIMIT 1");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    $success = "You have already completed the Pre-Evaluation. Thank you!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success) {
    // Save 5 questions
    for ($i = 1; $i <= 5; $i++) {
        $score = isset($_POST["q$i"]) ? (int)$_POST["q$i"] : 3;
        $pdo->prepare("INSERT INTO evaluations (user_id, type, question_id, score) VALUES (?, 'pre', ?, ?)")->execute([$userId, $i, $score]);
    }
    $success = "Thank you for completing the Pre-Evaluation. You may now explore the system.";
}

$pageTitle = "Pre-Evaluation";
include '../includes/header.php';
?>
<div style="max-width: 800px; margin: 2rem auto; padding: 2rem;" class="card">
    <h1 style="text-align: center; margin-bottom: 2rem; color: var(--color-admin);">ExcellMark Pre-Evaluation</h1>
    
    <?php if ($success): ?>
        <div class="flash-message flash-success" style="text-align: center; font-size: 1.2rem;"><?= htmlspecialchars($success) ?></div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="/excell-mark/<?= $_SESSION['role'] === 'applicant' ? 'applicant/home.php' : $_SESSION['role'] . '/dashboard.php' ?>" class="btn btn-primary">Go to Dashboard</a>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); margin-bottom: 2rem; text-align: center;">Please rate the following statements before using the system based on your expectations. (1 = Strongly Disagree, 5 = Strongly Agree)</p>
        
        <form method="POST">
            <?php 
            $questions = [
                1 => "I expect the system to be easy to use.",
                2 => "I expect the AI matching to improve the hiring process.",
                3 => "I expect the document validation to save time.",
                4 => "I believe the workload rebalancing feature will be useful.",
                5 => "I think the visual dashboard will help monitor performance effectively."
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
            
            <button type="submit" class="btn btn-admin" style="width: 100%; font-size: 1.1rem; padding: 1rem;">Submit Pre-Evaluation</button>
        </form>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
