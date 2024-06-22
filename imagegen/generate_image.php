<?php
// Database configuration
$servername = "localhost";
$username = "user1";
$password = "";
$dbname = "wallify";

// OpenAI API key
$openai_api_key = 'PUT_KEY_HERE';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_query = $_POST['search_query'];

    // Call OpenAI API to generate image based on user input
    $prompt = "Generate an image of " . $search_query;
    $data = array(
        'prompt' => $prompt
    );
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key
    );

    $ch = curl_init("https://api.openai.com/v1/images/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
    curl_close($ch);

    if ($http_code == 200) {
        $image_data = json_decode($response, true);
        if (isset($image_data['url'])) {
            $image_url = $image_data['url'];

            // Save the image to uploads folder (you may need to adjust the folder path)
            $image_name = uniqid() . '.jpg'; // Unique image name
            $upload_path = 'uploads/' . $image_name;
            file_put_contents($upload_path, file_get_contents($image_url));

            // Save data to MySQL database
            $sql = "INSERT INTO images2 (image_path, tags) VALUES ('$upload_path', '$search_query')";

            if ($conn->query($sql) === TRUE) {
                echo "Image generated and saved successfully!";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            echo "Error: Failed to retrieve image URL from OpenAI response";
        }
    } else {
        echo "Error: Failed to call OpenAI API. HTTP Status Code: " . $http_code . "<br>";
        echo "Response: " . $response;
    }
}

$conn->close();
?>
