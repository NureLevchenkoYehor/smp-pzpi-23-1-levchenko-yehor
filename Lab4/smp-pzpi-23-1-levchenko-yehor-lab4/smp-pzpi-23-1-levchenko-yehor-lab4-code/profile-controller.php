<?php

const SQL_FIND_USER_PROFILE = '
SELECT 
    c.[customer_first_name] AS first_name,
    c.[customer_last_name] AS last_name,
    c.[customer_birth_date] AS birthdate,
    c.[customer_description] AS description,
    i.[image_id] AS image_id,
    i.[image_url] AS image_url,
    i.[image_name] AS image_name
FROM [CustomerProfiles] c
LEFT JOIN [Images] i ON c.[customer_profile_image_id] = i.[image_id]
WHERE [customer_identity_id] = :id
LIMIT 1;';

const SQL_INSERT_IMAGE = '
INSERT INTO [Images] ([image_name], [image_url])
VALUES (:image_name, :image_url);';

const SQL_UPDATE_PROFILE = '
UPDATE [CustomerProfiles] SET 
    [customer_first_name] = :first_name,
    [customer_last_name] = :last_name,
    [customer_birth_date] = :birthdate,
    [customer_description] = :description,
    [customer_profile_image_id] = :image_id
WHERE [customer_identity_id] = :user_id;';


function get_profile(PDO $pdo, int $userId) {
    $stmt = $pdo->prepare(SQL_FIND_USER_PROFILE);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user_profile = $stmt->fetch();
    if (!$user_profile) {
        return null;
    }
    return (array) $user_profile;
}

function update_profile(PDO $pdo, array $params) {
    // Validate the parameters (in a cool way)
    $expectedParams = [
        'user_id',
        'first_name',
        'last_name',
        'birthdate',
        'description',
        'image_id',
        'image_name',
        'image_url'
    ];
    $missingParams = array_diff($expectedParams, array_keys($params));
    if (count($missingParams) > 0) {
        throw new InvalidArgumentException('Missing parameters: ' . implode(', ', $missingParams));
    }
    // Update profile
    $pdo->beginTransaction();
    // 1. Insert the image into the Images table if there's a new image
    if (!isset($params['image_id'])) {
        $stmt = $pdo->prepare(SQL_INSERT_IMAGE);
        $stmt->bindParam(':image_name', $params['image_name']);
        $stmt->bindParam(':image_url', $params['image_url']);
        $stmt->execute();
        $imageId = $pdo->lastInsertId();
        if (!$imageId) {
            $pdo->rollBack();
            return false;
        }
        $params['image_id'] = $imageId;
    }
    // 2. Update the customer profile with the new image ID
    $stmt = $pdo->prepare(SQL_UPDATE_PROFILE);
    $stmt->bindParam(':first_name', $params['first_name']);
    $stmt->bindParam(':last_name', $params['last_name']);
    $stmt->bindParam(':birthdate', $params['birthdate']);
    $stmt->bindParam(':description', $params['description']);
    $stmt->bindParam(':image_id', $params['image_id']);
    $stmt->bindParam(':user_id', $params['user_id']);
    if (!$stmt->execute()) {
        $pdo->rollBack();
        return false;
    }
    // 3. Commit the transaction
    $pdo->commit();
    return true;
}

// GET /profile
function index(array $params) {
    // Check if the user is already logged in
    if (!isset($_SESSION['user']['id'])) {
        // Redirect to the login page
        http_response_code(401);
        header('Location: /credential/login');
        exit;
    }
    // Prepare profile data
    global $pdo;
    $userId = $_SESSION['user']['id'];
    $profile = get_profile($pdo, $userId);
    // Logout if the profile is not found
    if (!isset($profile)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Profile not found: ' . $userId
        ]);
        // header('Location: /credential/logout');
        exit;
    }
    // Prepare errors data
    $error = [];
    foreach ($params as $key => $value) {
        $error[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    require './profile.php';
    exit;
}

// POST /profile/update
function update(array $params) {
    // Validate the parameters (in a cool way)
    $expectedParams = [
        'user_id' => 'user id',
        'first_name' => 'first name',
        'last_name' => 'last name',
        'birthdate' => 'birthdate',
        'description' => 'description',
        'image_name' => 'image name',
        'image_url' => 'image url',
    ];
    // Check expected parameters
    $errors = [];
    foreach ($params as $key => $value) {
        // Check if the parameter is expected
        if (!in_array($key, $expectedParams)) {
            continue;
        }
        // Check if the parameter is empty
        if (empty($value)) {
            $errors[$key] = "The {$expectedParams[$key]} field is required.";
        }
    }
    // First and last name must be strings and more than 1 character
    if (strlen($params['first_name']) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters long.';
    }
    if (strlen($params['last_name']) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters long.';
    }
    // user must be at least 16 years old
    $birthdate = new DateTime($params['birthdate']);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
    if ($age < 16) {
        $errors['birthdate'] = 'You must be at least 16 years old.';
    }
    // description must be at least 50 characters
    if (strlen($params['description']) < 50) {
        $errors['description'] = 'Description must be at least 50 characters long.';
    }
    // Check if there are any errors
    if (!empty($errors)) {
        // Redirect to the profile page with errors
        http_response_code(400);
        $query = http_build_query($errors);
        header("Location: /profile?$query");
        exit;
    }

    // Prepare parameters
    global $pdo;
    foreach ($params as $key => $value) {
        $params[$key] = htmlspecialchars($value);
    }
    $params['birthdate'] = date('Y-m-d', strtotime($params['birthdate']));
    // Handle the image upload
    $file = $_FILES['profile_image'];
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        // Extract the original file name without the extension
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);

        // Generate a unique file name for saving
        $fileSaveName = uniqid() . '_' . basename($file['name']);
        $filePath = UPLOAD_DIR . $fileSaveName;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Prepare params for the new image
            $params['image_id'] = null;
            $params['image_name'] = $fileName;
            $params['image_url'] = UPLOAD_DIR_URL . $fileSaveName;
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to save the uploaded file.',
                'data' => [
                    'file' => $file,
                    'filePath' => $filePath,
                    'fileName' => $fileName,
                    'fileSaveName' => $fileSaveName,
                ]
            ]);
            exit;
        }
    }
    // Update the profile
    if (update_profile($pdo, $params)) {
        header('Location: /profile');
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update the profile.'
        ]);
    }
    exit;
}