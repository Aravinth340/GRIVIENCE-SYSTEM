<?php
// Add these if missing

function canCloseGrievance($status) {
    return in_array($status, ['resolved', 'rejected', 'closed']);
}

function closeGrievance($grievanceId, $closureReason = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            UPDATE grievances 
            SET status = 'closed', resolution_remarks = ?, resolution_date = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$closureReason, $grievanceId]);
        
        if ($result) {
            logActivity('admin', $_SESSION['admin_id'] ?? null, 'GRIEVANCE_CLOSED', 'Grievance closed: ' . $closureReason, $grievanceId);
        }
        return $result;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

?>