<?php
/**
 * Payment Processing API
 * Handles Stripe payment intents and confirmations
 */

// Initialize security
define('HARUNFIT_SECURE', true);
require_once __DIR__ . '/../private/config/db_config.php';
require_once __DIR__ . '/../private/config/stripe_config.php';
require_once __DIR__ . '/../private/includes/security.php';

// Send security headers
sendSecurityHeaders();
startSecureSession();

// Set JSON response
header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Initialize Stripe
try {
    initializeStripe();
} catch (Exception $e) {
    error_log("Stripe initialization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Payment system error']);
    exit;
}

try {
    // Handle Payment Intent Creation
    if (isset($data['create_payment_intent'])) {
        $amount = (int)($data['amount'] ?? 0);
        $product = sanitizeInput($data['product'] ?? '', 'string');
        
        // Validate amount
        if ($amount !== PRODUCT_PREMIUM_PRICE && $amount !== PRODUCT_STARTER_PRICE) {
            throw new Exception('Invalid amount');
        }
        
        // Create Payment Intent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => STRIPE_CURRENCY,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => [
                'product' => $product,
            ],
        ]);
        
        echo json_encode([
            'clientSecret' => $paymentIntent->client_secret
        ]);
        exit;
    }
    
    // Handle Payment Method submission (for Apple Pay / Google Pay)
    if (isset($data['payment_method_id'])) {
        $amount = (int)($data['amount'] ?? 0);
        $customerEmail = sanitizeInput($data['customer_email'] ?? '', 'email');
        $product = sanitizeInput($data['product'] ?? '', 'string');
        
        // Validate
        if (!$customerEmail || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email required');
        }
        
        if ($amount !== PRODUCT_PREMIUM_PRICE && $amount !== PRODUCT_STARTER_PRICE) {
            throw new Exception('Invalid amount');
        }
        
        // Create Payment Intent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => STRIPE_CURRENCY,
            'payment_method' => $data['payment_method_id'],
            'confirmation_method' => 'manual',
            'confirm' => true,
            'receipt_email' => $customerEmail,
            'metadata' => [
                'product' => $product,
                'customer_email' => $customerEmail,
            ],
        ]);
        
        // Check if requires additional action
        if ($paymentIntent->status === 'requires_action' || $paymentIntent->status === 'requires_source_action') {
            echo json_encode([
                'requires_action' => true,
                'payment_intent_client_secret' => $paymentIntent->client_secret
            ]);
        } elseif ($paymentIntent->status === 'succeeded') {
            echo json_encode([
                'status' => 'success',
                'payment_intent_id' => $paymentIntent->id
            ]);
        } else {
            throw new Exception('Payment failed');
        }
        exit;
    }
    
    // Handle Payment Intent retrieval
    if (isset($data['payment_intent_id'])) {
        $paymentIntent = \Stripe\PaymentIntent::retrieve($data['payment_intent_id']);
        
        if ($paymentIntent->status === 'succeeded') {
            echo json_encode([
                'status' => 'success',
                'payment_intent_id' => $paymentIntent->id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment not completed'
            ]);
        }
        exit;
    }
    
    // Unknown request
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    
} catch (\Stripe\Exception\CardException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Payment processing error'
    ]);
}
?>