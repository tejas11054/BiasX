<?php
if ($_FILES['csvFile']) {
    $file = $_FILES['csvFile']['tmp_name'];
    $destination = "uploads/" . $_FILES['csvFile']['name'];
    move_uploaded_file($file, $destination);

    // Send file to Colab
    $colab_url = "https://colab.research.google.com/drive/1gX8x9VZGj2cba07ZAT6B5aaqB5tY7KTq?authuser=2#scrollTo=X0K4YEqoUcHC"; // Replace with your Colab link

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $colab_url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, ['file' => new CURLFile($destination)]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);

    // Return output from Colab
    echo json_encode(["status" => "success", "colab_output" => $response]);
} else {
    echo json_encode(["status" => "error", "message" => "No file uploaded."]);
}
?>
