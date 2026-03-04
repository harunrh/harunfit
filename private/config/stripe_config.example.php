<?php
/**
 * Stripe Configuration Template
 */

// Prevent direct access
if (!defined('HARUNFIT_SECURE')) {
    die('Direct access not permitted');
}

// Stripe API Keys
// Test Keys (for development)
define('STRIPE_TEST_SECRET_KEY', 'sk_test_YOUR_TEST_SECRET_KEY');
define('STRIPE_TEST_PUBLIC_KEY', 'pk_test_YOUR_TEST_PUBLIC_KEY');

// Live Keys (for production)
define('STRIPE_LIVE_SECRET_KEY', 'sk_live_YOUR_LIVE_SECRET_KEY');
define('STRIPE_LIVE_PUBLIC_KEY', 'pk_live_YOUR_LIVE_PUBLIC_KEY');

// Set which keys to use (true = live, false = test)
define('STRIPE_LIVE_MODE', false);

// Get current keys based on mode
define('STRIPE_SECRET_KEY', STRIPE_LIVE_MODE ? STRIPE_LIVE_SECRET_KEY : STRIPE_TEST_SECRET_KEY);
define('STRIPE_PUBLIC_KEY', STRIPE_LIVE_MODE ? STRIPE_LIVE_PUBLIC_KEY : STRIPE_TEST_PUBLIC_KEY);
?>
