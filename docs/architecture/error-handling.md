# Error Handling

Error handling in STMS follows a layered approach. In the **Service Layer**, all business logic that interacts with external systems or performs complex operations is wrapped in `try/catch` blocks. Caught exceptions are logged via `Log::error()` and re-thrown as custom domain exceptions (e.g., `TournamentRegistrationException`) so that controllers can respond with meaningful HTTP status codes.

Controllers use defensive querying patterns to prevent 500 errors. Before accessing a model's relations or attributes, nullable checks (`?->`) and `findOrFail()` / `find()` are employed. In Inertia response rendering, optional chaining prevents "Call to a member function on null" errors when optional relationships are missing. Form Request validation handles input errors before they reach the controller, returning 422 responses with field-level messages.

Laravel's exception handler in `bootstrap/app.php` maps domain exceptions to appropriate HTTP codes. Unhandled exceptions render a generic error page in production; debug mode is never enabled outside local development. The goal is to surface actionable errors to users without exposing internal state.
