<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

require_once '../includes/helpers.php';

// Admin/Manager can export all leads; Sales Rep only their own
if (hasAnyRole(['admin','manager'])) {
    $where  = "1=1";
    $params = [];
} else {
    $where  = "assigned_to = ?";
    $params = [$_SESSION['user_id']];
}

$search    = trim($_GET['search']    ?? '');
$fStatus   = $_GET['status']         ?? '';
$fPriority = $_GET['priority']       ?? '';
$fSource   = $_GET['source']         ?? '';
$fDateFrom = $_GET['date_from']      ?? '';
$fDateTo   = $_GET['date_to']        ?? '';

if (!empty($search)) {
    $where   .= " AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if (!empty($fStatus))   { $where .= " AND status = ?";               $params[] = $fStatus;   }
if (!empty($fPriority)) { $where .= " AND priority = ?";             $params[] = $fPriority; }
if (!empty($fSource))   { $where .= " AND source = ?";               $params[] = $fSource;   }
if (!empty($fDateFrom)) { $where .= " AND DATE(created_at) >= ?";    $params[] = $fDateFrom; }
if (!empty($fDateTo))   { $where .= " AND DATE(created_at) <= ?";    $params[] = $fDateTo;   }

$allowedSorts = ['created_at', 'name', 'company', 'status', 'priority'];
$orderBy  = in_array($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'created_at';
$orderDir = (strtolower($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';

$stmt = $pdo->prepare("SELECT name, company, email, phone, source, status, priority, notes, followup_date, followup_notes, created_at FROM leads WHERE $where ORDER BY $orderBy $orderDir");
$stmt->execute($params);
$leads = $stmt->fetchAll();

// ── Stream as CSV ──────────────────────────────────────────────────────────
$filename = 'leads_export_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fwrite($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, ['Name', 'Company', 'Email', 'Phone', 'Source', 'Status', 'Priority', 'Notes', 'Follow-up Date', 'Follow-up Notes', 'Created At']);

// Data rows
function preventCsvInjection($field) {
    $field = (string)$field;
    if (in_array(substr($field, 0, 1), ['=', '+', '-', '@'], true)) {
        return "'" . $field;
    }
    return $field;
}

foreach ($leads as $row) {
    $rowData = [
        $row['name'],
        $row['company']       ?? '',
        $row['email']         ?? '',
        $row['phone']         ?? '',
        $row['source']        ?? '',
        $row['status'],
        $row['priority'],
        $row['notes']         ?? '',
        $row['followup_date'] ?? '',
        $row['followup_notes']?? '',
        $row['created_at'],
    ];
    $sanitizedRow = array_map('preventCsvInjection', $rowData);
    fputcsv($out, $sanitizedRow);
}

fclose($out);
exit;
?>
