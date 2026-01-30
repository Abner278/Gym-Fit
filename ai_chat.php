<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// 🔐 Hugging Face API KEY (KEEP SECRET)
$API_KEY = "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";

// 🤖 Model
$MODEL = "mistralai/Mistral-7B-Instruct-v0.2";
$URL = "https://router.huggingface.co/v1/chat/completions";

// 📩 Read user input
$input = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($input["message"] ?? "");

if ($userMessage === "") {
    echo json_encode(["reply" => "Please type a message 🙂"]);
    exit;
}

// 🧠 AI payload
$data = [
    "model" => $MODEL,
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a friendly AI fitness coach. Give safe, beginner-friendly workout and diet advice."
        ],
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ],
    "temperature" => 0.7,
    "max_tokens" => 200
];

// 🌐 cURL request
$ch = curl_init($URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $API_KEY",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["reply" => "Curl error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

// ✅ Extract AI reply
if (isset($result["choices"][0]["message"]["content"])) {
    echo json_encode([
        "reply" => trim($result["choices"][0]["message"]["content"])
    ]);
} else {
    echo json_encode([
    "reply" => "AI Error",
    "debug" => $result
]);

}
