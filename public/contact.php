<?php
// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $message = trim($_POST["message"]);
    $website = trim($_POST["website"]); // Honeypot field

    // Validate the data
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo "All required fields must be filled out.";
        exit;
    }

    // Simple email validation (check for @ and .)
    if (!strpos($email, '@') || !strpos($email, '.')) {
        http_response_code(400);
        echo "Please enter a valid email address.";
        exit;
    }

    // Check if honeypot field is filled (likely a bot)
    $isSpam = !empty($website);

    // Set the recipient email address
    $to = "aryn@thesonguru.com"; // Change this to your email address
    $from = "aryn@thesonguru.com";

    // Set the email subject
    $subject = $isSpam ? "SPAM: New TheSONGuru Inquiry from $name" : "New TheSONGuru Inquiry from $name";

    // Build the email content
    $email_content = $isSpam ? "LIKELY SPAM - Honeypot field was filled\n\n" : "";
    $email_content .= "Name: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";
    if ($isSpam) {
        $email_content .= "\nHoneypot Field (Website): $website\n";
    }

    // Build the email headers
    $headers = "From: <$from>";

    // Send the email
    if (mail($to, $subject, $email_content, $headers)) {
        // Return a success response
        http_response_code(200);
        echo "Thank you! Your request has been sent. We'll contact you soon.";
    } else {
        http_response_code(500);
        echo "Oops! Something went wrong and we couldn't send your request.";
    }
} else {
    // Not a POST request
    http_response_code(405);
    echo "Method not allowed.";
}
?>
