<?php
// --- CONFIGURATION ---
$telegramBotToken = '8010764268:AAGxW-Cgf4NOozPAY20BZJmpBDClDbbbftg'; // YOUR TELEGRAM BOT TOKEN
$telegramChatId = '7755823415'; // YOUR TELEGRAM CHAT ID
$emailTo = 'bandaraakarsha99@gmail.com'; // YOUR EMAIL

header('Content-Type: application/json');

// --- HELPER FUNCTION TO SEND RESPONSE AND EXIT ---
function sendResponse($status, $message, $extraData = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extraData));
    exit;
}

// --- VALIDATE REQUEST METHOD ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Invalid request method.');
}

// --- VALIDATE INPUTS ---
if (!isset($_POST['whatsapp']) || !isset($_POST['package']) || !isset($_FILES['proof'])) {
    sendResponse('error', 'Missing form data.');
}

$whatsapp = trim($_POST['whatsapp']);
$packageInfo = trim($_POST['package']);
$proofFile = $_FILES['proof'];

if (empty($whatsapp) || empty($packageInfo) || $proofFile['error'] !== UPLOAD_ERR_OK) {
    sendResponse('error', 'All fields are required and file must be uploaded successfully.');
}

// Extract package name and amount
list($packageName, $amount) = explode(' - ', $packageInfo);
$amount = trim($amount);

// --- PROCESS TELEGRAM NOTIFICATION (WITH FILE) ---
$telegramApiUrl = "https://api.telegram.org/bot{$telegramBotToken}/sendPhoto";
$caption = "New Ukussa VIP Payment!\n\n"
         . "WhatsApp: " . $whatsapp . "\n"
         . "Package: " . $packageName . "\n"
         . "Amount: " . $amount;

// Use cURL to send a multipart/form-data request
$ch = curl_init();
$curl_post_data = [
    'chat_id' => $telegramChatId,
    'photo'   => new CURLFile($proofFile['tmp_name'], $proofFile['type'], $proofFile['name']),
    'caption' => $caption,
];
curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$telegramResult = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// If sending the photo fails (e.g., PDF was uploaded), send as a document
if ($httpcode != 200) {
    $telegramApiUrl = "https://api.telegram.org/bot{$telegramBotToken}/sendDocument";
    $ch = curl_init();
    $curl_post_data = [
        'chat_id' => $telegramChatId,
        'document' => new CURLFile($proofFile['tmp_name'], $proofFile['type'], $proofFile['name']),
        'caption' => $caption,
    ];
    curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $telegramResult = curl_exec($ch);
    curl_close($ch);
}

// --- PROCESS EMAIL NOTIFICATION (TEXT ONLY FOR SIMPLICITY) ---
// Note: Sending attachments via PHP's mail() is complex. Telegram is the primary method for the file.
$emailSubject = "New Ukussa VIP Payment - " . $whatsapp;
$emailBody = "A new payment has been submitted.\n\n"
           . "WhatsApp Number: " . $whatsapp . "\n"
           . "Package: " . $packageName . "\n"
           . "Amount: " . $amount . "\n\n"
           . "The payment proof has been sent to your Telegram chat.";
$emailHeaders = "From: no-reply@yourdomain.com"; // Change this if you have a domain

// Use @ to suppress warnings if mail() fails, as it's not always configured.
@mail($emailTo, $emailSubject, $emailBody, $emailHeaders);

// --- PREPARE DATA FOR RECEIPT ---
date_default_timezone_set('Asia/Colombo'); // Set your timezone
$receiptData = [
    'number' => 'UKUSSA-' . strtoupper(uniqid()),
    'whatsapp' => htmlspecialchars($whatsapp),
    'package' => htmlspecialchars($packageName),
    'amount' => htmlspecialchars($amount),
    'dateTime' => date('Y-m-d H:i:s')
];

// --- SEND SUCCESS RESPONSE ---
sendResponse('success', 'Payment submitted successfully! Your receipt will now open.', ['receipt' => $receiptData]);

?>