# HarunFit Website

A secure web application for fitness with integrated payment processing.

HarunFit allows users to purchase digital training programs (£5.99 Standard, £25 Premium) via Stripe and submit coaching applications. The platform was developed with a security-first approach, implementing OWASP Top 10 protections including CSRF tokens, SQL injection prevention using prepared statements, XSS mitigation through input sanitization, and secure session management. All payment processing is handled securely through Stripe Elements, ensuring sensitive card data never touches the server.

**Live Demo**: [harunfit.com](https://harunfit.com)
## Features

- Stripe Payment Integration (Standard £5.99 & Premium £25 programs)
- Security: CSRF protection, XSS prevention, SQL injection protection, rate limiting
- Admin Dashboard with coaching application tracking
- Email delivery of digital products
- Fully responsive design
- Modern UI with custom styling

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 8.2
- **Database:** MySQL
- **Payment:** Stripe API
- **Security:** Custom implementation (CSRF tokens, prepared statements, input sanitization)


### Prerequisites

- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx web server
- Composer (optional, for dependencies)


## Security Features

- CSRF token validation on all forms
- Prepared statements for SQL queries
- XSS prevention with input sanitization and output escaping
- Rate limiting on sensitive endpoints
- Security event logging
- Secure session management

## Project Structure
```
harunfit/
├── public/              # Public web root
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── images/         # Images and assets
│   └── index.html      # Homepage
├── private/            # Private files (not web accessible)
│   ├── config/         # Configuration files
│   ├── includes/       # PHP includes
│   ├── logs/           # Security logs
│   └── products/       # Digital products (PDFs)
├── admin/              # Admin dashboard
├── api/                # API endpoints
└── database/           # Database schemas
```

## License

© 2026 HarunFit. All rights reserved.

## Author

Created by Harun Rahahla
