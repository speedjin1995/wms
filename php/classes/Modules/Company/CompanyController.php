<?php
namespace App\Modules\Company;

use App\Core\BaseController;

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
            'chinese_name' => $this->optionalText('chineseName', true),
            'tin_no' => $this->optionalText('tinNo', true),
            'address' => $this->postText('address1'),
            'address2' => $this->optionalText('address2', true),
            'address3' => $this->optionalText('address3', true),
            'address4' => $this->optionalText('address4', true),
            'phone' => $this->optionalText('phone', true),
            'email' => $this->optionalText('email', true),
            'fax' => $this->optionalText('fax', true)
        ];

        $banking = [
            'banker_name' => $this->optionalText('bankerName', true),
            'bank_acct_no' => $this->optionalText('bankAccountNo', true),
            'bank_swift_code' => $this->optionalText('bankSwiftCode', true)
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

    private function postYesNo(string $key): string
    {
        return (($_POST[$key] ?? '') === 'Y') ? 'Y' : 'N';
    }
}
