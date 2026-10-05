<?php
namespace App\Controllers;

use App\Services\CompanyService;

class CompanyController extends BaseController
{
    private CompanyService $service;

    public function __construct(CompanyService $service)
    {
        $this->service = $service;
    }

    /**
     * Update session company's profile
     */
    public function update(): array
    {
        $details = [
            'reg_no' => $this->postText('regNo'),
            'name' => $this->postText('name'),
            'chinese_name' => $this->optionalText('chineseName'),
            'tin_no' => $this->optionalText('tinNo'),
            'address' => $this->postText('address1'),
            'address2' => $this->optionalText('address2'),
            'address3' => $this->optionalText('address3'),
            'address4' => $this->optionalText('address4'),
            'phone' => $this->optionalText('phone'),
            'email' => $this->optionalText('email'),
            'fax' => $this->optionalText('fax')
        ];

        $banking = [
            'banker_name' => $this->optionalText('bankerName'),
            'bank_acct_no' => $this->optionalText('bankAccountNo'),
            'bank_swift_code' => $this->optionalText('bankSwiftCode')
        ];

        $preferences = [
            'include_price' => $this->postYesNo('includePrice'),
            'include_photo' => $this->postYesNo('includePhoto'),
            'include_barcode' => $this->postYesNo('includeBarcode'),
            'include_sec_remark' => $this->postYesNo('includeSecRemark'),
            'photo_upload_mode' => trim((string)($_POST['photoUploadMode'] ?? '')) ?: 'local'
        ];

        return $this->service->update($details, $banking, $preferences);
    }

    /**
     * Upload company logo
     */
    public function uploadLogo(): array
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'failed', 'message' => 'No file uploaded or upload error'];
        }

        return $this->service->uploadLogo($_FILES['file']);
    }

    /**
     * Sanitised text, null when empty
     */
    private function optionalText(string $key): ?string
    {
        $value = $this->postText($key);

        return $value === '' ? null : $value;
    }

    private function postYesNo(string $key): string
    {
        return (($_POST[$key] ?? '') === 'Y') ? 'Y' : 'N';
    }
}
