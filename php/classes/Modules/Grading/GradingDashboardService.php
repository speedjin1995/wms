<?php
namespace App\Modules\Grading;

use App\Core\BaseService;

/**
 * Grading dashboard tab: graded weight by product / grade (rejects excluded) next to the received weight by product / grade.
 * SADMIN sees all companies.
 */
class GradingDashboardService extends BaseService
{
    /**
     * Filters: fromDate, toDate (d/m/Y), location (grading side only, as before)
     */
    public function getSummary(array $filters): array
    {
        $dates = [];
        foreach (['fromDate' => '>=', 'toDate' => '<='] as $key => $operator) {
            $date = \DateTime::createFromFormat('d/m/Y', (string)($filters[$key] ?? ''));
            if ($date) {
                $dates[$operator] = $date->format('Y-m-d');
            }
        }

        // Graded weight (grading_items, rejects excluded)
        $where = "g.deleted = 0 AND gi.to_grade != ?";
        $params = [GradingService::REJECT_GRADE];
        $types = 's';
        $this->applyCompanyScope($where, $params, $types, 'g.company');
        foreach ($dates as $operator => $date) {
            $where .= " AND DATE(g.start_date) $operator ?";
            $params[] = $date;
            $types .= 's';
        }
        if (($filters['location'] ?? '') !== '') {
            $where .= " AND g.location = ?";
            $params[] = $filters['location'];
            $types .= 's';
        }

        $gradingRows = $this->fetchAll(
            "SELECT gi.nett_weight, p.product_name, gr.units AS grade_name
             FROM grading g
             INNER JOIN grading_items gi ON gi.grading_id = g.id AND gi.deleted = 0
             LEFT JOIN products p ON gi.product_id = p.id
             LEFT JOIN grades gr ON gi.to_grade = gr.id
             WHERE $where",
            $types,
            $params
        );

        $totalNet = 0;
        $gradingMap = [];
        foreach ($gradingRows as $row) {
            $net = floatval($row['nett_weight']);
            $totalNet += $net;
            $this->addWeight($gradingMap, $row['product_name'] ?: 'Unknown', $row['grade_name'] ?: 'Unknown', $net);
        }

        // Received weight (wholesales weight rows)
        $where = "w.deleted = 0 AND w.status IN ('RECEIVING','INCOMING')";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'w.company');
        foreach ($dates as $operator => $date) {
            $where .= " AND DATE(w.start_time) $operator ?";
            $params[] = $date;
            $types .= 's';
        }

        $receivingMap = [];
        $productNames = [];
        foreach ($this->fetchAll("SELECT w.weight_details FROM wholesales w WHERE $where", $types, $params) as $row) {
            foreach (json_decode((string)$row['weight_details'], true) ?: [] as $item) {
                $productId = $item['product'] ?? '';
                if ($productId == '') {
                    continue;
                }
                if (!isset($productNames[$productId])) {
                    $product = $this->fetchOne("SELECT product_name FROM products WHERE id = ?", 's', [$productId]);
                    $productNames[$productId] = $product['product_name'] ?? 'Unknown';
                }
                $this->addWeight($receivingMap, $productNames[$productId], $item['grade'] ?? 'Unknown', floatval($item['net'] ?? 0));
            }
        }

        return [
            'summary' => [
                // Not counted (always 0, as before)
                'session_count' => 0,
                'total_net' => round($totalNet, 2)
            ],
            'gradingBreakdown' => $this->breakdown($gradingMap),
            'receivingBreakdown' => $this->breakdown($receivingMap)
        ];
    }

    private function addWeight(array &$map, string $productName, string $gradeName, float $weight): void
    {
        $key = $productName . '||' . $gradeName;
        if (!isset($map[$key])) {
            $map[$key] = ['product_name' => $productName, 'grade_name' => $gradeName, 'total_weight' => 0];
        }
        $map[$key]['total_weight'] += $weight;
    }

    /**
     * Rows sorted by product name, then heaviest grade first
     */
    private function breakdown(array $map): array
    {
        $rows = array_values($map);
        usort($rows, function ($a, $b) {
            $cmp = strcmp($a['product_name'], $b['product_name']);
            return $cmp !== 0 ? $cmp : $b['total_weight'] <=> $a['total_weight'];
        });
        foreach ($rows as &$row) {
            $row['total_weight'] = round($row['total_weight'], 2);
        }
        unset($row);

        return $rows;
    }
}
