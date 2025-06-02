<?php
require_once 'config.php'; // $db_connection

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An unknown error occurred.', 'employee' => null);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $employee_id_custom = isset($_GET['employee_id_custom']) ? trim($_GET['employee_id_custom']) : '';

    if (empty($employee_id_custom)) {
        $response['message'] = 'Employee ID is required to fetch details.';
    } else {
        $sql = "SELECT emp_id_system, employee_id_custom, first_name, last_name, 
                       DATE_FORMAT(date_of_birth, '%Y-%m-%d') as date_of_birth, 
                       civil_status, contact_number, address_text, zip_code, 
                       gender, department, 
                       DATE_FORMAT(date_hired, '%Y-%m-%d') as date_hired, 
                       basic_salary 
                FROM employees 
                WHERE employee_id_custom = ? AND is_active = 1";

        $stmt = $db_connection->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $employee_id_custom);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $employee_data = $result->fetch_assoc();
                    $response['status'] = 'success';
                    $response['employee'] = $employee_data;
                    $response['message'] = 'Employee details fetched successfully.';
                } else {
                    $response['message'] = 'Employee not found or not active for ID: ' . htmlspecialchars($employee_id_custom);
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
    $response['message'] = 'Invalid request method. Only GET is accepted for this endpoint.';
}

$db_connection->close();
echo json_encode($response);
exit;
?>