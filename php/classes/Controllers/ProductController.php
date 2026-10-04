<?php
namespace App\Controllers;

use App\Services\ProductService;

class ProductController extends BaseController
{
    private const FIELDS = [
        'code' => ['column' => 'product_code', 'required' => true],
        'product' => ['column' => 'product_name', 'required' => true],
        'serial' => ['column' => 'product_sn', 'required' => false],
        'batch' => ['column' => 'batch_no', 'required' => false],
        'part' => ['column' => 'parts_no', 'required' => false],
        'uom' => ['column' => 'uom', 'required' => false],
        'remark' => ['column' => 'remark', 'required' => false],
        'pricingType' => ['column' => 'pricing_type', 'required' => false],
        'pricingCurrency' => ['column' => 'pricing_currency', 'required' => false],
        'price' => ['column' => 'price', 'required' => false],
        'purchasingPricingType' => ['column' => 'purchasing_pricing_type', 'required' => false],
        'purchasingPricingCurrency' => ['column' => 'purchasing_pricing_currency', 'required' => false],
        'purchasingPrice' => ['column' => 'purchasing_price', 'required' => false],
        'weight' => ['column' => 'weight', 'required' => false],
        'okWeight' => ['column' => 'ok_weight', 'required' => false],
        'okWeightUnit' => ['column' => 'ok_weight_unit', 'required' => false],
        'loWeight' => ['column' => 'lo_weight', 'required' => false],
        'loWeightUnit' => ['column' => 'lo_weight_unit', 'required' => false],
        'hiWeight' => ['column' => 'hi_weight', 'required' => false],
        'hiWeightUnit' => ['column' => 'hi_weight_unit', 'required' => false],
        'productCategory' => ['column' => 'category', 'required' => false],
        'productPackaging' => ['column' => 'packaging', 'required' => false],
        'productColour' => ['column' => 'colour', 'required' => false]
    ];

    private ProductService $productService;

    public function __construct(ProductService $service)
    {
        $this->productService = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams();

        try {
            $result = $this->productService->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('ProductController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get product with customer, supplier and grade pricing rows
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $product = $this->productService->getById($id);
        } catch (\Exception $e) {
            error_log('ProductController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$product) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $product];
    }

    /**
     * Create or update product with grade pricing rows and image
     */
    public function save(): array
    {
        $id = $this->postId();
        $company = $this->postId('company');

        $data = $this->collectFields(self::FIELDS);
        if ($data === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($data['uom'] === '-') {
            $data['uom'] = null;
        }
        $data['range_set'] = (int)($_POST['rangeSet'] ?? 0);
        $data['state'] = !empty($_POST['state']) ? json_encode($_POST['state']) : null;

        $grades = null;
        if (isset($_POST['gradeNo']) && is_array($_POST['gradeNo'])) {
            $grades = [];
            foreach (array_keys($_POST['gradeNo']) as $key) {
                $grades[] = [
                    'id' => trim((string)($_POST['productGradeId'][$key] ?? '')),
                    'grade' => $_POST['grades'][$key] ?? null,
                    'type' => $this->valueOrDefault($_POST['gradeType'][$key] ?? '', 'Local'),
                    'pricingType' => $_POST['gradePricingType'][$key] ?? null,
                    'pricingCurrency' => $this->valueOrDefault($_POST['gradePricingCurrency'][$key] ?? '', null),
                    'price' => $_POST['gradePrice'][$key] ?? null,
                    'purchasingPricingType' => $_POST['gradePurchasingPricingType'][$key] ?? null,
                    'purchasingPricingCurrency' => $this->valueOrDefault($_POST['gradePurchasingPricingCurrency'][$key] ?? '', null),
                    'purchasingPrice' => $_POST['gradePurchasingPrice'][$key] ?? null
                ];
            }
        }

        return $this->productService->saveProduct($id, $data, $company, $grades, $_FILES['productImage'] ?? null);
    }

    /**
     * Resolve price for a product (wholesales / industrial)
     */
    public function getPrice(): array
    {
        $id = $this->postId();
        $status = trim($_POST['status'] ?? '');

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }
        if ($status === '') {
            return ['status' => 'failed', 'message' => 'Missing status'];
        }

        $partnerId = $this->valueOrDefault($_POST['customerID'] ?? '', '');
        $grade = $this->valueOrDefault($_POST['grade'] ?? '', '');
        $currency = $this->valueOrDefault($_POST['currency'] ?? '', '');

        try {
            $price = $this->productService->getPrice($id, $status, $partnerId, $grade, $currency);
        } catch (\Exception $e) {
            error_log('ProductController::getPrice - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if ($price === null) {
            return ['status' => 'failed', 'message' => 'Cannot find product'];
        }

        return ['status' => 'success', 'message' => $price];
    }

    /**
     * Save customer and supplier pricing rows for a product
     */
    public function saveCustomerSupplier(): array
    {
        $productId = $this->postId('product_id');

        if (!$productId) {
            return ['status' => 'failed', 'message' => 'Missing product ID.'];
        }

        $customerRows = [];
        if (isset($_POST['no']) && is_array($_POST['no'])) {
            foreach (array_keys($_POST['no']) as $key) {
                $customerRows[] = [
                    'id' => trim((string)($_POST['customerProductId'][$key] ?? '')),
                    'partner' => $_POST['customers'][$key] ?? null,
                    'type' => $this->valueOrDefault($_POST['customerType'][$key] ?? '', 'Local'),
                    'grade' => $this->valueOrDefault($_POST['customerGrade'][$key] ?? '', null),
                    'pricingType' => $_POST['customerPricingType'][$key] ?? null,
                    'currency' => $this->valueOrDefault($_POST['customerCurrency'][$key] ?? '', null),
                    'price' => $_POST['customerPrice'][$key] ?? null
                ];
            }
        }

        $supplierRows = [];
        if (isset($_POST['supplierNo']) && is_array($_POST['supplierNo'])) {
            foreach (array_keys($_POST['supplierNo']) as $key) {
                $supplierRows[] = [
                    'id' => trim((string)($_POST['supplierProductId'][$key] ?? '')),
                    'partner' => $_POST['suppliers'][$key] ?? null,
                    'type' => $this->valueOrDefault($_POST['supplierType'][$key] ?? '', 'Local'),
                    'grade' => $this->valueOrDefault($_POST['supplierGrade'][$key] ?? '', null),
                    'pricingType' => $_POST['supplierPricingType'][$key] ?? null,
                    'currency' => $this->valueOrDefault($_POST['supplierCurrency'][$key] ?? '', null),
                    'price' => $_POST['supplierPrice'][$key] ?? null
                ];
            }
        }

        return $this->productService->saveCustomerSupplier($productId, $customerRows, $supplierRows);
    }

    /**
     * Soft delete single or multiple products
     */
    public function delete(): array
    {
        return $this->productService->delete($this->postIds());
    }

    /**
     * Reactivate soft deleted product
     */
    public function reactivate(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->productService->reactivate($id);
    }

    /**
     * Bulk upload products from JSON body
     */
    public function upload(): array
    {
        $rows = $this->jsonBody();

        if ($rows === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->productService->upload($rows);
    }

    /**
     * Get products with grades filtered by type
     */
    public function getProductsByType(?array $categoryIds = null): array
    {
        $type = $_POST['type'] ?? 'Local';

        try {
            $products = $this->productService->getProductsWithGrades($categoryIds, $type);
            return [
                'status' => 'success',
                'data' => $products
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Empty POST values fall back to the default
     */
    private function valueOrDefault($value, ?string $default): ?string
    {
        return empty($value) ? $default : (string)$value;
    }
}
