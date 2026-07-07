<?php
/**
 * httpdocs/app/Auth.php
 * 管理画面ログイン、セッション認証、パスワード検証を担当します。
 */

declare(strict_types=1);

final class Auth
{
    public function __construct(private PDO $pdo)
    {
    }

    public function user(): ?array
    {
        $id = $_SESSION['admin_user_id'] ?? null;
        if (!$id) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([(int)$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['admin_user_id'] = (int)$user['id'];
        session_regenerate_id(true);
        $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user_id']);
        session_regenerate_id(true);
    }

    public function requireLogin(): array
    {
        $user = $this->user();
        if (!$user) {
            redirect_to('/admin/login');
        }
        return $user;
    }
}
