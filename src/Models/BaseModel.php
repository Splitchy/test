<?php

namespace App\Models;

use App\Database\Database;

abstract class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function findBy(array $criteria): ?array
    {
        $whereClause = implode(' = ? AND ', array_keys($criteria)) . ' = ?';
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$whereClause}",
            array_values($criteria)
        );
    }

    public function findAll(array $criteria = [], string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($criteria)) {
            $whereClause = implode(' = ? AND ', array_keys($criteria)) . ' = ?';
            $sql .= " WHERE {$whereClause}";
            $params = array_values($criteria);
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

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        $filteredData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->insert($this->table, $filteredData);
    }

    public function update(int $id, array $data): bool
    {
        $filteredData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->update($this->table, $filteredData, [$this->primaryKey => $id]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->delete($this->table, [$this->primaryKey => $id]) > 0;
    }

    public function count(array $criteria = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($criteria)) {
            $whereClause = implode(' = ? AND ', array_keys($criteria)) . ' = ?';
            $sql .= " WHERE {$whereClause}";
            $params = array_values($criteria);
        }

        $result = $this->db->fetch($sql, $params);
        return (int) $result['count'];
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        return $this->db->query($sql, $params);
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->db->fetch($sql, $params);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    public function raw(string $sql, array $params = []): \PDOStatement
    {
        return $this->db->query($sql, $params);
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollback();
    }

    public function generateReference(string $prefix = ''): string
    {
        $timestamp = date('YmdHis');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return $prefix . $timestamp . $random;
    }
}