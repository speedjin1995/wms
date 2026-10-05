<?php
namespace App\Controllers;

/**
 * Shared request helpers for all controllers.
 */
abstract class BaseController
{
    /**
     * DataTables server-side request parameters
     */
    protected function dataTableParams(string $defaultColumn = 'id', string $defaultDir = 'asc'): array
    {
        $columnIndex = $_POST['order'][0]['column'] ?? 0;

        return [
            'draw' => (int)($_POST['draw'] ?? 0),
            'start' => (int)($_POST['start'] ?? 0),
            'length' => (int)($_POST['length'] ?? 10),
            'orderColumn' => (string)($_POST['columns'][$columnIndex]['data'] ?? $defaultColumn),
            'orderDir' => (string)($_POST['order'][0]['dir'] ?? $defaultDir),
            'search' => trim($_POST['search']['value'] ?? '')
        ];
    }

    /**
     * DataTables server-side response from a service list result
     */
    protected function dataTableResponse(int $draw, array $result): array
    {
        return [
            'draw' => $draw,
            'iTotalRecords' => $result['totalRecords'],
            'iTotalDisplayRecords' => $result['totalFiltered'] ?? $result['totalRecords'],
            'aaData' => $result['data']
        ];
    }

    protected function emptyListResult(): array
    {
        return ['totalRecords' => 0, 'totalFiltered' => 0, 'data' => []];
    }

    protected function postId(string $key = 'id'): int
    {
        return (int)($_POST[$key] ?? 0);
    }

    /**
     * IDs posted as an array or a single value
     */
    protected function postIds(string $key = 'ids'): array
    {
        $ids = $_POST[$key] ?? [];

        return is_array($ids) ? $ids : [$ids];
    }

    /**
     * Decoded JSON request body, or null when empty / invalid
     */
    protected function jsonBody(): ?array
    {
        $rows = json_decode(file_get_contents('php://input'), true);

        return (!empty($rows) && is_array($rows)) ? $rows : null;
    }

    /**
     * Trimmed POST text with tags stripped and quotes encoded (same output as the former FILTER_SANITIZE_STRING).
     * Use for values echoed unescaped into HTML / JS strings.
     */
    protected function postText(string $key): string
    {
        $value = strip_tags(trim((string)($_POST[$key] ?? '')));

        return str_replace(['"', "'"], ['&#34;', '&#39;'], $value);
    }

    /**
     * Read form fields (POST name => ['column' => db column, 'required' => bool]) as column => value.
     * Empty values become null; returns null when a required field is empty.
     */
    protected function collectFields(array $fields): ?array
    {
        $data = [];
        foreach ($fields as $name => $field) {
            $value = trim((string)($_POST[$name] ?? ''));

            if ($value === '') {
                if (!empty($field['required'])) {
                    return null;
                }
                $value = null;
            }

            $data[$field['column']] = $value;
        }

        return $data;
    }
}
