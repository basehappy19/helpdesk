<?php

function getUser($userId)
{
    global $pdo;
    $sql = "SELECT id, username, display_th FROM users WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => (int)$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return [
            'id'        => (int)$row['id'],
            'username'  => $row['username'],
            'display_th' => $row['display_th'],
        ];
    } else {
        return null;
    }
}

function Auth($data)
{
    global $pdo;
    $sql = "SELECT id, username, password FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'username' => $data['username'],
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($data['password'], $row['password'])) {
        return [
            'id' => (int)$row['id'],
        ];
    } else {
        return null;
    }
}

function AddUser($data)
{
    global $pdo;
    $sql = "INSERT INTO users (username, password, display_th, phone_ext) VALUES (:username, :password, :display_th, :phone_ext)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'username'  => $data['username'],
        'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
        'display_th'     => $data['display_th'],
        'phone_ext'      => $data['phone_ext'],
    ]);
    return $pdo->lastInsertId();
}
