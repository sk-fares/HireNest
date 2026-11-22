
<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid request. Please go back and try again.');
    }

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!$userId || !in_array($action, ['activate', 'deactivate'], true)) {
        $message = 'Invalid user action.';
    } elseif ($userId === (int) $_SESSION['user_id']) {
        $message = 'You cannot deactivate your own account.';
    } else {
        // Only applicant and company accounts can be managed here.
        $activeValue = $action === 'activate' ? 1 : 0;

        $stmt = $pdo->prepare(
            "UPDATE users
             SET is_active = ?
             WHERE id = ? AND role IN ('applicant', 'company')"
        );
        $stmt->execute([$activeValue, $userId]);

        $message = $stmt->rowCount() > 0
            ? 'User account updated successfully.'
            : 'No eligible user was changed.';
    }
}

$stmt = $pdo->query(
    "SELECT id, name, email, role, is_active, created_at
     FROM users
     ORDER BY created_at DESC"
);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Manage Users</h1>
    <p>View registered accounts and manage applicant or company access.</p>

    <?php if ($message): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>All Registered Users</h2>

        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int) $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                <td>
                                    <?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                </td>
                                <td>
                                    <?php if (in_array($user['role'], ['applicant', 'company'], true)): ?>
                                        <form method="POST" action="users.php">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="user_id"
                                                   value="<?= (int) $user['id'] ?>">

                                            <?php if ((int) $user['is_active'] === 1): ?>
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="btn btn-danger">
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn">
                                                    Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span>Protected admin account</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p><a href="dashboard.php">Back to Admin Dashboard</a></p>
</div>

<?php require_once '../includes/footer.php'; ?>
