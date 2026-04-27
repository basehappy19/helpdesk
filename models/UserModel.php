<?php

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT id, username, display_th, phone_ext, role, solver, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => trim($username)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers($limit, $offset) {
        $stmt = $this->pdo->prepare("SELECT id, username, display_th, phone_ext, role, solver, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount() {
        return $this->pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, password, display_th, phone_ext, role, solver) VALUES (:username, :password, :display_th, :phone_ext, :role, :solver)");
        $stmt->execute([
            'username'   => trim($data['username']),
            'password'   => $data['password'], 
            'display_th' => trim($data['display_th']),
            'phone_ext'  => trim($data['phone_ext']),
            'role'       => $data['role'] ?? 'MEMBER',
            'solver'     => (int)$data['solver']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE users SET display_th = :display_th, phone_ext = :phone_ext, role = :role, solver = :solver";
        $params = [
            'display_th' => trim($data['display_th']),
            'phone_ext'  => trim($data['phone_ext']),
            'role'       => $data['role'],
            'solver'     => (int)$data['solver'],
            'id'         => (int)$id
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params['password'] = $data['password'];
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }
}