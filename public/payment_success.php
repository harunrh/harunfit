<?php
/**
 * Payment Success Page
 * Shown after successful Stripe payment
 * Sends email with PDF attachment
 */

// Initialize
define('HARUNFIT_SECURE', true);
require_once __DIR__ . '/../private/includes/email.php';

// Get parameters
$email = $_GET['email'] ?? '';
$product = $_GET['product'] ?? '';

// Determine which PDF to download
$filename = '';
$productName = '';
$price = '';

if ($product === 'premium') {
    $filename = 'PremiumTrainingProgram.pdf';
    $productName = 'Complete Transformation Program';
    $price = '£25.00';
} elseif ($product === 'starter') {
    $filename = 'StarterProgram.pdf';
    $productName = 'Standard Program';
    $price = '£5.99';
} else {
    // Invalid product
    header('Location: index.html');
    exit;
}

// Sanitize email
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// Send email with PDF if email is provided
$emailSent = false;
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pdfPath = dirname(__DIR__) . '/private/products/' . $filename;
    $emailTemplate = getPDFEmailTemplate($productName);
    $subject = "Your HarunFit Program - $productName";
    
    $emailSent = sendPDFEmail($email, $subject, $emailTemplate, $pdfPath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - HarunFit</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f28c38, #e74c3c);
            padding: 20px;
        }
        
        .success-box {
            background: white;
            padding: 60px 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: #43a047;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .checkmark {
            width: 40px;
            height: 40px;
            stroke: white;
            stroke-width: 3;
            fill: none;
        }
        
        h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .product-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .product-price {
            color: #f28c38;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .email-notice {
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .email-notice strong {
            color: #2c3e50;
        }
        
        .email-status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .email-success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            color: #2e7d32;
        }
        
        .email-error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            color: #c62828;
        }
        
        .download-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f28c38;
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            margin: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .download-button:hover {
            background: #e07a26;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .download-icon {
            width: 20px;
            height: 20px;
        }
        
        .home-link {
            display: inline-block;
            margin-top: 20px;
            color: #787878;
            text-decoration: none;
        }
        
        .home-link:hover {
            color: #f28c38;
        }
        
        .thank-you {
            color: #666;
            margin: 20px 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-box">
            <div class="success-icon">
                <svg class="checkmark" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="14 27 22 35 38 17"/>
                </svg>
            </div>
            
            <h1>Payment Successful!</h1>
            
            <p class="thank-you">Thank you for your purchase. Your transformation journey starts now!</p>
            
            <div class="product-info">
                <div class="product-name"><?php echo htmlspecialchars($productName); ?></div>
                <div class="product-price"><?php echo htmlspecialchars($price); ?></div>
            </div>
            
            <?php if ($email): ?>
                <?php if ($emailSent): ?>
                    <div class="email-status email-success">
                        <strong>✓ Email Sent!</strong><br>
                        Your program has been sent to: <strong><?php echo htmlspecialchars($email); ?></strong><br>
                        <small>Check your inbox (and spam folder if needed)</small>
                    </div>
                <?php else: ?>
                    <div class="email-status email-error">
                        <strong>⚠ Email Failed</strong><br>
                        We couldn't send the email automatically.<br>
                        <small>Please download your program using the button below</small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="secure_download.php?file=<?php echo urlencode($filename); ?>&email=<?php echo urlencode($email); ?>" class="download-button">                <svg class="download-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download Your Program
            </a>
            
            <div>
                <a href="index.html" class="home-link">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>