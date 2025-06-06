<?php
abstract class Model
{
    protected $conn;
    protected $table;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function findAll()
    {
        $stmt = $this->conn->query("SELECT * FROM {$this->table} ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $fields = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} ({$fields}) VALUES ({$values})");
        $stmt->execute(array_values($data));
        return $this->conn->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = implode(' = ?, ', array_keys($data)) . ' = ?';
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET {$fields} WHERE id = ?");
        return $stmt->execute($values);
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
