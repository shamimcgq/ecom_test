<?php

require_once __DIR__ . '/db.php';

function getAdminUsers(): array
{
    $pdo = db();
    $rows = $pdo->query('SELECT username, password_hash, role, name FROM admin_users ORDER BY id')->fetchAll();

    if ($rows) {
        return $rows;
    }

    $seed = [
        [
            'username' => 'superadmin',
            'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'name' => 'Super Admin',
        ],
        [
            'username' => 'manager',
            'password_hash' => password_hash('Manager@123', PASSWORD_DEFAULT),
            'role' => 'manager',
            'name' => 'Store Manager',
        ],
        [
            'username' => 'ops',
            'password_hash' => password_hash('Ops@123', PASSWORD_DEFAULT),
            'role' => 'staff',
            'name' => 'Ops Staff',
        ],
    ];

    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, role, name) VALUES (:username, :password_hash, :role, :name)');
    foreach ($seed as $user) {
        $stmt->execute($user);
    }

    return $seed;
}

function authenticateAdmin(string $username, string $password): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT username, password_hash, role, name FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return $user;
    }

    return null;
}

function roleTabs(string $role): array
{
    $map = [
        'admin' => ['products', 'orders', 'pnl', 'banner', 'users', 'shipping'],
        'manager' => ['products', 'orders', 'shipping'],
        'staff' => ['orders'],
    ];

    return $map[$role] ?? ['orders'];
}
