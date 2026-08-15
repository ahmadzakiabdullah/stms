## 2024-05-24 - Missing Rate Limiting on Unauthenticated POST routes
**Vulnerability:** The `/register` and `/forgot-password` POST routes in `routes/auth.php` did not have rate limiting middleware applied.
**Learning:** These endpoints are susceptible to brute force attacks, email spam, and user enumeration because Laravel's default scaffolding doesn't always automatically apply the `throttle` middleware to all sensitive auth routes, specifically custom ones or if omitted during setup.
**Prevention:** Always verify that endpoints performing sensitive operations or triggering external systems (e.g., sending emails for password resets) are protected by a throttle middleware (like `throttle:6,1`) to prevent spamming and user enumeration.
