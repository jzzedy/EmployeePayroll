<?php
require_once 'config.php'; // $db_connection

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An unknown error occurred.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //delete via POST
    $employee_id_custom_to_delete = isset($_POST['employee_id_custom_to_delete']) ? trim($_POST['employee_id_custom_to_delete']) : '';

    if (empty($employee_id_custom_to_delete)) {
        $response['message'] = 'Employee ID to delete is missing.';
    } else {
        //soft delete yung data, which means mawawala siya sa app, pero nasa database lang siya pero naka 0.
        $stmt = $db_connection->prepare(
            "DELETE FROM employees WHERE employee_id_custom = ?"
        );

        if ($stmt) {
            $stmt->bind_param("s", $employee_id_custom_to_delete);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['status'] = 'success';
                    $response['message'] = 'Employee (ID: ' . htmlspecialchars($employee_id_custom_to_delete) . ') marked as inactive successfully!';
                } else {
                    $response['status'] = 'info';
                    $response['message'] = 'No employee found with ID: ' . htmlspecialchars($employee_id_custom_to_delete) . ' or employee was already inactive.';
                }
            } else {
                $response['message'] = 'Execute failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Database statement preparation failed: ' . $db_connection->error;
        }
    }
} else {
    $response['message'] = 'Invalid request method. Only POST is accepted.';
}

$db_connection->close();
echo json_encode($response);
exit;
?>