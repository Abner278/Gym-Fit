<?php
$API_KEY = "AIzaSyDERp8bNwhecn8oZUUSrMQwqP79TH5SX4E";

$input = json_decode(file_get_contents("php://input"), true);
$userMsg = $input["message"];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$API_KEY";

$data = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                [
                    "text" => "You are a gym fitness assistant.
Give simple, educational, non-medical answers.
Question: $userMsg"
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.6,
        "maxOutputTokens" => 300
    ],
    "safetySettings" => [
        [
            "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
            "threshold" => "BLOCK_ONLY_HIGH"
        ],
        [
            "category" => "HARM_CATEGORY_HARASSMENT",
            "threshold" => "BLOCK_ONLY_HIGH"
        ],
        [
            "category" => "HARM_CATEGORY_HATE_SPEECH",
            "threshold" => "BLOCK_ONLY_HIGH"
        ],
        [
            "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
            "threshold" => "BLOCK_ONLY_HIGH"
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

// Debug logging
file_put_contents("gemini_debug.json", $response);

$result = json_decode($response, true);

if (isset($result["candidates"][0]["content"]["parts"][0]["text"])) {
    echo json_encode([
        "reply" => $result["candidates"][0]["content"]["parts"][0]["text"]
    ]);
} else {
    // Return the actual error if available for debugging, or generic message
    $errorMsg = "AI is temporarily unavailable.";
    if (isset($result["error"]["message"])) {
        $errorMsg .= " (Debug: " . $result["error"]["message"] . ")";
    }
    echo json_encode([
        "reply" => $errorMsg
    ]);
}
