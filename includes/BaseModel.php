<?php
/**
 * Base Model Class
 * Provides common database operations for all models
 */

require_once __DIR__ . '/functions.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Find record by ID
     * @param int $id
     * @return array|false
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->queryOne($sql, [$id]);
    }
    
    /**
     * Find all records with optional conditions
     * @param array $conditions
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findAll($conditions = [], $orderBy = '', $limit = 0, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Find one record by conditions
     * @param array $conditions
     * @return array|false
     */
    public function findOne($conditions) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $sql .= " LIMIT 1";
        
        return $this->db->queryOne($sql, $params);
    }
    
    /**
     * Insert new record
     * @param array $data
     * @return string|false Last insert ID or false on failure
     */
    public function insert($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        if ($this->db->execute($sql, array_values($data))) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update record by ID
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = ?";
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Update records by conditions
     * @param array $data
     * @param array $conditions
     * @return bool
     */
    public function updateWhere($data, $conditions) {
        $setFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setFields[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $whereClause = [];
        foreach ($conditions as $field => $value) {
            $whereClause[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setFields) . " WHERE " . implode(' AND ', $whereClause);
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Delete record by ID
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Delete records by conditions
     * @param array $conditions
     * @return bool
     */
    public function deleteWhere($conditions) {
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $whereClause);
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Count records
     * @param array $conditions
     * @return int
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $result = $this->db->queryOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Check if record exists
     * @param array $conditions
     * @return bool
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }
    
    /**
     * Execute custom SQL query
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }
    
    /**
     * Execute custom SQL query and return single row
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    protected function queryOne($sql, $params = []) {
        return $this->db->queryOne($sql, $params);
    }
    
    /**
     * Execute custom SQL statement
     * @param string $sql
     * @param array $params
     * @return bool
     */
    protected function execute($sql, $params = []) {
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Check if a column exists in the current table
     * @param string $columnName
     * @return bool
     */
    public function columnExists($columnName) {
        try {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE ?";
            $result = $this->db->queryOne($sql, [$columnName]);
            error_log("Column check for {$columnName} in {$this->table}: " . ($result ? 'FOUND' : 'NOT FOUND'));
            return !empty($result);
        } catch (Exception $e) {
            error_log("Column check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get last database error for debugging
     * @return string
     */
    public function getLastError() {
        if (method_exists($this->db, 'getLastError')) {
            return $this->db->getLastError();
        }
        return 'No error information available';
    }
}
?>