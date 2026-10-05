<?php
namespace App\Services;

require_once __DIR__ . '/../../uploadFileHelper.php';

/**
 * Session company's own profile. Only SADMIN may change company details and feature toggles;
 * ADMIN may change banking details and photo upload mode.
 */
class CompanyService extends BaseService
{
    public const PHOTO_UPLOAD_MODES = ['local', 'google_drive', 'one_drive'];

    /**
     * Session company's profile
     */
    public function getCompany(): ?array
    {
        return $this->fetchOne("SELECT * FROM companies WHERE id = ?", 'i', [$this->company]);
    }

    /**
     * Update session company's profile; columns allowed depend on role
     */
    public function update(array $details, array $banking, array $preferences): array
    {
        if (!in_array($preferences['photo_upload_mode'], self::PHOTO_UPLOAD_MODES, true)) {
            return ['status' => 'failed', 'message' => 'Invalid photo upload mode'];
        }

        $data = $banking + ['photo_upload_mode' => $preferences['photo_upload_mode']];

        if ($this->isSuperAdmin()) {
            if ($details['reg_no'] === '' || $details['name'] === '' || $details['address'] === '') {
                return ['status' => 'failed', 'message' => 'Please fill in all fields'];
            }

            $data = $details + $data + $preferences;
        }

        if (!$this->updateRow('companies', $data, "id = ?", 'i', [$this->company])) {
            return ['status' => 'failed', 'message' => 'Failed to update company profile'];
        }

        return ['status' => 'success', 'message' => 'Your company profile is updated successfully!'];
    }

    /**
     * Replace the session company's logo
     */
    public function uploadLogo(array $file): array
    {
        $result = uploadFile($file, 'logo', (string)$this->company, $this->db);

        if ($result['status'] === 'failed') {
            return ['status' => 'failed', 'message' => $result['message']];
        }

        $old = $this->fetchOne("SELECT company_logo FROM companies WHERE id = ?", 'i', [$this->company]);
        if (!empty($old['company_logo'])) {
            deleteOldFile($old['company_logo'], $this->db);
        }

        if (!$this->executeWrite("UPDATE companies SET company_logo = ? WHERE id = ?", 'ii', [(int)$result['fid'], $this->company])) {
            return ['status' => 'failed', 'message' => 'Failed to update company logo'];
        }

        return ['status' => 'success', 'message' => 'File uploaded successfully!'];
    }
}
