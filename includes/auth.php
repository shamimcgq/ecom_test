<?php

function getAdminUsers(): array
{
    $path = __DIR__ . '/../data/admin_users.json';
    if (!file_exists($path)) {
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
        file_put_contents($path, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $seed;
    }

    $users = json_decode((string) file_get_contents($path), true);
    return is_array($users) ? $users : [];
}

function authenticateAdmin(string $username, string $password): ?array
{
    foreach (getAdminUsers() as $user) {
        if (($user['username'] ?? '') !== $username) {
            continue;
        }

        if (password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return $user;
        }
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
