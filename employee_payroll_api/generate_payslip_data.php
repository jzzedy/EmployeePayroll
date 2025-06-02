<?php
require_once 'config.php'; // $db_connection

header('Content-Type: application/json');
$response = array(
    'status' => 'error',
    'message' => 'An unknown error occurred.',
    'payslip_data' => null
);


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $employee_id_custom = isset($_GET['employee_id_custom']) ? trim($_GET['employee_id_custom']) : '';
    $pay_month = isset($_GET['month']) ? intval($_GET['month']) : 0; 
    $pay_year = isset($_GET['year']) ? intval($_GET['year']) : 0;

    if (empty($employee_id_custom)) {
        $response['message'] = 'Employee ID is required.';
    } elseif ($pay_month < 1 || $pay_month > 12 || $pay_year < 1900 || $pay_year > date("Y") + 5) { // Basic year validation
        $response['message'] = 'Invalid pay period (month/year) specified.';
    } else {
        //Fetch Employee Details
        $employee_details = null;
        $stmt_emp = $db_connection->prepare(
            "SELECT emp_id_system, first_name, last_name, department, basic_salary, employee_id_custom, 
                    DATE_FORMAT(date_hired, '%Y-%m-%d') as date_hired 
             FROM employees 
             WHERE employee_id_custom = ? AND is_active = 1"
        );

        if ($stmt_emp) {
            $stmt_emp->bind_param("s", $employee_id_custom);
            if ($stmt_emp->execute()) {
                $result_emp = $stmt_emp->get_result();
                if ($result_emp->num_rows === 1) {
                    $employee_details = $result_emp->fetch_assoc();
                } else {
                    $response['message'] = 'Active employee not found for ID: ' . htmlspecialchars($employee_id_custom);
                }
            } else {
                $response['message'] = 'Failed to execute employee details query: ' . $stmt_emp->error;
            }
            $stmt_emp->close();
        } else {
            $response['message'] = 'Failed to prepare employee details statement: ' . $db_connection->error;
        }

        if ($employee_details) {
   
            $first_day_of_month = sprintf("%04d-%02d-01", $pay_year, $pay_month);
            $last_day_of_month = date("Y-m-t", strtotime($first_day_of_month)); // 't' gives number of days in month

            $deductions_data = null;
            $stmt_deduct = $db_connection->prepare(
                "SELECT sss_contribution, philhealth_contribution, pagibig_contribution, 
                        withholding_tax, other_deductions, total_deductions 
                 FROM deductions 
                 WHERE emp_id_system_fk = ? 
                   AND recorded_at >= ? 
                   AND recorded_at <= ? 
                 ORDER BY recorded_at DESC 
                 LIMIT 1" 
            );

            if ($stmt_deduct) {
                $stmt_deduct->bind_param("iss", $employee_details['emp_id_system'], $first_day_of_month, $last_day_of_month);

                if ($stmt_deduct->execute()) {
                    $result_deduct = $stmt_deduct->get_result();
                    if ($result_deduct->num_rows === 1) {
                        $deductions_data = $result_deduct->fetch_assoc();
                    } else {
                       
                        $deductions_data = [
                            'sss_contribution' => 0.00, 'philhealth_contribution' => 0.00,
                            'pagibig_contribution' => 0.00, 'withholding_tax' => 0.00,
                            'other_deductions' => 0.00, 'total_deductions' => 0.00
                        ];
                        
                    }
                } else {
                     $response['message'] = 'Failed to execute deductions query: ' . $stmt_deduct->error;
                     $deductions_data = false; 
                }
                $stmt_deduct->close();
            } else {
                $response['message'] = 'Failed to prepare deductions statement: ' . $db_connection->error;
                $deductions_data = false; 
            }

            if ($deductions_data !== false) {
                $basic_salary = floatval($employee_details['basic_salary']);

                // Use fetched deductions
                $sss = floatval($deductions_data['sss_contribution']);
                $philhealth = floatval($deductions_data['philhealth_contribution']);
                $pagibig = floatval($deductions_data['pagibig_contribution']);
                $tax = floatval($deductions_data['withholding_tax']);
                $other_deductions_val = floatval($deductions_data['other_deductions']);

                //calculate total deductions
                $current_total_deductions = $sss + $philhealth + $pagibig + $tax + $other_deductions_val;
                $current_net_pay = $basic_salary - $current_total_deductions;

                $response['status'] = 'success';
                $response['message'] = 'Payslip data generated successfully.';
                $response['payslip_data'] = array(
                    'employee_id' => $employee_details['employee_id_custom'],
                    'full_name' => $employee_details['first_name'] . ' ' . $employee_details['last_name'],
                    'department' => $employee_details['department'],
                    'date_hired' => $employee_details['date_hired'], 
                    'pay_period_month_year' => date("F Y", mktime(0, 0, 0, $pay_month, 1, $pay_year)),
                    'pay_period_full' => $first_day_of_month . " to " . $last_day_of_month,

                    'earnings' => array(
                        array('description' => 'Basic Salary', 'amount' => number_format($basic_salary, 2)),
                        // Add other earnings here 
                    ),
                    'total_earnings' => number_format($basic_salary, 2), 

                    'deductions_list' => array( // Changed key for clarity
                        array('description' => 'SSS Contribution', 'amount' => number_format($sss, 2)),
                        array('description' => 'PhilHealth Contribution', 'amount' => number_format($philhealth, 2)),
                        array('description' => 'Pag-IBIG Contribution', 'amount' => number_format($pagibig, 2)),
                        array('description' => 'Withholding Tax', 'amount' => number_format($tax, 2)),
                        array('description' => 'Other Deductions', 'amount' => number_format($other_deductions_val, 2)),
                    ),
                    'total_deductions' => number_format($current_total_deductions, 2),
                    'net_pay' => number_format($current_net_pay, 2)
                );
            }
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$db_connection->close();
echo json_encode($response);
exit;
?>