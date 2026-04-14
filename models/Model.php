<?php
// ============================================
// Base Model (Abstracts SQL Queries)
// ============================================

require_once __DIR__ . '/../config/Database.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all properties of the child class that are not in the base Model
     */
    protected function getAttributes() {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);
        
        $attributes = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            // Exclude Model base class properties
            if (!in_array($name, ['db', 'table', 'primaryKey'])) {
                $property->setAccessible(true);
                $value = $property->getValue($this);
                if ($value !== null && $name !== $this->primaryKey && $name !== 'date_ajout') {
                    $attributes[$name] = $value;
                }
            }
        }
        return $attributes;
    }

    /**
     * Find a single record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get all records
     */
    public function list($orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
             $sql .= " ORDER BY " . $orderBy;
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Add a new record
     */
    public function add() {
        $attributes = $this->getAttributes();
        $fields = array_keys($attributes);
        $placeholders = array_map(function($field) { return ':' . $field; }, $fields);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        
        foreach ($attributes as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Update an existing record
     */
    public function update() {
        $attributes = $this->getAttributes();
        $setClauses = [];
        foreach (array_keys($attributes) as $field) {
            $setClauses[] = "{$field} = :{$field}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = :pk_id";
        $stmt = $this->db->prepare($sql);

        foreach ($attributes as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        // Get the primary key value using reflection or getter if necessary, 
        // assuming here we have a getId() method or property is accessible.
        $pkValue = method_exists($this, 'get' . ucfirst($this->primaryKey)) 
            ? $this->{'get' . ucfirst($this->primaryKey)}() 
            : $this->{$this->primaryKey};

        $stmt->bindValue(':pk_id', $pkValue, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Delete a record by ID
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
