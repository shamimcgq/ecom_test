<?php

require_once __DIR__ . '/db.php';

function getUsers(): array
{
    $pdo = db();
    return $pdo->query('SELECT id, name, email, role FROM users ORDER BY id DESC')->fetchAll();
}

function addUser(array $user): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO users (name, email, role) VALUES (:name, :email, :role)');
    return $stmt->execute([
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => (string) ($user['role'] ?? 'customer'),
    ]);
}

function deleteUserById(int $id): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    return $stmt->execute(['id' => $id]);
}
