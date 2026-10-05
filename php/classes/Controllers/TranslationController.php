<?php
namespace App\Controllers;

use App\Services\TranslationService;

class TranslationController extends BaseController
{
    private TranslationService $service;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams();

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('TranslationController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single record by ID
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log('TranslationController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update record
     */
    public function save(): array
    {
        $id = $this->postId('keyId');

        $data = [
            'message_key_code' => $this->postText('keyCode'),
            'en' => $this->postText('englishDecs'),
            'zh' => $this->postText('chineseDecs'),
            'my' => $this->postText('malayDecs'),
            'ne' => $this->postText('tamilDecs'),
            'ja' => $this->postText('japaneseDecs'),
            'company' => (int)($_POST['company'] ?? 0)
        ];

        if ($data['message_key_code'] === '' || $data['en'] === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($id) {
            return $this->service->update($id, $data);
        }

        return $this->service->create($data);
    }

    /**
     * Delete single record
     */
    public function delete(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->delete($id);
    }

    /**
     * Trimmed POST text with tags stripped and quotes encoded (same output as the former FILTER_SANITIZE_STRING).
     * Translations are echoed unescaped into HTML and JS strings, so quotes must stay encoded.
     */
    private function postText(string $key): string
    {
        $value = strip_tags(trim((string)($_POST[$key] ?? '')));

        return str_replace(['"', "'"], ['&#34;', '&#39;'], $value);
    }
}
