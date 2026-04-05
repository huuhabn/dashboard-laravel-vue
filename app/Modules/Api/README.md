# API Module (`app/Modules/Api`)

The API module handles all backend logic, RESTful endpoints, security, and authentication mechanisms for the application. Built on Laravel 11+, it is an API-first monolithic boundaries design, fully optimized to be consumed by SPAs (like Vue or React) or mobile clients.

## Architecture & Structure

The module revolves heavily around Domain-Driven boundaries, Strict Typed PHP, and standard Data Transfer Objects (DTOs).

- **Controllers** (`Http/Controllers/Api/`): Thin API endpoints strictly handling request mapping and HTTP JSON responses.
- **Requests** (`Http/Requests/Api/`): FormRequest validations.
- **Services** (`Services/`): Heavy-lifting domain logic (e.g., Token Creation, 2FA Challenge Generation, Social Handling).
- **Repositories** (`Repositories/`): Eloquent abstraction layers to prevent logic leakage down into models over-reaching.
- **Enums** (`Enums/`): Strongly typed configurations (e.g., `SocialProvider`, `LoginMethod`, `OtpDeliverTo`).
- **DTOs** (`DTOs/`): Typed outcomes (like `TokenResult`, `OtpOutcome`) passed seamlessly back and forth between Services and Controllers.

## Authentication System

The application uses **Laravel Sanctum**. Because the SPA expects direct token exchanges instead of CSRF State Cookies, tokens are explicitly issued to the clients as `Bearer tokens`.

| Auth Feature           | Description                                                                                                                                                                |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Credentials**        | Standard Email/Password login.                                                                                                                                             |
| **Passwordless (OTP)** | Deliver One Time Passwords directly to Email or Phone (via Libphonenumber verification).                                                                                   |
| **Two-Factor (2FA)**   | Enforce TOTP challenges seamlessly. Endpoints automatically intercept successful logins and return a `pending_token` rather than a real token if the user has 2FA enabled. |
| **Social (OAuth)**     | Ready-to-go Google and GitHub links via Enum mapped setups. Can be linked/unlinked freely.                                                                                 |

### How Login/Token Flow Works

1. Client POSTs to `/api/v1/auth/token`.
2. Controller maps credentials.
3. `AuthTokenResponseService` validates credentials.
    - If User has 2FA enabled: A dummy payload `['status' => 'pending_challenge', 'two_factor_required' => true, 'pending_token' => 'abc-xyz']` is returned. Client must proceed to `/api/v1/auth/token/two-factor` with this pending token and the generated user TOTP code.
    - If no 2FA: System returns a direct Auth Token.

## Configuration & Enums (SaasAuth)

Most authentication flows can be rapidly configured using `.env` options mapping to `config/services.php` (under `services.saas.auth` config paths). These are consumed using `SaasAuth` helper which uses strict Enums:

- `login_methods`: `password_only`, `otp_only`, `both`.
- `otp_deliver_to`: `email`, `phone`, `both`.

## Development & Test Coverage

All core Domain operations strictly enforce:

- `declare(strict_types=1);` across the board.
- Complete domain specific exception catching (no default `RuntimeExceptions`). Controllers explicitly check for `TwoFactorStateException` or `InvalidTwoFactorCodeException`.
- Complete Unit and E2E API logic covered under the `tests/Feature/Api/` and `tests/Unit/Shared/` directory.

_A complete postman collection containing all mapped endpoints/payloads is available at `app/Modules/Api/postman_collection.json`._
