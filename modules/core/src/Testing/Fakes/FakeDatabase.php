<?php

namespace Modules\Core\Testing\Fakes;

/**
 * Fake Database Connection
 * 
 * Provides a fake database that stores data in memory for testing.
 * Preferred over mocks for more realistic test behavior.
 */
class FakeDatabase
{
    protected array $tables = [];
    protected array $queries = [];
    protected int $lastInsertId = 0;
    protected int $affectedRows = 0;

    /**
     * Insert data into a table
     */
    public function insert(string $table, array $data): bool
    {
        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
        }

        // Generate auto-increment ID if not provided
        if (!isset($data['id'])) {
            $this->lastInsertId++;
            $data['id'] = $this->lastInsertId;
        } else {
            $this->lastInsertId = max($this->lastInsertId, $data['id']);
        }

        $this->tables[$table][] = $data;
        $this->affectedRows = 1;
        $this->queries[] = ['type' => 'INSERT', 'table' => $table, 'data' => $data];

        return true;
    }

    /**
     * Select data from a table
     */
    public function select(string $table, array $where = []): array
    {
        if (!isset($this->tables[$table])) {
            return [];
        }

        $this->queries[] = ['type' => 'SELECT', 'table' => $table, 'where' => $where];

        if (empty($where)) {
            return $this->tables[$table];
        }

        return array_values(array_filter($this->tables[$table], function ($row) use ($where) {
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] !== $value) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Update data in a table
     */
    public function update(string $table, array $data, array $where): int
    {
        if (!isset($this->tables[$table])) {
            $this->affectedRows = 0;
            return 0;
        }

        $count = 0;
        foreach ($this->tables[$table] as &$row) {
            $matches = true;
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                foreach ($data as $key => $value) {
                    $row[$key] = $value;
                }
                $count++;
            }
        }

        $this->affectedRows = $count;
        $this->queries[] = ['type' => 'UPDATE', 'table' => $table, 'data' => $data, 'where' => $where];

        return $count;
    }

    /**
     * Delete data from a table
     */
    public function delete(string $table, array $where): int
    {
        if (!isset($this->tables[$table])) {
            $this->affectedRows = 0;
            return 0;
        }

        $originalCount = count($this->tables[$table]);
        $this->tables[$table] = array_values(array_filter($this->tables[$table], function ($row) use ($where) {
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] !== $value) {
                    return true; // Keep this row
                }
            }
            return false; // Remove this row
        }));

        $deletedCount = $originalCount - count($this->tables[$table]);
        $this->affectedRows = $deletedCount;
        $this->queries[] = ['type' => 'DELETE', 'table' => $table, 'where' => $where];

        return $deletedCount;
    }

    /**
     * Get last insert ID
     */
    public function insertId(): int
    {
        return $this->lastInsertId;
    }

    /**
     * Get affected rows from last query
     */
    public function affectedRows(): int
    {
        return $this->affectedRows;
    }

    /**
     * Get all executed queries
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get last executed query
     */
    public function getLastQuery(): ?array
    {
        return !empty($this->queries) ? end($this->queries) : null;
    }

    /**
     * Clear all data and queries
     */
    public function clear(): void
    {
        $this->tables = [];
        $this->queries = [];
        $this->lastInsertId = 0;
        $this->affectedRows = 0;
    }

    /**
     * Truncate a table
     */
    public function truncate(string $table): void
    {
        $this->tables[$table] = [];
        $this->queries[] = ['type' => 'TRUNCATE', 'table' => $table];
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    /**
     * Get all data from a table
     */
    public function getTable(string $table): array
    {
        return $this->tables[$table] ?? [];
    }

    /**
     * Count rows in a table
     */
    public function count(string $table, array $where = []): int
    {
        return count($this->select($table, $where));
    }
}
