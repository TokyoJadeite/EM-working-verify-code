<?php
// Fix recruiter sub-pages: convert main-content to main-wrapper layout
$files = [
    'C:/xampp/htdocs/excell-mark/recruiter/pending.php',
    'C:/xampp/htdocs/excell-mark/recruiter/reviewed.php',
    'C:/xampp/htdocs/excell-mark/recruiter/shortlisted.php',
    'C:/xampp/htdocs/excell-mark/recruiter/hired.php',
    'C:/xampp/htdocs/excell-mark/recruiter/overdue.php',
    'C:/xampp/htdocs/excell-mark/recruiter/my-posts.php',
    'C:/xampp/htdocs/excell-mark/recruiter/messages.php',
    'C:/xampp/htdocs/excell-mark/recruiter/documents.php',
    'C:/xampp/htdocs/excell-mark/recruiter/quota.php',
    'C:/xampp/htdocs/excell-mark/recruiter/my-funnel.php',
    'C:/xampp/htdocs/excell-mark/recruiter/thread.php',
];

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $content = file_get_contents($f);
    
    // Skip if already converted
    if (strpos($content, 'main-wrapper') !== false) continue;
    if (strpos($content, 'main-content') === false) continue;
    
    // Move nav-recruiter include from before main-content to inside main-wrapper
    $content = str_replace(
        "include '../includes/nav-recruiter.php';\n?>",
        "?>",
        $content
    );
    
    // Replace main-content with main-wrapper + sidebar + top-nav + content-area
    $content = preg_replace(
        '/<div class="main-content">\s*<div class="top-header">\s*<h1>(.*?)<\/h1>\s*(.*?)\s*<\/div>/s',
        '<div class="main-wrapper">' . "\n" .
        '    <?php include \'../includes/nav-recruiter.php\'; ?>' . "\n" .
        '    <div class="top-nav"><div class="page-title">$1</div><div class="top-nav-actions">$2</div></div>' . "\n" .
        '    <div class="content-area">',
        $content
    );
    
    // Fix the closing: replace </div> before footer with proper closing
    // Find last </div> before footer include and add content-area close
    $content = str_replace(
        "</div>\n\n<?php include '../includes/footer.php'; ?>",
        "</div>\n\n    </div>\n</div>\n<?php include '../includes/footer.php'; ?>",
        $content
    );
    
    file_put_contents($f, $content);
    echo "Fixed: " . basename($f) . "\n";
}
echo "Done\n";
