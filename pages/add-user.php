<?php
require_once __DIR__ . '/../functions/users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $display_th = trim($_POST['display_th'] ?? '');
    $phone_ext = trim($_POST['phone_ext'] ?? '');
    $data = [
        'username'  => $username,
        'password'  => $password,
        'display_th' => $display_th,
        'phone_ext'  => $phone_ext,
    ];
    $user = AddUser($data);

    if ($user) {
        header('Location: index.php?page=users');
        exit;
    } else {
        $error = "Failed to add user.";
    }
}

?>

<form action="" method="post">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="mb-3">
        <label for="display_th" class="form-label">Display Name</label>
        <input type="text" class="form-control" id="display_th" name="display_th" required>
    </div>
    <div class="mb-3">
        <label for="phone_ext" class="form-label">Phone</label>
        <input type="tel" class="form-control" id="phone_ext" name="phone_ext" required>
    </div>
    <button type="submit" class="btn btn-primary">Add User</button>
</form>