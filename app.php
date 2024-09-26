<?php


if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

// Start the session to manage user states

session_start();



// In-memory storage for user credentials

$users = [];



$users_file = 'users.json';



// Load the users data from the file

function load_users() {

    global $users_file;

    if (file_exists($users_file)) {

        $users = json_decode(file_get_contents($users_file), true);

        return $users ?? []; // Return an empty array if no users found

    }

    return [];

}



// Save the users data to the file

function save_users($users) {

    global $users_file;

    file_put_contents($users_file, json_encode($users));

}



// Function to handle registration

function register($email, $password) {

    global $users;

	$users = load_users();

    // Check if the email already exists

    if (isset($users[$email])) {

        http_response_code(400);

        echo json_encode(['message' => 'Email already registered.']);

        return;

    }



    // Store the new user credentials (in memory)

    $users[$email] = password_hash($password, PASSWORD_DEFAULT);

	

	save_users($users);

    echo json_encode(['message' => 'Registration successful.']);

}







// Function to handle login

function login($email, $password) {

    global $users;

	$users = load_users();

    // Check if the user exists

    if (!isset($users[$email])) {

        http_response_code(400);

        echo json_encode(['message' => 'Invalid email.']);

        return;

    }



    // Verify the password

    if (!password_verify($password, $users[$email])) {

        http_response_code(400);

        echo json_encode(['message' => 'Invalid password.']);

        return;

    }



    // Successful login, set session

    $_SESSION['user'] = $email;

    echo json_encode(['message' => 'Login successful.', 'user' => $email]);

}



function userlist() {

    global $users;

	$users = load_users();

	$value_pairs = [];
        foreach ($users as $email => $hash) {
            $value_pairs[] = ['email' => $email];
        }

        // Encode the value-pair array to JSON
        $value_pairs_json = json_encode($value_pairs, JSON_PRETTY_PRINT);

        // Output the JSON
        echo $value_pairs_json;

}



// Handle incoming requests

$request_method = $_SERVER['REQUEST_METHOD'];

switch ($request_method) {

    case 'POST':

        // Get the request body

        $data = json_decode(file_get_contents("php://input"), true);

        

        // Check if action is provided

        if (!isset($data['action'])) {

            http_response_code(400);

            echo json_encode(['message' => 'Action not specified.']);

            break;

        }



        // Call the appropriate function based on the action

        if ($data['action'] === 'register') {

            if (isset($data['email']) && isset($data['password'])) {

                register($data['email'], $data['password']);

            } else {

                http_response_code(400);

                echo json_encode(['message' => 'Email and password are required.']);

            }

        } elseif ($data['action'] === 'login') {

            if (isset($data['email']) && isset($data['password'])) {

                login($data['email'], $data['password']);

            } else {

                http_response_code(400);

                echo json_encode(['message' => 'Email and password are required.']);

            }

        }elseif ($data['action'] === 'userlist') {

             userlist();

       

        }else {

            http_response_code(400);

            echo json_encode(['message' => 'Invalid action.']);

        }

        break;



    default:

        http_response_code(405);

        echo json_encode(['message' => 'Method not allowed.']);

        break;

}

?>

			
