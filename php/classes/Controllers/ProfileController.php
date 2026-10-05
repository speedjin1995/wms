<?php
namespace App\Controllers;

use App\Services\ProfileService;

class ProfileController extends BaseController
{
    private ProfileService $service;

    public function __construct(ProfileService $service)
    {
        $this->service = $service;
    }

    /**
     * Update own name, email and language
     */
    public function updateProfile(): array
    {
        $name = $this->postText('userName');
        $email = filter_var(trim((string)($_POST['userEmailAddress'] ?? '')), FILTER_SANITIZE_EMAIL);
        $language = trim((string)($_POST['language'] ?? ''));

        if ($name === '' || $language === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all fields'];
        }

        $result = $this->service->updateProfile($name, ($email === '' || $email === false) ? null : $email, $language);

        if ($result['status'] === 'success') {
            $_SESSION['language'] = $language;
        }

        return $result;
    }

    /**
     * Change own password. Passwords are hashed raw (not sanitised), matching login and reset.
     */
    public function changePassword(): array
    {
        $oldPassword = (string)($_POST['oldPassword'] ?? '');
        $newPassword = (string)($_POST['newPassword'] ?? '');
        $confirmPassword = (string)($_POST['confirmPassword'] ?? '');

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['status' => 'failed', 'message' => 'Confirm password does not match new password'];
        }

        return $this->service->changePassword($oldPassword, $newPassword);
    }
}
