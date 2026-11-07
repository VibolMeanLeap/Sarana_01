# ABA PayWay Sandbox + ngrok (Windows)

Use ngrok when Laravel runs only on your own PC. ABA must be able to POST to your callback URL over the public internet. If the application is already hosted on a public HTTPS server, use that server's URL instead; ngrok is unnecessary.

## 1. Start Laravel

From the directory containing `artisan`:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Keep this terminal open. If your project is served by XAMPP Apache on port 80, use that server and tunnel port 80 instead.

## 2. Start ngrok in a second terminal

Install ngrok from https://ngrok.com/download/windows, then add your ngrok authtoken:

```powershell
ngrok config add-authtoken YOUR_NGROK_AUTHTOKEN
```

Forward the Laravel port:

```powershell
ngrok http 8000
```

For XAMPP Apache on port 80:

```powershell
ngrok http 80
```

Copy the HTTPS URL shown by ngrok. Use the exact URL displayed by your own terminal; the sample URL below is not a real host.

## 3. Put the callback URL in the Laravel root `.env`

The `.env` file belongs beside `artisan`, not inside `config/` and not inside the ngrok installation folder.

```dotenv
APP_URL=https://your-assigned-domain.ngrok-free.dev
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=YOUR_ABA_SANDBOX_MERCHANT_ID
PAYWAY_API_KEY=YOUR_ABA_SANDBOX_API_KEY
PAYWAY_CALLBACK_URL=https://your-assigned-domain.ngrok-free.dev/web/payments/payway/callback
PAYWAY_CURRENCY=USD
PAYWAY_PAYMENT_OPTION=abapay_khqr
PAYWAY_LIFETIME=15
PAYWAY_QR_IMAGE_TEMPLATE=template3_color
```

Replace both sample host names with the same HTTPS domain ngrok printed. `APP_URL` is only the base URL. `PAYWAY_CALLBACK_URL` includes the callback path. Never put your merchant API key in a Blade file, JavaScript, screenshot, Git commit, or shared ZIP.

The application reads `PAYWAY_CALLBACK_URL` from `config/services.php`. Both POS KHQR and website KHQR creation use this configured value. PayWay's QR request expects the `callback_url` field; the service Base64-encodes it when building the API request.

## 4. Clear cached Laravel configuration

In a third terminal at the Laravel project root:

```powershell
php artisan optimize:clear
```

Then create a small ABA sandbox KHQR payment from the POS or website. The callback endpoint is:

```text
POST https://your-assigned-domain.ngrok-free.dev/web/payments/payway/callback
```

Do not manually enter that endpoint in phpMyAdmin or a browser address bar. ABA calls it as an HTTP POST. The app still calls ABA's Check Transaction API from the server before marking an order paid.

## 5. Keep both terminals running

Do not close the Laravel server or ngrok while testing. If you stop the tunnel, the public callback URL is unavailable. When you start a new tunnel, check the URL printed by ngrok and update both `.env` URL values if the address changed, then clear config again.

## Troubleshooting

- **QR request fails with wrong domain / code 6:** the PayWay documentation says non-whitelisted domains/IP addresses can receive `6: wrong domain`. Ask ABA PayWay support which sandbox source IP/domain must be allowlisted for your merchant account.
- **QR is created but callback is not received:** check that the ngrok terminal is running, the Laravel server is running, the callback path is exactly `/web/payments/payway/callback`, and `PAYWAY_CALLBACK_URL` matches the current HTTPS ngrok domain.
- **Application still sends localhost:** check spelling in `.env`, make sure there are no duplicate `PAYWAY_CALLBACK_URL` lines, run `php artisan optimize:clear`, and retry.
- **Using XAMPP:** forward port 80 if Apache serves the app there. Do not use `ngrok http 8000` unless Laravel is actually listening on port 8000.

Ngrok exposes the whole local Laravel site through a public URL while the tunnel is open, not only the callback path. Use sandbox credentials and test data, keep debug pages/credentials private, and close the tunnel after testing.
