# Sarana 01 — Google Sign-In overlay patch

This is an **overlay patch** for the existing Sarana 01 Laravel repository that already has the Telegram order workflow patch applied. It is not a new project. Extract its contents into the root of the current repository and replace only the files with matching paths.

## What it adds

- A **Continue with Google** button on the staff login page (administrator and cashier).
- A **Continue with Google** button on the customer login page.
- Google OpenID Connect authorization-code login with state validation, PKCE, nonce validation, and server-side ID-token signature/audience/issuer/expiry verification using Google's official PHP API client.
- One-time account linking from an existing Sarana `users` row using its verified email; subsequent sign-ins use Google's stable `sub` value stored in `users.google_id`.
- Laravel's persistent remember cookie for successful Google sign-ins.

## Account and role rules

- Google Sign-In does **not** auto-create accounts.
- The Google email must be verified and match an existing Sarana account's `users.email` on the first sign-in.
- The local account must have `status = 'active'`.
- The **staff** button accepts only `admin` and `cashier` roles.
- The **customer** button accepts only the `customer` role.
- Google Sign-In never changes a user's local role or password.
- Once linked, the Google `sub` is the persistent identity key. A different Google account cannot silently replace that link.
- Demo emails ending in `.test` are placeholders, not usable Google addresses. Update the intended demo admin/cashier/customer account to the real Google email you will use before testing.

## 1. Back up first

Back up the current project and export the `sarona01` database. Keep the existing `.env` file and `.git` directory. This patch does not include either one.

## 2. Apply the files

Extract this ZIP directly into the root of the existing repository. Allow the included files to replace the same paths. It adds:

- `app/Http/Controllers/GoogleAuthController.php`
- `database/05_google_sign_in.sql`

It updates:

- `app/Models/User.php`
- `config/services.php`
- `routes/web.php`
- `resources/views/admin/login.blade.php`
- `resources/views/website/login/login.blade.php`

## 3. Install Google's PHP verification library

Run from the project root:

```bash
composer require google/apiclient
```

This adds the required package to your existing `composer.json` and `composer.lock`. Do not manually copy a `vendor` folder from another PHP project.

## 4. Apply the database patch

In phpMyAdmin, select the existing `sarona01` database, choose **Import**, and import:

`database/05_google_sign_in.sql`

The script is idempotent on MariaDB 10.4+: it creates `users.google_id` and its unique index only if they are missing. It does not delete or rewrite existing user records.

## 5. Create Google OAuth credentials

1. Open the [Google Cloud Console](https://console.cloud.google.com/).
2. Select or create a project.
3. Open **Google Auth Platform → Branding** and configure your app name, support email, and contact details.
4. Open **Google Auth Platform → Audience**. For personal Gmail accounts, use **External**. While the app is in Testing, add each Google account you intend to test as a test user.
5. Open **Google Auth Platform → Clients → Create client**.
6. Select **Web application**.
7. Add an **Authorized redirect URI** exactly matching the URL below. This server-side flow does not require an Authorized JavaScript origin.
8. Create the client and copy its Client ID and Client Secret.

Official Google instructions:
- [Create OAuth credentials and configure redirect URIs](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Google OpenID Connect and ID-token validation](https://developers.google.com/identity/openid-connect/openid-connect)
- [Google PHP API client](https://github.com/googleapis/google-api-php-client)

## 6. Configure the existing `.env`

Add these lines. Do not commit the real client secret to GitHub:

```dotenv
GOOGLE_CLIENT_ID=your-web-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

For local testing, use the same host and port in your browser and in the redirect URI. If you browse with `http://localhost:8000`, register and configure that exact callback instead of `127.0.0.1`.

For HTTPS/ngrok/production, use the full public HTTPS URL, for example:

```dotenv
APP_URL=https://your-current-public-domain.example
GOOGLE_REDIRECT_URI=https://your-current-public-domain.example/auth/google/callback
```

Add that exact URL to **Authorized redirect URIs** in Google Cloud Console. The scheme, hostname, port, path, and trailing slash must match exactly. Update the URI whenever your temporary ngrok hostname changes.

## 7. Clear configuration cache

```bash
php artisan optimize:clear
```

Confirm the routes are registered:

```bash
php artisan route:list --path=auth/google
```

You should see:

- `GET auth/google/{audience}`
- `GET auth/google/callback`

## 8. Link and test existing accounts

1. In the current admin/staff account management screen, set the target staff account's email to the verified Google email that person controls. Do not create a new staff account for this test.
2. For a customer, make sure their existing customer account has the verified Google email in `users.email` and is active.
3. Visit `/` and click **Continue with Google** to test an admin/cashier account.
4. Visit `/web/login` and click **Continue with Google** to test a customer account.
5. On the first successful Google login, the account's `google_id` is populated. Later sign-ins match by that stable Google ID.

You can verify the link with:

```sql
SELECT user_id, name, email, role_id, status,
       CASE WHEN google_id IS NULL THEN 'Not linked' ELSE 'Linked' END AS google_status
FROM users
ORDER BY user_id;
```

## Troubleshooting

- `redirect_uri_mismatch`: the callback URL in `.env` and Google Cloud Console are not identical.
- `Access blocked / app not verified`: during Testing, add the Google account to the OAuth consent-screen test users.
- `No Sarona account uses this verified Google email`: update the existing account's email to the Google email; this patch intentionally does not auto-create admin, cashier, or customer accounts.
- `This Sarona account is inactive`: reactivate the account through the existing admin controls.
- `Google Sign-In could not be completed`: check `storage/logs/laravel.log`; verify the Client ID/Secret and that `composer require google/apiclient` completed successfully.
- If you changed `.env`, run `php artisan optimize:clear` again.

## Notes

- Google Sign-In is a convenience authentication method; it does not replace application role checks or the existing password login.
- Staff and customer accounts remain separated by their local Sarona role.
- The app requests only `openid`, `email`, and `profile`; it does not request access to Gmail, Drive, Calendar, or other Google services.
- No Google access token or refresh token is saved in Sarona's database.
- Successful Google sign-ins use Laravel's remember-me cookie. The browser can still require sign-in again after cookies are cleared, the account is logged out, or the session/remember token is invalidated.
