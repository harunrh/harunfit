document.addEventListener('DOMContentLoaded', () => {
    // Add CSRF token management
    let csrfToken = null;
    
    // Function to get CSRF token from the server
    async function getCsrfToken() {
        try {
            const response = await fetch('../api/get-csrf-token.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Failed to get CSRF token');
            }
            
            const data = await response.json();
            csrfToken = data.csrf_token;
            return csrfToken;
        } catch (error) {
            console.error('Error getting CSRF token:', error);
            return null;
        }
    }

    // Get CSRF token when page loads
    getCsrfToken();

    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const navLinksItems = document.querySelectorAll('.nav-links a');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
            document.body.classList.toggle('no-scroll');
        });

        navLinksItems.forEach(item => {
            item.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
                document.body.classList.remove('no-scroll');
            });
        });
    }

    // Shrink header on scroll
    const header = document.querySelector('.fixed-header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // FAQ accordion
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const answer = question.nextElementSibling;
            faqQuestions.forEach(q => {
                if (q !== question) {
                    q.classList.remove('active');
                    q.nextElementSibling.classList.remove('active');
                }
            });
            question.classList.toggle('active');
            answer.classList.toggle('active');
        });
    });

    // Payment modal variables
    const buyButton = document.getElementById('buy-paid-program');
    const paymentModal = document.getElementById('payment-modal');
    const paymentFormContainer = document.getElementById('payment-form-container');
    const formMessage = document.querySelector('.form-message');
    
    // Coaching form submission with CSRF protection
    const coachingForm = document.getElementById('coaching-form');
    if (coachingForm) {
        // Add hidden CSRF input field
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        coachingForm.appendChild(csrfInput);
        
        // Update CSRF token before submission
        coachingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Make sure we have a valid CSRF token
            if (!csrfToken) {
                try {
                    csrfToken = await getCsrfToken();
                } catch (error) {
                    showModal("Security error. Please refresh the page and try again.");
                    return;
                }
            }
            
            // Set the CSRF token value
            csrfInput.value = csrfToken;
            
            const form = e.target;
            const formData = new FormData(form);
        
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
                .then(data => {
                    if (data.success === true) {
                        if (data.csrf_token) {
                            csrfToken = data.csrf_token;
                        }
                        showModal("Thanks for applying! I'll be in touch soon.");
                        form.reset();
                    } else {
                        showModal(data.message || "There was an error submitting your application. Please try again.");
                    }
                })
            .catch(() => {
                showModal("There was an error submitting your application. Please try again.");
            });
        });
    }
    
    function showModal(message) {
        const modal = document.createElement('div');
        modal.className = 'application-modal';
        modal.innerHTML = `
            <div class="modal-overlay"></div>
            <div class="modal-box">
                <p>${message}</p>
                <button class="modal-close-btn">Close</button>
            </div>
        `;
        document.body.appendChild(modal);
    
        modal.querySelector('.modal-close-btn').onclick = () => {
            document.body.removeChild(modal);
        };
    
        modal.querySelector('.modal-overlay').onclick = () => {
            modal.remove();
        };
    }

    // Reveal animations on scroll
    const revealElements = document.querySelectorAll('.content-section');
    function revealSection() {
        revealElements.forEach(element => {
            if (element.getBoundingClientRect().top < window.innerHeight - 100) {
                element.classList.add('revealed');
            }
        });
    }
    revealElements.forEach(element => element.classList.add('reveal-section'));
    window.addEventListener('scroll', revealSection);
    revealSection();

    // Image slider functionality for mobile
    const photosContainer = document.querySelector('.photo-slider .photos');
    const photos = document.querySelectorAll('.photo-slider .transform-photo');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');

    if (photosContainer && photos.length > 0 && prevBtn && nextBtn) {
        let currentPhoto = 0;
        let slideInterval;
        let userInteracted = false;

        function updateSlider() {
            photosContainer.style.transform = `translateX(-${currentPhoto * 100}%)`;
        }

        function nextPhoto() {
            currentPhoto = (currentPhoto + 1) % photos.length;
            updateSlider();
        }

        function prevPhoto() {
            currentPhoto = (currentPhoto - 1 + photos.length) % photos.length;
            updateSlider();
        }

        function startInterval() {
            slideInterval = setInterval(() => {
                if (!userInteracted) {
                    nextPhoto();
                }
            }, 3000);
        }

        function stopAutoSlide() {
            userInteracted = true;
            clearInterval(slideInterval);
        }

        nextBtn.addEventListener('click', () => {
            nextPhoto();
            stopAutoSlide();
        });

        prevBtn.addEventListener('click', () => {
            prevPhoto();
            stopAutoSlide();
        });

        updateSlider();
        startInterval();
        window.addEventListener('resize', updateSlider);
    }

    // Check for form submission status in URL
    const urlParams = new URLSearchParams(window.location.search);
    const submissionStatus = urlParams.get('submission');
    
    if (formMessage && submissionStatus) {
        if (submissionStatus === 'success') {
            formMessage.textContent = 'Thanks for applying! I\'ll be in touch with you soon.';
            formMessage.style.display = 'block';
            formMessage.className = 'form-message success';
        } else if (submissionStatus === 'error') {
            formMessage.textContent = 'There was an error submitting your application. Please try again.';
            formMessage.style.display = 'block';
            formMessage.className = 'form-message error';
        }
        
        if (window.history && window.history.replaceState) {
            const newUrl = window.location.href.split('?')[0] + window.location.hash;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }

    // Helper function to validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // --- Stripe Payment Integration ---
    if (typeof Stripe !== 'undefined') {
        const stripePublicKey = 'pk_test_51QQ9lwFNX6aUkvbomavlEOiZHypFq3OWpnLJV6hP1n6x5eKaHeQBFIXX0uWTtLU27fhNyrtNPNSFEE9vduZE6Wbr00OGeWFQpr';        
        const stripe = Stripe(stripePublicKey);
        
        // PREMIUM PROGRAM (£25) PAYMENT
        let elements;
        let paymentElement;
        let paymentRequest;
        
        async function mountStripeElements() {
            if (!elements) {
                const formHtml = `
                    <div class="payment-methods-container">
                        <div class="payment-header">
                            <h3>Checkout</h3>
                            <div class="payment-amount">£25.00</div>
                        </div>
                        
                        <div class="email-collection-form">
                            <label for="customer-email">Email address (for program delivery)</label>
                            <input type="email" id="customer-email" required placeholder="youremail@example.com">
                        </div>
                        
                        <div id="payment-request-button"></div>
                        <div class="express-payment-separator">
                            <span>or pay with card</span>
                        </div>
                        
                        <form id="payment-form">
                            <div id="payment-element"></div>
                            <div id="card-errors" class="payment-error"></div>
                            <button type="submit" id="submit-payment" class="payment-button">
                                <span class="spinner hidden" id="spinner"></span>
                                <span id="button-text">Pay £25.00</span>
                            </button>
                        </form>
                        
                        <div class="payment-footer">
                            <div class="secure-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                Secure payment
                            </div>
                        </div>
                    </div>
                `;
                
                paymentFormContainer.innerHTML = formHtml;
                
                try {
                    const currentToken = csrfToken || await getCsrfToken();
                    const response = await fetch('../api/payment_process.php', {
                        method: 'POST', 
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': currentToken
                        },
                        body: JSON.stringify({
                            create_payment_intent: true,
                            amount: 2500,
                            product: 'Complete Transformation Program'
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to create payment intent');
                    }
                    
                    const { clientSecret } = await response.json();
                    
                    if (!clientSecret) {
                        throw new Error('No client secret received');
                    }
                    
                    paymentRequest = stripe.paymentRequest({
                        country: 'GB',
                        currency: 'gbp',
                        total: {
                            label: 'Complete Transformation Program',
                            amount: 2500,
                        },
                        requestPayerName: true,
                        requestPayerEmail: true,
                    });

                    const paymentRequestSupported = await paymentRequest.canMakePayment();
                    const prButton = document.getElementById('payment-request-button');
                    
                    if (paymentRequestSupported) {
                        const prElement = stripe.elements().create('paymentRequestButton', {
                            paymentRequest: paymentRequest,
                            style: {
                                paymentRequestButton: {
                                    type: 'buy',
                                    theme: 'dark',
                                    height: '48px'
                                },
                            },
                        });
                        
                        prElement.mount('#payment-request-button');
                        
                        paymentRequest.on('paymentmethod', async function(ev) {
                            const customerEmail = document.getElementById('customer-email').value;
                            if (!customerEmail || !isValidEmail(customerEmail)) {
                                document.getElementById('card-errors').textContent = 'Please enter a valid email address for program delivery.';
                                ev.complete('fail');
                                return;
                            }
                            
                            try {
                                const response = await fetch('../api/payment_process.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-Token': currentToken
                                    },
                                    body: JSON.stringify({
                                        payment_method_id: ev.paymentMethod.id,
                                        amount: 2500,
                                        customer_email: customerEmail,
                                        product: 'Complete Transformation Program'
                                    })
                                });
                                
                                const result = await response.json();
                                
                                if (result.requires_action) {
                                    const { error, paymentIntent } = await stripe.confirmCardPayment(
                                        result.payment_intent_client_secret,
                                        { payment_method: ev.paymentMethod.id }
                                    );
                                    
                                    if (error) {
                                        ev.complete('fail');
                                        document.getElementById('card-errors').textContent = error.message;
                                    } else {
                                        ev.complete('success');
                                        paymentSuccess(customerEmail, 'PremiumTrainingProgram.pdf');
                                    }
                                } else if (result.status === 'success') {
                                    ev.complete('success');
                                    paymentSuccess(customerEmail, 'PremiumTrainingProgram.pdf');
                                } else {
                                    ev.complete('fail');
                                    document.getElementById('card-errors').textContent = result.message || 'Payment failed';
                                }
                            } catch (error) {
                                console.error('Error processing payment:', error);
                                ev.complete('fail');
                                document.getElementById('card-errors').textContent = 'Payment processing error. Please try again.';
                            }
                        });
                    } else {
                        prButton.style.display = 'none';
                        document.querySelector('.express-payment-separator').style.display = 'none';
                    }
                    
                    elements = stripe.elements({
                        clientSecret,
                        appearance: {
                            theme: 'stripe',
                            variables: {
                                colorPrimary: '#000000',
                                colorBackground: '#ffffff',
                                colorText: '#333333',
                                colorDanger: '#df1b41',
                                fontFamily: 'Montserrat, system-ui, sans-serif',
                                spacingUnit: '4px',
                                borderRadius: '4px',
                            }
                        }
                    });
                    
                    paymentElement = elements.create('payment', {
                        layout: {
                            type: 'accordion',
                            defaultCollapsed: false,
                            radios: true,
                            spacedAccordionItems: true
                        }
                    });
                    
                    paymentElement.mount('#payment-element');
                    
                    const paymentForm = document.getElementById('payment-form');
                    
                    paymentForm.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        
                        const customerEmail = document.getElementById('customer-email').value;
                        if (!customerEmail || !isValidEmail(customerEmail)) {
                            document.getElementById('card-errors').textContent = 'Please enter a valid email address for program delivery.';
                            return;
                        }
                        
                        setLoading(true);
                        
                        const { error } = await stripe.confirmPayment({
                            elements,
                            confirmParams: {
                                return_url: `${window.location.origin}/harunfit-local/public/payment_success.php?email=${encodeURIComponent(customerEmail)}&product=premium`,                            },
                            redirect: 'if_required',
                        });
                        
                        if (error) {
                            const errorElement = document.getElementById('card-errors');
                            errorElement.textContent = error.message || 'Payment failed. Please try again.';
                            setLoading(false);
                        } else {
                            paymentSuccess(customerEmail, 'PremiumTrainingProgram.pdf');
                        }
                    });
                } catch (error) {
                    console.error('Error setting up payment:', error);
                    paymentFormContainer.innerHTML = `
                        <div class="payment-error-container">
                            <p>There was an error setting up the payment system. Please try again later or contact support.</p>
                            <button class="payment-button" onclick="window.location.reload()">Try Again</button>
                        </div>
                    `;
                }
            }
        }
        
        function setLoading(isLoading) {
            const submitButton = document.getElementById('submit-payment');
            const spinner = document.getElementById('spinner');
            const buttonText = document.getElementById('button-text');
            
            if (isLoading) {
                submitButton.disabled = true;
                spinner.classList.remove('hidden');
                buttonText.classList.add('hidden');
            } else {
                submitButton.disabled = false;
                spinner.classList.add('hidden');
                buttonText.classList.remove('hidden');
            }
        }
        
        function paymentSuccess(customerEmail = '', filename = 'PremiumTrainingProgram.pdf') {
            const container = filename === 'StarterProgram.pdf' ? 
                document.getElementById('starter-payment-form-container') : 
                paymentFormContainer;
                
            container.innerHTML = `
                <div class="payment-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#43a047" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <h3>Payment Successful!</h3>
                    <p>Thank you for your purchase. Your transformation journey starts now!</p>
                    ${customerEmail ? `<p class="email-notice">I've sent your program to: <strong>${customerEmail}</strong><br>If you don't see the email in your inbox, please check your spam folder.</p>` : ''}
                    <a href="secure_download.php?file=${filename}&token=direct_download${customerEmail ? '&email=' + encodeURIComponent(customerEmail) : ''}" class="download-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Download Your Program
                    </a>
                </div>
            `;
        }
        
        // Show premium modal
        buyButton?.addEventListener('click', () => {
            paymentModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            mountStripeElements();
        });
        
        // STARTER PROGRAM (£5.99) PAYMENT
        const buyStarterButton = document.getElementById('buy-starter-program');
        const starterPaymentModal = document.getElementById('starter-payment-modal');
        const starterPaymentFormContainer = document.getElementById('starter-payment-form-container');
        let starterElements;
        let starterPaymentElement;
        let starterPaymentRequest;
        
        async function mountStarterStripeElements() {
            if (!starterElements) {
                const formHtml = `
                    <div class="payment-methods-container">
                        <div class="payment-header">
                            <h3>Checkout</h3>
                            <div class="payment-amount">£5.99</div>
                        </div>
                        
                        <div class="email-collection-form">
                            <label for="starter-customer-email">Email address (for program delivery)</label>
                            <input type="email" id="starter-customer-email" required placeholder="youremail@example.com">
                        </div>
                        
                        <div id="starter-payment-request-button"></div>
                        <div class="express-payment-separator starter-separator">
                            <span>or pay with card</span>
                        </div>
                        
                        <form id="starter-payment-form">
                            <div id="starter-payment-element"></div>
                            <div id="starter-card-errors" class="payment-error"></div>
                            <button type="submit" id="submit-starter-payment" class="payment-button">
                                <span class="spinner hidden" id="starter-spinner"></span>
                                <span id="starter-button-text">Pay £5.99</span>
                            </button>
                        </form>
                        
                        <div class="payment-footer">
                            <div class="secure-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                Secure payment
                            </div>
                        </div>
                    </div>
                `;
                
                starterPaymentFormContainer.innerHTML = formHtml;
                
                try {
                    const currentToken = csrfToken || await getCsrfToken();
                    const response = await fetch('../api/payment_process.php', {
                        method: 'POST', 
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': currentToken
                        },
                        body: JSON.stringify({
                            create_payment_intent: true,
                            amount: 599,
                            product: 'Starter Program'
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to create payment intent');
                    }
                    
                    const { clientSecret } = await response.json();
                    
                    if (!clientSecret) {
                        throw new Error('No client secret received');
                    }
                    
                    starterPaymentRequest = stripe.paymentRequest({
                        country: 'GB',
                        currency: 'gbp',
                        total: {
                            label: 'Starter Program',
                            amount: 599,
                        },
                        requestPayerName: true,
                        requestPayerEmail: true,
                    });

                    const paymentRequestSupported = await starterPaymentRequest.canMakePayment();
                    const prButton = document.getElementById('starter-payment-request-button');
                    
                    if (paymentRequestSupported) {
                        const prElement = stripe.elements().create('paymentRequestButton', {
                            paymentRequest: starterPaymentRequest,
                            style: {
                                paymentRequestButton: {
                                    type: 'buy',
                                    theme: 'dark',
                                    height: '48px'
                                },
                            },
                        });
                        
                        prElement.mount('#starter-payment-request-button');
                        
                        starterPaymentRequest.on('paymentmethod', async function(ev) {
                            const customerEmail = document.getElementById('starter-customer-email').value;
                            if (!customerEmail || !isValidEmail(customerEmail)) {
                                document.getElementById('starter-card-errors').textContent = 'Please enter a valid email address for program delivery.';
                                ev.complete('fail');
                                return;
                            }
                            
                            try {
                                const response = await fetch('../api/payment_process.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-Token': currentToken
                                    },
                                    body: JSON.stringify({
                                        payment_method_id: ev.paymentMethod.id,
                                        amount: 599,
                                        customer_email: customerEmail,
                                        product: 'Starter Program'
                                    })
                                });
                                
                                const result = await response.json();
                                
                                if (result.requires_action) {
                                    const { error, paymentIntent } = await stripe.confirmCardPayment(
                                        result.payment_intent_client_secret,
                                        { payment_method: ev.paymentMethod.id }
                                    );
                                    
                                    if (error) {
                                        ev.complete('fail');
                                        document.getElementById('starter-card-errors').textContent = error.message;
                                    } else {
                                        ev.complete('success');
                                        paymentSuccess(customerEmail, 'StarterProgram.pdf');
                                    }
                                } else if (result.status === 'success') {
                                    ev.complete('success');
                                    paymentSuccess(customerEmail, 'StarterProgram.pdf');
                                } else {
                                    ev.complete('fail');
                                    document.getElementById('starter-card-errors').textContent = result.message || 'Payment failed';
                                }
                            } catch (error) {
                                console.error('Error processing payment:', error);
                                ev.complete('fail');
                                document.getElementById('starter-card-errors').textContent = 'Payment processing error. Please try again.';
                            }
                        });
                    } else {
                        prButton.style.display = 'none';
                        document.querySelector('.starter-separator').style.display = 'none';
                    }
                    
                    starterElements = stripe.elements({
                        clientSecret,
                        appearance: {
                            theme: 'stripe',
                            variables: {
                                colorPrimary: '#000000',
                                colorBackground: '#ffffff',
                                colorText: '#333333',
                                colorDanger: '#df1b41',
                                fontFamily: 'Montserrat, system-ui, sans-serif',
                                spacingUnit: '4px',
                                borderRadius: '4px',
                            }
                        }
                    });
                    
                    starterPaymentElement = starterElements.create('payment', {
                        layout: {
                            type: 'accordion',
                            defaultCollapsed: false,
                            radios: true,
                            spacedAccordionItems: true
                        }
                    });
                    
                    starterPaymentElement.mount('#starter-payment-element');
                    
                    const starterPaymentForm = document.getElementById('starter-payment-form');
                    
                    starterPaymentForm.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        
                        const customerEmail = document.getElementById('starter-customer-email').value;
                        if (!customerEmail || !isValidEmail(customerEmail)) {
                            document.getElementById('starter-card-errors').textContent = 'Please enter a valid email address for program delivery.';
                            return;
                        }
                        
                        setStarterLoading(true);
                        
                        const { error } = await stripe.confirmPayment({
                            elements: starterElements,
                            confirmParams: {
                                return_url: `${window.location.origin}/harunfit-local/public/payment_success.php?email=${encodeURIComponent(customerEmail)}&product=starter`,                            },
                            redirect: 'if_required',
                        });
                        
                        if (error) {
                            const errorElement = document.getElementById('starter-card-errors');
                            errorElement.textContent = error.message || 'Payment failed. Please try again.';
                            setStarterLoading(false);
                        } else {
                            paymentSuccess(customerEmail, 'StarterProgram.pdf');
                        }
                    });
                } catch (error) {
                    console.error('Error setting up payment:', error);
                    starterPaymentFormContainer.innerHTML = `
                        <div class="payment-error-container">
                            <p>There was an error setting up the payment system. Please try again later or contact support.</p>
                            <button class="payment-button" onclick="window.location.reload()">Try Again</button>
                        </div>
                    `;
                }
            }
        }
        
        function setStarterLoading(isLoading) {
            const submitButton = document.getElementById('submit-starter-payment');
            const spinner = document.getElementById('starter-spinner');
            const buttonText = document.getElementById('starter-button-text');
            
            if (isLoading) {
                submitButton.disabled = true;
                spinner.classList.remove('hidden');
                buttonText.classList.add('hidden');
            } else {
                submitButton.disabled = false;
                spinner.classList.add('hidden');
                buttonText.classList.remove('hidden');
            }
        }
        
        // Show starter modal
        buyStarterButton?.addEventListener('click', () => {
            starterPaymentModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            mountStarterStripeElements();
        });
        
        // Close modals using the close button
        document.querySelectorAll('.close-modal').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                const modal = closeBtn.closest('.modal');
                if (modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }
});