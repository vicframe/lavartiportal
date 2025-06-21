<?php
/**
 * Admin Export Orders API
 * 
 * Exports orders in CSV or PDF format
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Require admin login
if (!is_logged_in()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = get_current_logged_user();

if (!isset($user['is_admin']) || !$user['is_admin']) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

try {
    // Get export format
    $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
    
    if (!in_array($format, ['csv', 'pdf'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid export format']);
        exit;
    }
    
    // Get filter parameters
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    // Build query
    $query = "
        SELECT o.id, o.user_id, o.product_id, o.total_amount, o.status, o.order_date, o.created_at,
               p.name as product_name, p.price as product_price, p.tier_level,
               u.first_name, u.last_name, u.email
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add status filter
    if ($status && $status !== 'all') {
        $query .= " AND o.status = ?";
        $params[] = $status;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            u.email LIKE ? OR 
            p.name LIKE ? OR 
            o.id::text LIKE ?
        )";
        
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add order
    $query .= " ORDER BY o.order_date DESC";
    
    // Execute query
    $orders_result = db_query($query, $params);
    $orders = db_fetch_all($orders_result);
    
    // Generate filename
    $date = date('Y-m-d');
    $filename = "orders-export-{$date}";
    
    if ($status && $status !== 'all') {
        $filename .= "-{$status}";
    }
    
    // Export based on format
    if ($format === 'csv') {
        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'Order ID',
            'Customer Name',
            'Email',
            'Product',
            'Tier',
            'Amount',
            'Status',
            'Order Date',
            'Created At',
            'Notes'
        ]);
        
        // Add data rows
        foreach ($orders as $order) {
            $tierName = '';
            switch ($order['tier_level']) {
                case 1:
                    $tierName = 'Basic';
                    break;
                case 2:
                    $tierName = 'Premium';
                    break;
                case 3:
                    $tierName = 'Elite';
                    break;
                default:
                    $tierName = 'Unknown';
            }
            
            fputcsv($output, [
                $order['id'],
                $order['first_name'] . ' ' . $order['last_name'],
                $order['email'],
                $order['product_name'],
                $tierName,
                $order['total_amount'],
                ucfirst($order['status']),
                date('Y-m-d', strtotime($order['order_date'])),
                date('Y-m-d H:i:s', strtotime($order['created_at'])),
                $order['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    } else {
        // PDF export - Simple version
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        // Simple PDF generation using HTML and browser rendering
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Orders Export</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { font-size: 18px; margin-bottom: 20px; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #f2f2f2; }
                tr:nth-child(even) { background-color: #f9f9f9; }
            </style>
        </head>
        <body>
            <h1>Orders Export - ' . date('Y-m-d') . '</h1>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Order Date</th>
                </tr>';
        
        foreach ($orders as $order) {
            $orderDate = date('Y-m-d', strtotime($order['order_date']));
            $statusUpper = ucfirst($order['status']);
            
            echo '<tr>
                <td>' . $order['id'] . '</td>
                <td>' . $order['first_name'] . ' ' . $order['last_name'] . '<br><small>' . $order['email'] . '</small></td>
                <td>' . $order['product_name'] . '</td>
                <td>$' . number_format($order['total_amount'], 2) . '</td>
                <td>' . $statusUpper . '</td>
                <td>' . $orderDate . '</td>
            </tr>';
        }
        
        echo '</table>
        </body>
        </html>';
        
        // Note: In a production environment, you would use a proper PDF library like FPDF or TCPDF
        exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}