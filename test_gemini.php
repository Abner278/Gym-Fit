<?php
$API_KEY = ;

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$API_KEY";

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
    die("CURL ERROR: " . curl_error($ch));
}

curl_close($ch);

echo "<pre>";
echo $response;
echo "</pre>";
