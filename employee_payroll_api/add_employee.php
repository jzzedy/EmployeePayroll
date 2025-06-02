<?php
require_once 'config.php'; // $db_connection

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An unknown error occurred.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id_custom = isset($_POST['employee_id_custom']) ? trim($_POST['employee_id_custom']) : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
    $civil_status = isset($_POST['civil_status']) ? trim($_POST['civil_status']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $address_text = isset($_POST['address_text']) ? trim($_POST['address_text']) : '';
    $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $date_hired = isset($_POST['date_hired']) ? trim($_POST['date_hired']) : null;
    $basic_salary_str = isset($_POST['basic_salary']) ? trim($_POST['basic_salary']) : '';

    //server-Side Validation
    $errors = [];

    if (empty($employee_id_custom)) {
        $errors[] = "Employee ID cannot be empty.";
    } elseif (!preg_match("/^[a-zA-Z0-9-]+$/", $employee_id_custom)) {
        $errors[] = "Employee ID can only contain letters, numbers, and hyphens.";
    }

    if (empty($first_name)) {
        $errors[] = "First Name cannot be empty.";
    } elseif (!preg_match("/^[a-zA-Z .'-]+$/", $first_name)) {
        $errors[] = "First Name contains invalid characters.";
    }

    if (empty($last_name)) {
        $errors[] = "Last Name cannot be empty.";
    } elseif (!preg_match("/^[a-zA-Z .'-]+$/", $last_name)) {
        $errors[] = "Last Name contains invalid characters.";
    }

    if (!empty($date_of_birth) && $date_of_birth !== "null" && $date_of_birth !== "") {
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_of_birth)) {
            $errors[] = "Date of Birth must be in YYYY-MM-DD format.";
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $date_of_birth);
            if (!$d || $d->format('Y-m-d') !== $date_of_birth) {
                $errors[] = "Date of Birth is not a valid calendar date.";
            }
        }
    }


    if (empty($civil_status)) { 
        $errors[] = "Civil Status cannot be empty.";
    }

    if (empty($contact_number)) {
        $errors[] = "Contact Number cannot be empty.";
    } elseif (!preg_match("/^\+?[0-9\s()-]+$/", $contact_number)) {
        $errors[] = "Contact Number contains invalid characters.";
    }

    if (empty($address_text)) {
        $errors[] = "Address cannot be empty.";
    }

    if (empty($zip_code)) {
        $errors[] = "Zip Code cannot be empty.";
    } elseif (!preg_match("/^[0-9]{4,10}$/", $zip_code)) {
        $errors[] = "Zip Code must be numeric (4-10 digits).";
    }

    if (empty($gender)) { 
        $errors[] = "Gender cannot be empty.";
    }

    if (empty($department)) {
        $errors[] = "Department cannot be empty.";
    }

    if (empty($date_hired) || $date_hired === "null" || $date_hired === "") { 
        $errors[] = "Date Hired cannot be empty.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_hired)) {
        $errors[] = "Date Hired must be in YYYY-MM-DD format.";
    } else {
        $dh = DateTime::createFromFormat('Y-m-d', $date_hired);
        if (!$dh || $dh->format('Y-m-d') !== $date_hired) {
            $errors[] = "Date Hired is not a valid calendar date.";
        }
    }


    if (empty($basic_salary_str)) {
        $errors[] = "Basic Salary cannot be empty.";
    } elseif (!is_numeric($basic_salary_str)) {
        $errors[] = "Basic Salary must be a valid number.";
    } else {
        $basic_salary_float = floatval($basic_salary_str);
        if ($basic_salary_float < 0) {
            $errors[] = "Basic Salary cannot be negative.";
        }
    }


    if (!empty($errors)) {
        $response['message'] = implode("\n", $errors);
    } else {
        //bawal dupli
        $checkStmt = $db_connection->prepare("SELECT emp_id_system FROM employees WHERE employee_id_custom = ?");
        if ($checkStmt) {
            $checkStmt->bind_param("s", $employee_id_custom);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $response['message'] = 'Employee ID (' . htmlspecialchars($employee_id_custom) . ') already exists.';
            } else {
                $stmt = $db_connection->prepare(
                    "INSERT INTO employees (employee_id_custom, first_name, last_name, date_of_birth, civil_status, contact_number, address_text, zip_code, gender, department, date_hired, basic_salary) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if ($stmt) {
                    $dob_db = (empty($date_of_birth) || $date_of_birth === "null") ? null : $date_of_birth;
                    $date_hired_db = (empty($date_hired) || $date_hired === "null") ? null : $date_hired;
                    $basic_salary_to_insert = floatval($basic_salary_str); 

                    $stmt->bind_param(
                        "sssssssssssd", 
                        $employee_id_custom, $first_name, $last_name, $dob_db, $civil_status, 
                        $contact_number, $address_text, $zip_code, $gender, $department, 
                        $date_hired_db, $basic_salary_to_insert
                    );

                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $response['status'] = 'success';
                            $response['message'] = 'Employee added successfully!';
                        } else {
                            $response['message'] = 'Employee could not be added (no rows affected).';
                        }
                    } else {
                        $response['message'] = 'Execute failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $response['message'] = 'Database statement preparation failed: ' . $db_connection->error;
                }
            }
            $checkStmt->close();
        } else {
             $response['message'] = 'Database statement preparation failed (check duplicate): ' . $db_connection->error;
        }
    }
} else {
    $response['message'] = 'Invalid request method. Only POST is accepted.';
}

$db_connection->close();
echo json_encode($response);
exit;
?>
