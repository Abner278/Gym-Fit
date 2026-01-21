<?php
$API_KEY = "AIzaSyDERp8bNwhecn8oZUUSrMQwqP79TH5SX4E";

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent?key=" . $API_KEY;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "What are the benefits of gym?"]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "CURL ERROR: " . curl_error($ch);
    exit;
}

curl_close($ch);

echo $response;
