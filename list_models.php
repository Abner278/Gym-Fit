<?php
$API_KEY = "AIzaSyDERp8bNwhecn8oZUUSrMQwqP79TH5SX4E";
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=$API_KEY";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo "<pre>";
echo $response;
echo "</pre>";
