<?php
require_once 'config.php'; // $db_connection

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An unknown error occurred.', 'employees' => []);

//use GET for search
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search_term = isset($_GET['employee_id_custom']) ? trim($_GET['employee_id_custom']) : '';

    if (empty($search_term)) {
        $sql = "SELECT emp_id_system, employee_id_custom, first_name, last_name, department, basic_salary, date_of_birth, civil_status, contact_number, address_text, zip_code, gender, date_hired FROM employees WHERE is_active = 1 ORDER BY last_name, first_name";
        $stmt = $db_connection->prepare($sql);
    } else {
        $sql = "SELECT emp_id_system, employee_id_custom, first_name, last_name, department, basic_salary, date_of_birth, civil_status, contact_number, address_text, zip_code, gender, date_hired FROM employees WHERE employee_id_custom = ? AND is_active = 1";
        $stmt = $db_connection->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $search_term);
        }
    }

    if ($stmt) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $employees_data = array();
            while ($row = $result->fetch_assoc()) {
                $employees_data[] = $row;
            }
            $response['status'] = 'success';
            $response['employees'] = $employees_data;
            if (empty($employees_data) && !empty($search_term)) {
                $response['message'] = 'No employee found matching Employee ID: ' . htmlspecialchars($search_term);
            } elseif (empty($employees_data) && empty($search_term)) {
                $response['message'] = 'No active employees found in the database.';
            } else {
                 $response['message'] = 'Employees fetched successfully.';
            }
        } else {
            $response['message'] = 'Execute failed: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Database statement preparation failed: ' . $db_connection->error;
    }
} else {
    $response['message'] = 'Invalid request method. Only GET is accepted for this search endpoint.';
}

$db_connection->close();
echo json_encode($response);
exit;
?>