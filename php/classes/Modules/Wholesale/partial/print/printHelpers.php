<?php
// Helpers used by the weighing print and report templates (rendered by WholesaleReportService / WholesaleExportService)

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    $earliest_time = null;
    $latest_time = null;

    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            $product = $detail['product'] ?? 'Unknown';
            $grade = $detail['grade'] ?? 'Unknown';
            $key = $product . ' - ' . $grade;

            if(!isset($arranged[$key])) {
                $arranged[$key] = [];
            }
            $arranged[$key][] = $detail;

            if(isset($detail['time'])) {
                if($earliest_time == null || $detail['time'] < $earliest_time) {
                    $earliest_time = $detail['time'];
                }
                if($latest_time == null || $detail['time'] > $latest_time) {
                    $latest_time = $detail['time'];
                }
            }
        }
    }

    return ['arranged' => $arranged, 'earliest_time' => $earliest_time, 'latest_time' => $latest_time];
}

// Weight rows grouped as [product name][grade][] (rows without a product name are skipped)
function arrangeByProductGrade($weighingDetails) {
    $arranged = [];
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            if(empty($detail['product_name'])) continue;
            $product = $detail['product_name'];
            $grade = $detail['grade'] ?? 'Unknown';
            $arranged[$product][$grade][] = $detail;
        }
    }
    return $arranged;
}
