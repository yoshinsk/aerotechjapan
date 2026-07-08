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

        $idleTimeout = (int)config_value('security.session_idle_timeout', 1800);
        $lastSeen = (int)($_SESSION['admin_last_seen_at'] ?? 0);
        if ($idleTimeout > 0 && $lastSeen > 0 && (time() - $lastSeen) > $idleTimeout) {
            $this->logout();
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([(int)$id]);
        $user = $stmt->fetch();
        if (!$user) {
            $this->logout();
            return null;
        }

        $_SESSION['admin_last_seen_at'] = time();
        return $user;
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_last_seen_at'] = time();
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
        }
        $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_last_seen_at']);
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
