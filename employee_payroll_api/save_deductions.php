<?php
require_once 'config.php'; // $db_connection 

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An unknown error occurred.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //employee identifier
    $employee_id_custom = isset($_POST['employee_id_custom']) ? trim($_POST['employee_id_custom']) : '';

    //deduction values
    $sss_contribution = isset($_POST['sss_contribution']) ? trim($_POST['sss_contribution']) : '0.00';
    $philhealth_contribution = isset($_POST['philhealth_contribution']) ? trim($_POST['philhealth_contribution']) : '0.00';
    $pagibig_contribution = isset($_POST['pagibig_contribution']) ? trim($_POST['pagibig_contribution']) : '0.00';
    $withholding_tax = isset($_POST['withholding_tax']) ? trim($_POST['withholding_tax']) : '0.00';
    $other_deductions = isset($_POST['other_deductions']) ? trim($_POST['other_deductions']) : '0.00';
    $total_deductions = isset($_POST['total_deductions']) ? trim($_POST['total_deductions']) : '0.00';
    $net_pay = isset($_POST['net_pay']) ? trim($_POST['net_pay']) : '0.00';

    //Server-side validation
    if (empty($employee_id_custom)) {
        $response['message'] = 'Employee ID is required to save deductions.';
    } else {
        $emp_id_system = null;
        $stmt_get_id = $db_connection->prepare("SELECT emp_id_system FROM employees WHERE employee_id_custom = ? AND is_active = 1");
        if ($stmt_get_id) {
            $stmt_get_id->bind_param("s", $employee_id_custom);
            $stmt_get_id->execute();
            $result_get_id = $stmt_get_id->get_result();
            if ($result_get_id->num_rows === 1) {
                $row = $result_get_id->fetch_assoc();
                $emp_id_system = $row['emp_id_system'];
            } else {
                $response['message'] = 'Active employee not found for ID: ' . htmlspecialchars($employee_id_custom);
            }
            $stmt_get_id->close();
        } else {
            $response['message'] = 'Failed to prepare statement to fetch employee system ID.';
        }

        if ($emp_id_system !== null) {
            $stmt_insert_deduction = $db_connection->prepare(
                "INSERT INTO deductions (emp_id_system_fk, sss_contribution, philhealth_contribution, pagibig_contribution, withholding_tax, other_deductions, total_deductions, net_pay) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)" 
            );

            if ($stmt_insert_deduction) {
                $stmt_insert_deduction->bind_param(
                    "iddddddd", 
                    $emp_id_system,
                    $sss_contribution,
                    $philhealth_contribution,
                    $pagibig_contribution,
                    $withholding_tax,
                    $other_deductions,
                    $total_deductions,
                    $net_pay
                   
                );

                if ($stmt_insert_deduction->execute()) {
                    if ($stmt_insert_deduction->affected_rows > 0) {
                        $response['status'] = 'success';
                        $response['message'] = 'Deductions saved successfully for Employee ID: ' . htmlspecialchars($employee_id_custom);
                    } else {
                        $response['message'] = 'Deductions could not be saved (no rows affected).';
                    }
                } else {
                    $response['message'] = 'Execute failed (save deduction): ' . $stmt_insert_deduction->error;
                }
                $stmt_insert_deduction->close();
            } else {
                $response['message'] = 'Database statement preparation failed (save deduction): ' . $db_connection->error;
            }
        }
    }
} else {
    $response['message'] = 'Invalid request method. Only POST is accepted.';
}

$db_connection->close();
echo json_encode($response);
exit;
?>