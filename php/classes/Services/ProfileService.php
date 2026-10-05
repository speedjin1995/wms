<?php
namespace App\Services;

/**
 * Logged-in user's own profile and password.
 */
class ProfileService extends BaseService
{
    public const LANGUAGES = ['en', 'zh', 'my', 'ne', 'ja'];
    private const MIN_PASSWORD_LENGTH = 6;

    /**
     * Current user's profile
     */
    public function getProfile(): ?array
    {
        return $this->fetchOne("SELECT name, username, IFNULL(email, '') AS email, languages FROM users WHERE id = ?", 'i', [$this->user]);
    }

    /**
     * Update name, email and language (username is not editable)
     */
    public function updateProfile(string $name, ?string $email, string $language): array
    {
        if (!in_array($language, self::LANGUAGES, true)) {
            return ['status' => 'failed', 'message' => 'Invalid language'];
        }

        if (!$this->executeWrite("UPDATE users SET name = ?, email = ?, languages = ? WHERE id = ?", 'sssi', [$name, $email, $language, $this->user])) {
            return ['status' => 'failed', 'message' => 'Failed to update profile'];
        }

        return ['status' => 'success', 'message' => 'Your profile has been updated successfully!'];
    }

    /**
     * Change password after verifying the old one (sha512 + per-user salt, same as login)
     */
    public function changePassword(string $oldPassword, string $newPassword): array
    {
        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            return ['status' => 'failed', 'message' => 'Your password must be at least 6 characters long'];
        }

        $row = $this->fetchOne("SELECT password, salt FROM users WHERE id = ?", 'i', [$this->user]);
        if (!$row) {
            return ['status' => 'failed', 'message' => 'Data retrieve failed'];
        }

        if (!hash_equals($row['password'], hash('sha512', $oldPassword . $row['salt']))) {
            return ['status' => 'failed', 'message' => 'Old password is not matched'];
        }

        $password = hash('sha512', $newPassword . $row['salt']);
        if (!$this->executeWrite("UPDATE users SET password = ? WHERE id = ?", 'si', [$password, $this->user])) {
            return ['status' => 'failed', 'message' => 'Failed to update password'];
        }

        return ['status' => 'success', 'message' => 'Update successfully'];
    }
}
