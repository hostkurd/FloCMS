<?php
namespace HostKurd\Flocms\Lib;
use PDO;
use Throwable;
use InvalidArgumentException;

class Database
{
    protected PDO $pdo;

    protected string $table = '';
    protected string $fields = '*';
    protected array  $where  = [];
    protected array  $joins  = [];
    protected int    $limit  = 0;
    protected int    $offset = 0;
    protected ?array $order  = null;

    protected array $allowedOperators = [
        '=', '!=', '<>', '<', '>', '<=', '>=',
        'LIKE', 'NOT LIKE',
        'IN', 'NOT IN',
        'IS', 'IS NOT'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ------------------ Core Helpers ------------------ */

    protected function reset(): void
    {
        $this->table  = '';
        $this->fields = '*';
        $this->where  = [];
        $this->joins  = [];
        $this->limit  = 0;
        $this->offset = 0;
        $this->order  = null;
    }

    protected function validateIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        // Handle aliases: "table alias" OR "table AS alias"
        if (preg_match('/^([a-zA-Z0-9_.]+)\s+(?:AS\s+)?([a-zA-Z0-9_]+)$/i', $identifier, $m)) {
            return $m[1] . ' ' . $m[2];
        }

        // Simple identifier: table, column, table.column
        if (preg_match('/^[a-zA-Z0-9_.]+$/', $identifier)) {
            return $identifier;
        }

        throw new InvalidArgumentException("Invalid identifier: {$identifier}");
    }

    protected function quote(string $identifier): string
    {
        // Handle alias cases
        if (preg_match('/^([a-zA-Z0-9_.]+)\s+(?:AS\s+)?([a-zA-Z0-9_]+)$/i', $identifier, $m)) {
            return $this->quote($m[1]) . ' ' . $m[2];
        }

        // Handle dotted identifiers: table.column
        if (str_contains($identifier, '.')) {
            return implode('.', array_map(
                fn ($part) => '`' . str_replace('`', '', $part) . '`',
                explode('.', $identifier)
            ));
        }

        return '`' . str_replace('`', '', $identifier) . '`';
    }

    /* ------------------ Transactions ------------------ */

    public function transaction(callable $callback)
    {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ------------------ Builder ------------------ */

    public function table(string $table): self
    {
        $this->table = $this->quote($this->validateIdentifier($table));
        return $this;
    }

    public function select(string|array $fields): self
    {
        if (is_array($fields)) {
            $fields = array_map(
                fn ($f) => $this->quote($this->validateIdentifier($f)),
                $fields
            );
            $this->fields = implode(', ', $fields);
        } else {
            $this->fields = $fields;
        }

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $operator = strtoupper($operator);

        if (!in_array($operator, $this->allowedOperators, true)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}");
        }

        $this->where[] = [
            'type'     => 'AND',
            'column'   => $this->quote($this->validateIdentifier($column)),
            'operator' => $operator,
            'value'    => $value,
        ];

        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $operator = strtoupper($operator);

        if (!in_array($operator, $this->allowedOperators, true)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}");
        }

        $this->where[] = [
            'type'     => 'OR',
            'column'   => $this->quote($this->validateIdentifier($column)),
            'operator' => $operator,
            'value'    => $value,
        ];

        return $this;
    }

    public function join(
        string $table,
        string $left,
        string $operator,
        string $right,
        string $type = 'INNER'
    ): self {
        $type = strtoupper($type);

        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS'], true)) {
            throw new InvalidArgumentException("Invalid JOIN type: {$type}");
        }

        $this->joins[] =
            "{$type} JOIN " .
            $this->quote($this->validateIdentifier($table)) .
            " ON " .
            $this->quote($this->validateIdentifier($left)) .
            " {$operator} " .
            $this->quote($this->validateIdentifier($right));

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $this->order = [
            'column'    => $this->quote($this->validateIdentifier($column)),
            'direction' => $direction
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    /* ------------------ SQL Builders ------------------ */

    protected function buildWhere(): array
    {
        if (empty($this->where)) {
            return ['', []];
        }

        $sql = ' WHERE ';
        $params = [];

        foreach ($this->where as $i => $w) {
            if ($i > 0) {
                $sql .= " {$w['type']} ";
            }
            $sql .= "{$w['column']} {$w['operator']} ?";
            $params[] = $w['value'];
        }

        return [$sql, $params];
    }

    /* ------------------ Execution ------------------ */

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function get(): array
    {
        $sql = "SELECT {$this->fields} FROM {$this->table}";

        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        [$whereSql, $params] = $this->buildWhere();
        $sql .= $whereSql;

        if ($this->order) {
            $sql .= " ORDER BY {$this->order['column']} {$this->order['direction']}";
        }

        if ($this->limit > 0) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset > 0) {
            $sql .= " OFFSET {$this->offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $this->reset();

        return $result;
    }

    public function first(): ?object
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function insert(array $data): int
    {
        $columns = array_map(
            fn ($c) => $this->quote($this->validateIdentifier($c)),
            array_keys($data)
        );

        $placeholders = array_fill(0, count($data), '?');

        $sql = "INSERT INTO {$this->table} (" .
            implode(', ', $columns) .
            ") VALUES (" .
            implode(', ', $placeholders) .
            ")";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        $this->reset();
        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data): bool
    {
        if (empty($this->where)) {
            throw new RuntimeException("UPDATE without WHERE is not allowed.");
        }

        if (empty($data)) {
            throw new InvalidArgumentException("No data provided for update.");
        }

        $set = [];
        $bind = [];

        foreach ($data as $col => $val) {
            $col = $this->validateIdentifier($col);
            $set[] = $this->quote($col) . ' = ?';

            // Normalize values so PDO never receives arrays/objects
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($val)) {
                $val = $val ? 1 : 0;
            }

            $bind[] = $val;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        [$whereSql, $params] = $this->buildWhere();

        $stmt = $this->pdo->prepare($sql . $whereSql);
        $stmt->execute(array_merge($bind, $params));

        $this->reset();
        return true;
    }

    public function delete(): bool
    {
        if (empty($this->where)) {
            throw new RuntimeException("DELETE without WHERE is not allowed.");
        }

        $sql = "DELETE FROM {$this->table}";
        [$whereSql, $params] = $this->buildWhere();

        $stmt = $this->pdo->prepare($sql . $whereSql);
        $stmt->execute($params);

        $this->reset();
        return true;
    }

    public function count(string $column = '*'): int
    {
        $column = $column === '*'
            ? '*'
            : $this->quote($this->validateIdentifier($column));

        $sql = "SELECT COUNT({$column}) AS total FROM {$this->table}";
        [$whereSql, $params] = $this->buildWhere();

        $stmt = $this->pdo->prepare($sql . $whereSql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $this->reset();

        return (int) ($row->total ?? 0);
    }
}
