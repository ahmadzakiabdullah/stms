# Security Policy

## Supported Versions

STMS follows semantic versioning. Security updates will be provided for the following versions:

| Version | Supported          |
|---------|--------------------|
| 0.1.x   | :white_check_mark: |
| < 0.1.0 | :x:                |

## Reporting a Vulnerability

We take the security of STMS seriously. If you discover a security vulnerability, please follow the guidelines below:

### How to Report

- **Do not** create a public GitHub issue for security vulnerabilities.
- Send an email to **security@stms.example.com** (to be updated with real contact).
- Include as much detail as possible:
  - Description of the vulnerability
  - Steps to reproduce
  - Potential impact
  - Suggested fix (if any)

### Response Timeline

- We will acknowledge receipt of your report within **48 hours**.
- We will provide a more detailed response within **7 working days**.
- We aim to release a fix as soon as possible depending on severity.

### Responsible Disclosure

We appreciate responsible disclosure. If you report a valid security issue, we will:

- Work with you to understand and resolve the issue.
- Credit you in the release notes (unless you prefer to remain anonymous).
- Not take legal action against you for responsibly reporting the vulnerability.

## Security Best Practices

When contributing to STMS, please follow these security practices:

- Never commit sensitive information (API keys, passwords, `.env` files).
- Always validate and sanitize user input.
- Use Laravel’s built-in security features (CSRF, XSS protection, etc.).
- Follow the Principle of Least Privilege when designing roles and permissions.
- Keep dependencies up to date.

## Known Security Considerations

- Multi-tenant data isolation relies heavily on correct application of `organization_id` scoping.
- Role and permission checks must be consistently applied across all modules.

---

If you have any questions regarding security, feel free to contact the maintainers.
