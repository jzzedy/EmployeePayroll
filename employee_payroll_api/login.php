<?php
require_once 'config.php'; // $db_connection
//JSON
header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'An unknown error occurred.');

//ccheck if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';


    if (empty($username) || empty($password)) {
        $response['message'] = 'Username and password are required.';
    } else {
        //prevent SQL Injection
        $stmt = $db_connection->prepare("SELECT user_id, username, password_hash, full_name FROM users WHERE username = ?");

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                //Verifypassword
                if (password_verify($password, $user['password_hash'])) {
                    //Passwordcorrect
                    $response['status'] = 'success';
                    $response['message'] = 'Login successful!';

                    $response['user_data'] = array(
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name']
                    );

                } else {
                    //Invalid password
                    $response['message'] = 'Invalid username or password.';
                }
            } else {
                //User not found
                $response['message'] = 'Invalid username or password.';
            }
            $stmt->close();
        } else {
            $response['message'] = 'Database query error: ' . $db_connection->error;
        }
    }
} else {
    $response['message'] = 'Invalid request method. Only POST is accepted.';
}

// Close database 
$db_connection->close();

echo json_encode($response);
exit;
?>