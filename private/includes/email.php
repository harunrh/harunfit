<?php
/**
 * Email Functions
 * Simple email sending with PHP mail()
 */

// Prevent direct access
if (!defined('HARUNFIT_SECURE')) {
    die('Direct access not permitted');
}

/**
 * Send email with PDF attachment
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body
 * @param string $pdfPath Full path to PDF file
 * @return bool Success status
 */
function sendPDFEmail($to, $subject, $message, $pdfPath) {
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to");
        return false;
    }
    
    // Check if PDF exists
    if (!file_exists($pdfPath)) {
        error_log("PDF file not found: $pdfPath");
        return false;
    }
    
    // Email headers
    $from = "noreply@harunfit.com";
    $fromName = "HarunFit";
    
    // Boundary for multipart email
    $boundary = md5(time());
    
    // Headers
    $headers = "From: $fromName <$from>\r\n";
    $headers .= "Reply-To: admin@harunfit.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    
    // Email body
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n\r\n";
    
    // Attach PDF
    $fileContent = file_get_contents($pdfPath);
    $fileContent = chunk_split(base64_encode($fileContent));
    $fileName = basename($pdfPath);
    
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"$fileName\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
    $body .= $fileContent . "\r\n";
    $body .= "--$boundary--";
    
    // Send email
    $result = mail($to, $subject, $body, $headers);
    
    if ($result) {
        error_log("Email sent successfully to: $to");
    } else {
        error_log("Failed to send email to: $to");
    }
    
    return $result;
}

/**
 * Get HTML email template for PDF delivery
 * 
 * @param string $productName Name of the product
 * @return string HTML email template
 */
function getPDFEmailTemplate($productName) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f28c38, #e74c3c); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; background: #f28c38; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #999; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Thank You For Your Purchase!</h1>
            </div>
            <div class="content">
                <p>Hi there,</p>
                
                <p>Thanks for purchasing the <strong>' . htmlspecialchars($productName) . '</strong>! Your transformation journey starts now.</p>
                
                <p><strong>Your program is attached to this email as a PDF.</strong></p>
                
                <p>If you have any questions or need support, feel free to reach out:</p>
                <ul>
                    <li>Email: <a href="mailto:admin@harunfit.com">admin@harunfit.com</a></li>
                    <li>Instagram: <a href="https://instagram.com/altharun1">@altharun1</a></li>
                </ul>
                
                <p>Let\'s get to work! 💪</p>
                
                <p>Best regards,<br><strong>Harun</strong><br>HarunFit</p>
            </div>
            <div class="footer">
                <p>&copy; 2026 HarunFit. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
?>