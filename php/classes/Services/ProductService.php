<?php
namespace App\Services;

class ProductService
{
    private \mysqli $db;
    private int $company;

    public function __construct(\mysqli $db, int $company)
    {
        $this->db = $db;
        $this->company = $company;
    }

    /**
     * Get products with grades filtered by type (Local/Export)
     */
    public function getProductsWithGrades(?array $categoryIds = null, string $type = 'Local'): array
    {
        $where = "p.deleted = 0 AND p.customer = ?";
        $params = [$this->company];
        $types = 'i';

        if (!empty($categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $where .= " AND p.category IN ($placeholders)";
            foreach ($categoryIds as $catId) {
                $params[] = $catId;
                $types .= 'i';
            }
        }

        // Only get products that have grades of the specified type
        $where .= " AND EXISTS (SELECT 1 FROM product_grades pg WHERE pg.product_id = p.id AND pg.deleted = 0 AND pg.type = ?)";
        $params[] = $type;
        $types .= 's';

        $sql = "SELECT p.id, p.product_code, p.product_name, p.category, c.category_name
                FROM products p 
                LEFT JOIN categories c ON p.category = c.id
                WHERE $where 
                ORDER BY c.category_name ASC, p.product_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['grades'] = $this->getGradesForProduct((int)$row['id'], $type);
            $products[] = $row;
        }
        $stmt->close();

        return $products;
    }

    /**
     * Get grades for a specific product filtered by type
     */
    public function getGradesForProduct(int $productId, string $type = 'Local'): array
    {
        $stmt = $this->db->prepare(
            "SELECT pg.id as product_grade_id, pg.grade_id, pg.purchasing_price, pg.price, g.units as grade_name
             FROM product_grades pg 
             LEFT JOIN grades g ON pg.grade_id = g.id 
             WHERE pg.product_id = ? AND pg.deleted = 0 AND pg.type = ?
             ORDER BY g.units ASC"
        );
        $stmt->bind_param('is', $productId, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        $stmt->close();
        
        return $grades;
    }
}
