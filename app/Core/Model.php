<?php
/**
 * Base model providing common CRUD backed by the Database wrapper.
 *
 * Child models declare the table name, fillable columns and (optionally) a set
 * of searchable columns used by the global search and list filters.
 */

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    /** @var array<int, string> */
    protected array $fillable = [];
    /** @var array<int, string> */
    protected array $searchable = [];
    protected bool $timestamps = true;

    protected function db(): Database
    {
        return Database::instance();
    }

    public function find(int $id): ?array
    {
        return $this->db()->first(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>|null
     */
    public function findBy(array $conditions): ?array
    {
        [$where, $params] = $this->buildWhere($conditions);
        return $this->db()->first("SELECT * FROM `{$this->table}` {$where} LIMIT 1", $params);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db()->all("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }
        return $this->db()->insert($this->table, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): int
    {
        $data = $this->filterFillable($data);
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return $this->db()->update($this->table, $data, [$this->primaryKey => $id]);
    }

    public function delete(int $id): int
    {
        return $this->db()->delete($this->table, [$this->primaryKey => $id]);
    }

    public function count(string $where = '1', array $params = []): int
    {
        return (int) $this->db()->scalar("SELECT COUNT(*) FROM `{$this->table}` WHERE {$where}", $params);
    }

    /**
     * Paginated query with optional search and extra WHERE conditions.
     *
     * @param array<string, mixed> $conditions column => value (exact match)
     * @return array{data: array<int, array<string,mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, string $search = '', array $conditions = [], string $orderBy = 'id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $clauses = ['1'];
        $params = [];

        foreach ($conditions as $col => $val) {
            $clauses[] = "`{$col}` = ?";
            $params[] = $val;
        }

        if ($search !== '' && $this->searchable !== []) {
            $searchClauses = [];
            foreach ($this->searchable as $col) {
                $searchClauses[] = "`{$col}` LIKE ?";
                $params[] = '%' . $search . '%';
            }
            $clauses[] = '(' . implode(' OR ', $searchClauses) . ')';
        }

        $where = implode(' AND ', $clauses);

        $total = (int) $this->db()->scalar("SELECT COUNT(*) FROM `{$this->table}` WHERE {$where}", $params);

        $params[] = $perPage;
        $params[] = $offset;
        $rows = $this->db()->all(
            "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            $params
        );

        return [
            'data'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array{0:string, 1:array<int, mixed>}
     */
    protected function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $parts[] = "`{$col}` = ?";
            $params[] = $val;
        }
        return ['WHERE ' . implode(' AND ', $parts), $params];
    }
}
