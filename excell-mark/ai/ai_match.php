<?php
// ai/ai_match.php
// STUB for AI Job Matching (Word2Vec)

function getAiJobMatches($applicantId, $jobPosts) {
    // In a real implementation, you would:
    // 1. Fetch applicant's uploaded resume (text extracted)
    // 2. Fetch job post descriptions
    // 3. Call a Python service running Word2Vec or similar model to get similarity scores
    
    // Simulate API delay
    // sleep(1);
    
    $matches = [];
    foreach ($jobPosts as $job) {
        // Random match score between 40 and 98
        $score = mt_rand(40, 98);
        $matches[$job['id']] = $score;
    }
    
    // Sort descending by score
    arsort($matches);
    
    return $matches;
}
