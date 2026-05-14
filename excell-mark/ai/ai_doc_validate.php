<?php
// ai/ai_doc_validate.php
// STUB for AI Document Date Validation

function validateDocumentExpiry($filePath) {
    // In a real implementation, you would pass the file to a Python microservice
    // running an OCR/Vision AI model to read the expiry date.
    
    // Simulating API call delay
    // sleep(1);
    
    // Simulate finding a valid date in the future for 80% of documents
    $isValid = mt_rand(1, 100) <= 80;
    
    if ($isValid) {
        $expiryDate = date('Y-m-d', strtotime('+' . mt_rand(1, 36) . ' months'));
    } else {
        $expiryDate = date('Y-m-d', strtotime('-' . mt_rand(1, 12) . ' months'));
    }
    
    return [
        'valid' => $isValid,
        'expiry_date' => $expiryDate,
        'confidence' => (mt_rand(85, 99) / 100)
    ];
}
