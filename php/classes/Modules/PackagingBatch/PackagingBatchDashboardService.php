<?php
namespace App\Modules\PackagingBatch;

use App\Core\BaseService;

/**
 * Packaging dashboard tab: packed boxes / weight by product, then grade + packaging size with the individual boxes.
 * SADMIN sees all companies.
 */
class PackagingBatchDashboardService extends BaseService
{
    /**
     * Filters: fromDate, toDate (d/m/Y, on packaging_date), location, productionLine
     */
    public function getSummary(array $filters): array
    {
        $where = "pb.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'pb.company');

        foreach (['fromDate' => '>=', 'toDate' => '<='] as $key => $operator) {
            $date = \DateTime::createFromFormat('d/m/Y', (string)($filters[$key] ?? ''));
            if ($date) {
                $where .= " AND DATE(pb.packaging_date) $operator ?";
                $params[] = $date->format('Y-m-d');
                $types .= 's';
            }
        }
        foreach (['location' => 'pb.location', 'productionLine' => 'pb.production_line'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $where .= " AND $column = ?";
                $params[] = $filters[$key];
                $types .= 's';
            }
        }

        // One row per box; a box on a loading order carries its customer
        $rows = $this->fetchAll(
            "SELECT pb.id AS batch_id, pb.batch_no, pb.packaging_date, pbi.grade, pbi.packaging_size, pbi.units_per_box, pbi.weight,
                    p.product_name, gr.units AS grade_name, pk.packaging_name, c.customer_name
             FROM packaging_batches pb
             INNER JOIN packaging_batch_items pbi ON pbi.packaging_batch_id = pb.id AND pbi.deleted = 0
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades gr ON pbi.grade = gr.id
             LEFT JOIN packaging pk ON pbi.packaging_size = pk.id
             LEFT JOIN loading_order_items loi ON loi.packaging_batch_item_id = pbi.id AND loi.deleted = 0
             LEFT JOIN customers c ON loi.customer_id = c.id
             WHERE $where",
            $types,
            $params
        );

        $batchIds = [];
        $totalWeight = 0;
        $productMap = [];

        foreach ($rows as $row) {
            $batchIds[$row['batch_id']] = true;
            $weight = floatval($row['weight']);
            $totalWeight += $weight;

            $productName = $row['product_name'] ?: 'Unknown';
            $gradeName = $row['grade_name'] ?: $row['grade'] ?: 'Unknown';
            $packagingName = $row['packaging_name'] ?: $row['packaging_size'] ?: 'Unknown';

            if (!isset($productMap[$productName])) {
                $productMap[$productName] = ['total_weight' => 0, 'total_boxes' => 0, 'grades' => []];
            }
            $productMap[$productName]['total_weight'] += $weight;
            $productMap[$productName]['total_boxes']++;

            $gradeKey = $gradeName . '||' . $packagingName;
            if (!isset($productMap[$productName]['grades'][$gradeKey])) {
                $productMap[$productName]['grades'][$gradeKey] = [
                    'grade_name' => $gradeName, 'packaging_name' => $packagingName, 'total_weight' => 0, 'total_boxes' => 0, 'items' => []
                ];
            }
            $grade = &$productMap[$productName]['grades'][$gradeKey];
            $grade['total_weight'] += $weight;
            $grade['total_boxes']++;
            $grade['items'][] = [
                'batch_no' => $row['batch_no'],
                'date_raw' => $row['packaging_date'],
                'date' => $row['packaging_date'] ? date('d/m/Y', strtotime($row['packaging_date'])) : '',
                'units_per_box' => $row['units_per_box'],
                'weight' => round($weight, 2),
                'customer_name' => $row['customer_name'] ?: '—'
            ];
            unset($grade);
        }

        // Products and grades heaviest first, boxes by date
        $productBreakdown = [];
        foreach ($productMap as $productName => $data) {
            $grades = array_values($data['grades']);
            usort($grades, function ($a, $b) {
                return $b['total_weight'] <=> $a['total_weight'];
            });
            foreach ($grades as &$grade) {
                usort($grade['items'], function ($a, $b) {
                    return strcmp((string)$a['date_raw'], (string)$b['date_raw']);
                });
                $grade['total_weight'] = round($grade['total_weight'], 2);
            }
            unset($grade);

            $productBreakdown[] = [
                'product_name' => $productName,
                'total_weight' => round($data['total_weight'], 2),
                'total_boxes' => $data['total_boxes'],
                'grades' => $grades
            ];
        }
        usort($productBreakdown, function ($a, $b) {
            return $b['total_weight'] <=> $a['total_weight'];
        });

        return [
            'summary' => [
                'batch_count' => count($batchIds),
                'total_boxes' => count($rows),
                'total_weight' => round($totalWeight, 2)
            ],
            'productBreakdown' => $productBreakdown
        ];
    }
}
