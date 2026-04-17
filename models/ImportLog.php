<?php
class ImportLog {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO import_logs (filename, imported_by, import_date, total_rows, inserted, updated_rows, skipped, warnings)
             VALUES (:fn, :uid, :dt, :total, :ins, :upd, :skip, :warn)"
        );
        $stmt->execute([
            'fn'    => $data['filename'],
            'uid'   => $data['imported_by'] ?? null,
            'dt'    => $data['import_date'] ?? date('Y-m-d'),
            'total' => $data['total_rows'] ?? 0,
            'ins'   => $data['inserted'] ?? 0,
            'upd'   => $data['updated_rows'] ?? 0,
            'skip'  => $data['skipped'] ?? 0,
            'warn'  => is_array($data['warnings'] ?? null) ? implode("\n", $data['warnings']) : ($data['warnings'] ?? null),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getRecent(int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT il.*, u.full_name FROM import_logs il LEFT JOIN users u ON il.imported_by = u.id ORDER BY il.created_at DESC LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
