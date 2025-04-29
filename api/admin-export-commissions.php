<?php
/**
 * Admin Export Commissions API
 * 
 * Exports commissions in CSV or PDF format
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
        SELECT c.id, c.user_id, c.order_id, c.amount, c.status, c.type, c.commission_date, 
               c.created_at, c.notes,
               u.first_name, u.last_name, u.email
        FROM commissions c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add status filter
    if ($status && $status !== 'all') {
        $query .= " AND c.status = ?";
        $params[] = $status;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            u.email LIKE ? OR 
            c.id::text LIKE ? OR
            c.order_id::text LIKE ?
        )";
        
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add order
    $query .= " ORDER BY c.commission_date DESC";
    
    // Execute query
    $commissions_result = db_query($query, $params);
    $commissions = db_fetch_all($commissions_result);
    
    // Generate filename
    $date = date('Y-m-d');
    $filename = "commissions-export-{$date}";
    
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
            'Commission ID',
            'Affiliate Name',
            'Email',
            'Type',
            'Order ID',
            'Amount',
            'Status',
            'Date',
            'Created At',
            'Notes'
        ]);
        
        // Add data rows
        foreach ($commissions as $commission) {
            fputcsv($output, [
                $commission['id'],
                $commission['first_name'] . ' ' . $commission['last_name'],
                $commission['email'],
                ucfirst($commission['type']),
                $commission['order_id'],
                $commission['amount'],
                ucfirst($commission['status']),
                date('Y-m-d', strtotime($commission['commission_date'])),
                date('Y-m-d H:i:s', strtotime($commission['created_at'])),
                $commission['notes'] ?? ''
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
            <title>Commissions Export</title>
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
            <h1>Commissions Export - ' . date('Y-m-d') . '</h1>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Affiliate</th>
                    <th>Type</th>
                    <th>Order ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>';
        
        foreach ($commissions as $commission) {
            $commissionDate = date('Y-m-d', strtotime($commission['commission_date']));
            $statusUpper = ucfirst($commission['status']);
            $typeUpper = ucfirst($commission['type']);
            
            echo '<tr>
                <td>' . $commission['id'] . '</td>
                <td>' . $commission['first_name'] . ' ' . $commission['last_name'] . '<br><small>' . $commission['email'] . '</small></td>
                <td>' . $typeUpper . '</td>
                <td>' . $commission['order_id'] . '</td>
                <td>$' . number_format($commission['amount'], 2) . '</td>
                <td>' . $statusUpper . '</td>
                <td>' . $commissionDate . '</td>
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