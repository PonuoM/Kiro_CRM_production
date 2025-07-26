<?php
/**
 * CRM System Backup Script
 * This script creates automated backups of the database and files
 * Can be run via cron job or manually
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class BackupManager {
    private $backupPath;
    private $dbConfig;
    private $retentionDays;
    
    public function __construct() {
        global $db_config;
        $this->dbConfig = $db_config;
        $this->backupPath = defined('BACKUP_PATH') ? BACKUP_PATH : __DIR__ . '/../backups/';
        $this->retentionDays = defined('BACKUP_RETENTION_DAYS') ? BACKUP_RETENTION_DAYS : 30;
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    /**
     * Create complete backup (database + files)
     * @return array
     */
    public function createFullBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $results = [];
        
        try {
            // Database backup
            $dbBackupFile = $this->backupPath . "database_backup_{$timestamp}.sql";
            $results['database'] = $this->backupDatabase($dbBackupFile);
            
            // Files backup
            $filesBackupFile = $this->backupPath . "files_backup_{$timestamp}.tar.gz";
            $results['files'] = $this->backupFiles($filesBackupFile);
            
            // Create backup manifest
            $manifest = [
                'timestamp' => $timestamp,
                'database_file' => basename($dbBackupFile),
                'files_file' => basename($filesBackupFile),
                'database_size' => file_exists($dbBackupFile) ? filesize($dbBackupFile) : 0,
                'files_size' => file_exists($filesBackupFile) ? filesize($filesBackupFile) : 0,
                'status' => ($results['database']['success'] && $results['files']['success']) ? 'success' : 'partial'
            ];
            
            file_put_contents($this->backupPath . "manifest_{$timestamp}.json", json_encode($manifest, JSON_PRETTY_PRINT));
            
            // Clean old backups
            $this->cleanOldBackups();
            
            $results['manifest'] = $manifest;
            $this->logMessage('INFO', 'Full backup completed', $manifest);
            
        } catch (Exception $e) {
            $this->logMessage('ERROR', 'Backup failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Backup database to SQL file
     * @param string $outputFile
     * @return array
     */
    private function backupDatabase($outputFile) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Get all tables
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sql = "-- CRM System Database Backup\n";
            $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . $this->dbConfig['dbname'] . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
            
            foreach ($tables as $table) {
                // Get table structure
                $sql .= "-- Table structure for `{$table}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                
                $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                // Get table data
                $sql .= "-- Data for table `{$table}`\n";
                $rows = $pdo->query("SELECT * FROM `{$table}`");
                
                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $sql .= "INSERT INTO `{$table}` (";
                    $sql .= "`" . implode("`, `", array_keys($row)) . "`";
                    $sql .= ") VALUES (";
                    
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = $pdo->quote($value);
                        }
                    }
                    $sql .= implode(', ', $values);
                    $sql .= ");\n";
                }
                $sql .= "\n";
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            
            // Write to file
            $bytesWritten = file_put_contents($outputFile, $sql);
            
            return [
                'success' => $bytesWritten !== false,
                'file' => $outputFile,
                'size' => $bytesWritten,
                'tables' => count($tables)
            ];
            
        } catch (Exception $e) {
            $this->logMessage('ERROR', 'Database backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Backup files to compressed archive
     * @param string $outputFile
     * @return array
     */
    private function backupFiles($outputFile) {
        try {
            $rootPath = realpath(__DIR__ . '/../');
            
            // Files and directories to exclude
            $excludes = [
                'backups',
                'logs/*.log',
                'uploads/temp',
                '.git',
                'tests',
                '*.tmp',
                '*.cache'
            ];
            
            // Create tar command
            $excludeParams = '';
            foreach ($excludes as $exclude) {
                $excludeParams .= " --exclude='{$exclude}'";
            }
            
            $command = "tar -czf '{$outputFile}' -C '" . dirname($rootPath) . "' {$excludeParams} '" . basename($rootPath) . "'";
            
            // Execute backup
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $success = ($returnCode === 0 && file_exists($outputFile));
            
            return [
                'success' => $success,
                'file' => $outputFile,
                'size' => $success ? filesize($outputFile) : 0,
                'command' => $command,
                'output' => implode("\n", $output)
            ];
            
        } catch (Exception $e) {
            $this->logMessage('ERROR', 'Files backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean old backup files
     */
    private function cleanOldBackups() {
        try {
            $cutoffTime = time() - ($this->retentionDays * 24 * 60 * 60);
            $files = glob($this->backupPath . '*');
            
            $deletedCount = 0;
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    if (unlink($file)) {
                        $deletedCount++;
                    }
                }
            }
            
            $this->logMessage('INFO', "Cleaned {$deletedCount} old backup files");
            
        } catch (Exception $e) {
            $this->logMessage('ERROR', 'Failed to clean old backups: ' . $e->getMessage());
        }
    }
    
    /**
     * List available backups
     * @return array
     */
    public function listBackups() {
        $backups = [];
        $manifestFiles = glob($this->backupPath . 'manifest_*.json');
        
        foreach ($manifestFiles as $manifestFile) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            if ($manifest) {
                $manifest['manifest_file'] = basename($manifestFile);
                $backups[] = $manifest;
            }
        }
        
        // Sort by timestamp (newest first)
        usort($backups, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        
        return $backups;
    }
    
    /**
     * Restore database from backup file
     * @param string $backupFile
     * @return array
     */
    public function restoreDatabase($backupFile) {
        try {
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found: {$backupFile}");
            }
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Read SQL file
            $sql = file_get_contents($backupFile);
            if ($sql === false) {
                throw new Exception("Failed to read backup file");
            }
            
            // Execute SQL statements
            $pdo->exec($sql);
            
            $this->logMessage('INFO', 'Database restored successfully from: ' . basename($backupFile));
            
            return [
                'success' => true,
                'file' => $backupFile,
                'message' => 'Database restored successfully'
            ];
            
        } catch (Exception $e) {
            $this->logMessage('ERROR', 'Database restore failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get backup statistics
     * @return array
     */
    public function getBackupStats() {
        $backups = $this->listBackups();
        $totalSize = 0;
        $oldestBackup = null;
        $newestBackup = null;
        
        foreach ($backups as $backup) {
            $totalSize += $backup['database_size'] + $backup['files_size'];
            
            if (!$oldestBackup || $backup['timestamp'] < $oldestBackup['timestamp']) {
                $oldestBackup = $backup;
            }
            
            if (!$newestBackup || $backup['timestamp'] > $newestBackup['timestamp']) {
                $newestBackup = $backup;
            }
        }
        
        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'oldest_backup' => $oldestBackup,
            'newest_backup' => $newestBackup,
            'retention_days' => $this->retentionDays
        ];
    }
    
    /**
     * Format bytes to human readable format
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Log message
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function logMessage($level, $message, $context = []) {
        if (function_exists('logMessage')) {
            logMessage($level, $message, $context);
        } else {
            error_log("[{$level}] {$message}");
        }
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    echo "CRM System Backup Script\n";
    echo "========================\n\n";
    
    $backup = new BackupManager();
    
    $action = $argv[1] ?? 'backup';
    
    switch ($action) {
        case 'backup':
            echo "Creating full backup...\n";
            $result = $backup->createFullBackup();
            
            if (isset($result['manifest'])) {
                echo "Backup completed successfully!\n";
                echo "Database: " . $result['manifest']['database_file'] . " (" . $backup->formatBytes($result['manifest']['database_size']) . ")\n";
                echo "Files: " . $result['manifest']['files_file'] . " (" . $backup->formatBytes($result['manifest']['files_size']) . ")\n";
            } else {
                echo "Backup failed!\n";
                if (isset($result['error'])) {
                    echo "Error: " . $result['error'] . "\n";
                }
            }
            break;
            
        case 'list':
            echo "Available backups:\n";
            $backups = $backup->listBackups();
            
            if (empty($backups)) {
                echo "No backups found.\n";
            } else {
                foreach ($backups as $b) {
                    echo "- {$b['timestamp']} ({$b['status']}) - DB: " . $backup->formatBytes($b['database_size']) . ", Files: " . $backup->formatBytes($b['files_size']) . "\n";
                }
            }
            break;
            
        case 'stats':
            echo "Backup statistics:\n";
            $stats = $backup->getBackupStats();
            echo "Total backups: {$stats['total_backups']}\n";
            echo "Total size: {$stats['total_size_formatted']}\n";
            echo "Retention: {$stats['retention_days']} days\n";
            
            if ($stats['newest_backup']) {
                echo "Newest: {$stats['newest_backup']['timestamp']}\n";
            }
            if ($stats['oldest_backup']) {
                echo "Oldest: {$stats['oldest_backup']['timestamp']}\n";
            }
            break;
            
        default:
            echo "Usage: php backup.php [backup|list|stats]\n";
            echo "  backup - Create full backup\n";
            echo "  list   - List available backups\n";
            echo "  stats  - Show backup statistics\n";
            break;
    }
    
    echo "\n";
}
?>