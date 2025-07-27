<?php
/**
 * Story Definition of Done (DoD) Validation Script
 * 
 * This script validates stories against the comprehensive DoD checklist
 * ensuring quality, completeness, and production readiness.
 * 
 * Usage: php validate_story_dod.php --story=X.Y [--detailed] [--fix]
 */

class StoryDoDValidator {
    private $storyId;
    private $basePath;
    private $detailed;
    private $autoFix;
    private $results = [];
    private $score = 0;
    private $maxScore = 0;

    public function __construct($storyId, $detailed = false, $autoFix = false) {
        $this->storyId = $storyId;
        $this->basePath = __DIR__;
        $this->detailed = $detailed;
        $this->autoFix = $autoFix;
    }

    /**
     * Run complete DoD validation
     */
    public function validate() {
        echo "🔍 Story DoD Validation: {$this->storyId}\n";
        echo str_repeat("=", 50) . "\n\n";

        // Core validation categories
        $this->validateRequirements();
        $this->validateTechnicalImplementation();
        $this->validateDocumentation();
        $this->validateTesting();
        $this->validateProductionReadiness();
        $this->validateQualityAssurance();
        $this->validateIntegration();
        $this->validateCompleteness();

        // Generate final report
        $this->generateReport();
    }

    /**
     * 1. Requirements Validation
     */
    private function validateRequirements() {
        $this->startSection("📋 Requirements Validation");

        // Check story file exists
        $storyFile = "{$this->basePath}/docs/stories/{$this->storyId}.story.md";
        $this->checkFile("Story file exists", $storyFile, true);

        if (file_exists($storyFile)) {
            $content = file_get_contents($storyFile);
            
            // Check acceptance criteria
            $this->checkPattern("Acceptance Criteria defined", $content, '/## Acceptance Criteria/i');
            $this->checkPattern("Tasks/Subtasks defined", $content, '/## Tasks \/ Subtasks/i');
            $this->checkPattern("Dev Agent Record present", $content, '/## Dev Agent Record/i');
            
            // Check completion status
            $completedTasks = preg_match_all('/- \[x\]/i', $content);
            $totalTasks = preg_match_all('/- \[[ x]\]/i', $content);
            
            if ($totalTasks > 0) {
                $completionRate = ($completedTasks / $totalTasks) * 100;
                $this->addResult("Task completion rate", $completionRate >= 100, 
                    sprintf("%.1f%% (%d/%d tasks)", $completionRate, $completedTasks, $totalTasks));
            }
        }
    }

    /**
     * 2. Technical Implementation Validation
     */
    private function validateTechnicalImplementation() {
        $this->startSection("⚡ Technical Implementation");

        // Check for common implementation files
        $patterns = [
            'Database migrations' => "/database/*.sql",
            'API endpoints' => "/api/**/*.php", 
            'Cron jobs' => "/cron/*.php",
            'Test files' => "/tests/**/*.php"
        ];

        foreach ($patterns as $type => $pattern) {
            $files = $this->findFiles($pattern);
            $this->addResult($type, count($files) > 0, count($files) . " files found");
        }

        // Check for security patterns
        $this->checkSecurityPatterns();

        // Check for performance considerations
        $this->checkPerformancePatterns();
    }

    /**
     * 3. Documentation Validation
     */
    private function validateDocumentation() {
        $this->startSection("📚 Documentation");

        $storyFile = "{$this->basePath}/docs/stories/{$this->storyId}.story.md";
        
        if (file_exists($storyFile)) {
            $content = file_get_contents($storyFile);
            
            // Check required sections
            $requiredSections = [
                'Story definition' => '/## Story/i',
                'Acceptance Criteria' => '/## Acceptance Criteria/i',
                'Tasks/Subtasks' => '/## Tasks \/ Subtasks/i',
                'Dev Notes' => '/## Dev Notes/i',
                'Testing requirements' => '/## Testing/i',
                'Change Log' => '/## Change Log/i'
            ];

            foreach ($requiredSections as $section => $pattern) {
                $this->checkPattern($section, $content, $pattern);
            }

            // Check for completion report
            $reportFile = "{$this->basePath}/story_{$this->storyId}_completion_report.md";
            $this->checkFile("Completion report exists", $reportFile);
        }
    }

    /**
     * 4. Testing Validation
     */
    private function validateTesting() {
        $this->startSection("🧪 Testing & Validation");

        // Look for test files
        $testFiles = $this->findFiles("/tests/**/*test*.php");
        $this->addResult("Test files created", count($testFiles) > 0, count($testFiles) . " test files");

        // Check for validation scripts
        $validationFiles = $this->findFiles("/validate_*.php");
        $this->addResult("Validation scripts", count($validationFiles) > 0, count($validationFiles) . " validation scripts");

        // Check for specific testing patterns
        if (count($testFiles) > 0) {
            $this->checkTestingPatterns($testFiles);
        }
    }

    /**
     * 5. Production Readiness Validation
     */
    private function validateProductionReadiness() {
        $this->startSection("🚀 Production Readiness");

        // Check for migration scripts
        $migrationFiles = $this->findFiles("/database/migration*.sql");
        $this->addResult("Migration scripts", count($migrationFiles) > 0, count($migrationFiles) . " migration files");

        // Check for rollback scripts
        $rollbackFiles = $this->findFiles("/database/rollback*.sql");
        $this->addResult("Rollback scripts", count($rollbackFiles) > 0, count($rollbackFiles) . " rollback files");

        // Check for deployment guides
        $deployGuides = $this->findFiles("/database/*deployment*.md");
        $this->addResult("Deployment guides", count($deployGuides) > 0, count($deployGuides) . " deployment guides");

        // Check for backup procedures
        $backupFiles = $this->findFiles("/scripts/backup*");
        $this->addResult("Backup procedures", count($backupFiles) > 0, count($backupFiles) . " backup scripts");
    }

    /**
     * 6. Quality Assurance Validation
     */
    private function validateQualityAssurance() {
        $this->startSection("🏆 Quality Assurance");

        $storyFile = "{$this->basePath}/docs/stories/{$this->storyId}.story.md";
        
        if (file_exists($storyFile)) {
            $content = file_get_contents($storyFile);
            
            // Check for QA section
            $this->checkPattern("QA Results section", $content, '/## QA Results/i');
            
            // Check for completion status
            $this->checkPattern("Ready for Review status", $content, '/Ready for Review|COMPLETED/i');
            
            // Check for sign-off indicators
            $this->checkPattern("Agent completion notes", $content, '/Completion Notes List/i');
            $this->checkPattern("File list documented", $content, '/File List/i');
        }

        // Check for critical file existence
        $this->checkCriticalFiles();
    }

    /**
     * 7. Integration & Compatibility Validation
     */
    private function validateIntegration() {
        $this->startSection("🔗 Integration & Compatibility");

        // Check database connectivity
        $this->checkDatabaseIntegration();

        // Check for API compatibility
        $this->checkApiCompatibility();

        // Check file structure integrity
        $this->checkFileStructure();
    }

    /**
     * 8. Documentation Completeness
     */
    private function validateCompleteness() {
        $this->startSection("✅ Completeness Check");

        // Final completeness checks
        $storyFile = "{$this->basePath}/docs/stories/{$this->storyId}.story.md";
        
        if (file_exists($storyFile)) {
            $content = file_get_contents($storyFile);
            $lines = explode("\n", $content);
            
            $this->addResult("Story file size", strlen($content) > 1000, strlen($content) . " characters");
            $this->addResult("Comprehensive content", count($lines) > 50, count($lines) . " lines");
            
            // Check for implementation evidence
            $this->checkPattern("Implementation evidence", $content, '/files? created|modified|implemented/i');
        }
    }

    /**
     * Security pattern checks
     */
    private function checkSecurityPatterns() {
        $phpFiles = $this->findFiles("/**/*.php");
        $securityIssues = 0;
        $totalFiles = count($phpFiles);

        foreach ($phpFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Check for SQL injection protection
                if (preg_match('/mysql_query|mysqli_query/', $content) && 
                    !preg_match('/prepare|bind_param/', $content)) {
                    $securityIssues++;
                }
                
                // Check for XSS protection
                if (preg_match('/echo\s+\$_/', $content) && 
                    !preg_match('/htmlspecialchars|htmlentities/', $content)) {
                    $securityIssues++;
                }
            }
        }

        $this->addResult("Security patterns", $securityIssues === 0, 
            $securityIssues > 0 ? "$securityIssues potential issues found" : "No issues detected");
    }

    /**
     * Performance pattern checks
     */
    private function checkPerformancePatterns() {
        $phpFiles = $this->findFiles("/**/*.php");
        $performanceGood = 0;
        $totalChecked = 0;

        foreach ($phpFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $totalChecked++;
                
                // Check for LIMIT clauses in queries
                if (preg_match('/SELECT.*FROM/i', $content)) {
                    if (preg_match('/LIMIT\s+\d+/i', $content)) {
                        $performanceGood++;
                    }
                }
            }
        }

        if ($totalChecked > 0) {
            $rate = ($performanceGood / $totalChecked) * 100;
            $this->addResult("Performance optimization", $rate > 70, 
                sprintf("%.1f%% of database queries optimized", $rate));
        }
    }

    /**
     * Testing pattern checks
     */
    private function checkTestingPatterns($testFiles) {
        $hasUnitTests = false;
        $hasIntegrationTests = false;

        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                if (preg_match('/test.*function|function.*test/i', $content)) {
                    $hasUnitTests = true;
                }
                
                if (preg_match('/database|integration|api/i', $content)) {
                    $hasIntegrationTests = true;
                }
            }
        }

        $this->addResult("Unit tests", $hasUnitTests, $hasUnitTests ? "Unit test patterns found" : "No unit test patterns");
        $this->addResult("Integration tests", $hasIntegrationTests, $hasIntegrationTests ? "Integration test patterns found" : "No integration patterns");
    }

    /**
     * Database integration check
     */
    private function checkDatabaseIntegration() {
        $configFile = "{$this->basePath}/config/database.php";
        $this->checkFile("Database config", $configFile);

        // Check for migration completeness
        $migrationTest = "{$this->basePath}/test_migration_complete.php";
        $this->checkFile("Migration test", $migrationTest);
    }

    /**
     * API compatibility check
     */
    private function checkApiCompatibility() {
        $apiFiles = $this->findFiles("/api/**/*.php");
        $compatibleApis = 0;

        foreach ($apiFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Check for proper JSON response format
                if (preg_match('/json_encode|Content-Type.*json/i', $content)) {
                    $compatibleApis++;
                }
            }
        }

        $this->addResult("API compatibility", count($apiFiles) === 0 || $compatibleApis > 0, 
            count($apiFiles) > 0 ? "$compatibleApis/" . count($apiFiles) . " APIs compatible" : "No APIs to check");
    }

    /**
     * Critical files check
     */
    private function checkCriticalFiles() {
        $criticalFiles = [
            'includes/functions.php',
            'includes/permissions.php',
            'config/config.php',
            'config/database.php'
        ];

        $missingFiles = 0;
        foreach ($criticalFiles as $file) {
            $fullPath = "{$this->basePath}/$file";
            if (!file_exists($fullPath)) {
                $missingFiles++;
            }
        }

        $this->addResult("Critical files intact", $missingFiles === 0, 
            $missingFiles > 0 ? "$missingFiles critical files missing" : "All critical files present");
    }

    /**
     * File structure check
     */
    private function checkFileStructure() {
        $requiredDirs = ['docs', 'includes', 'api', 'pages', 'config'];
        $missingDirs = 0;

        foreach ($requiredDirs as $dir) {
            if (!is_dir("{$this->basePath}/$dir")) {
                $missingDirs++;
            }
        }

        $this->addResult("File structure", $missingDirs === 0, 
            $missingDirs > 0 ? "$missingDirs required directories missing" : "File structure intact");
    }

    /**
     * Helper methods
     */
    private function startSection($title) {
        echo "\n$title\n";
        echo str_repeat("-", strlen($title)) . "\n";
    }

    private function checkFile($description, $file, $required = false) {
        $exists = file_exists($file);
        $this->addResult($description, $exists, $exists ? "✅ Found" : ($required ? "❌ Missing (Required)" : "⚠️ Missing"));
    }

    private function checkPattern($description, $content, $pattern) {
        $found = preg_match($pattern, $content);
        $this->addResult($description, $found, $found ? "✅ Found" : "❌ Missing");
    }

    private function addResult($description, $passed, $details = "") {
        $this->results[] = [
            'description' => $description,
            'passed' => $passed,
            'details' => $details
        ];
        
        if ($passed) $this->score++;
        $this->maxScore++;
        
        $status = $passed ? "✅" : "❌";
        $detailStr = $details ? " ($details)" : "";
        echo "$status $description$detailStr\n";
    }

    private function findFiles($pattern) {
        $files = [];
        $pattern = str_replace('/**/', '/', $pattern);
        $pattern = str_replace('**/', '', $pattern);
        
        // Simple file finding - can be enhanced with proper glob patterns
        $searchPath = $this->basePath . str_replace('*', '', dirname($pattern));
        $filename = basename($pattern);
        
        if (is_dir($searchPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if (fnmatch($filename, $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }

    /**
     * Generate final validation report
     */
    private function generateReport() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 STORY DOD VALIDATION REPORT\n";
        echo str_repeat("=", 50) . "\n";

        $percentage = $this->maxScore > 0 ? ($this->score / $this->maxScore) * 100 : 0;
        
        echo "Story ID: {$this->storyId}\n";
        echo "Validation Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Overall Score: {$this->score}/{$this->maxScore} ({$percentage:.1f}%)\n\n";

        // Determine status
        if ($percentage >= 95) {
            $status = "🎉 EXCELLENT - Production Ready";
            $color = "\033[32m"; // Green
        } elseif ($percentage >= 85) {
            $status = "✅ GOOD - Minor issues to address";
            $color = "\033[33m"; // Yellow
        } elseif ($percentage >= 70) {
            $status = "⚠️ NEEDS WORK - Several issues to fix";
            $color = "\033[31m"; // Red
        } else {
            $status = "❌ NOT READY - Major issues present";
            $color = "\033[91m"; // Bright Red
        }

        echo $color . "Status: $status\033[0m\n\n";

        // Recommendations
        echo "📋 RECOMMENDATIONS:\n";
        $failed = array_filter($this->results, function($r) { return !$r['passed']; });
        
        if (count($failed) === 0) {
            echo "✅ All DoD criteria met! Story is ready for production.\n";
        } else {
            echo "The following items need attention:\n";
            foreach (array_slice($failed, 0, 5) as $item) {
                echo "• {$item['description']}: {$item['details']}\n";
            }
            
            if (count($failed) > 5) {
                echo "• ... and " . (count($failed) - 5) . " more items\n";
            }
        }

        echo "\n📁 For detailed analysis, check:\n";
        echo "• Story file: docs/stories/{$this->storyId}.story.md\n";
        echo "• Completion report: story_{$this->storyId}_completion_report.md\n";
        echo "• DoD checklist: docs/story-dod-checklist.md\n";
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $options = getopt("", ["story:", "detailed", "fix", "help"]);
    
    if (isset($options['help']) || !isset($options['story'])) {
        echo "Story DoD Validation Tool\n";
        echo "Usage: php validate_story_dod.php --story=X.Y [options]\n\n";
        echo "Options:\n";
        echo "  --story=X.Y    Story ID to validate (required)\n";
        echo "  --detailed     Show detailed analysis\n";
        echo "  --fix          Attempt to auto-fix common issues\n";
        echo "  --help         Show this help message\n\n";
        echo "Examples:\n";
        echo "  php validate_story_dod.php --story=1.2\n";
        echo "  php validate_story_dod.php --story=1.3 --detailed\n";
        exit(0);
    }
    
    $storyId = $options['story'];
    $detailed = isset($options['detailed']);
    $autoFix = isset($options['fix']);
    
    $validator = new StoryDoDValidator($storyId, $detailed, $autoFix);
    $validator->validate();
    
} else {
    // Web interface (basic)
    if (isset($_GET['story'])) {
        $storyId = $_GET['story'];
        $validator = new StoryDoDValidator($storyId, true, false);
        
        ob_start();
        $validator->validate();
        $output = ob_get_clean();
        
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<h1>Story DoD Validator</h1>";
        echo "<form method='GET'>";
        echo "Story ID: <input type='text' name='story' placeholder='1.2' required>";
        echo " <button type='submit'>Validate</button>";
        echo "</form>";
    }
}
?>