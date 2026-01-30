<?php
header("Content-Type: application/json");

// Your FREE HF token here
$HF_TOKEN = "token";

$input = json_decode(file_get_contents("php://input"), true);
$message = trim($input["message"] ?? "");

if ($message === "") {
    echo json_encode(["reply" => "Please type a message 🙂"]);
    exit;
}

$prompt = "You are a fitness coach chatbot. User says: \"$message\". Reply helpfully.";

$data = [
    "inputs" => $prompt,
    "parameters" => [
        "max_new_tokens" => 150,
        "temperature" => 0.7
    ]
];

$ch = curl_init("https://api-inference.huggingface.co/models/google/flan-t5-large");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $HF_TOKEN",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// If HF says model is loading, ask user to retry shortly
if (isset($result["error"]) && strpos($result["error"], "loading") !== false) {
    echo json_encode(["reply" => "Model is waking up — please try again in 10 seconds."]);
    exit;
}

// For safety if key fails or unclear
if (!isset($result[0]["generated_text"])) {
    echo json_encode(["reply" => "AI model is unavailable right now. Try again soon."]);
    exit;
}

echo json_encode([
    "reply" => $result[0]["generated_text"]
]);
