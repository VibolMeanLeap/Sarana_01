<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator sign-in verification</title>
</head>

<body style="margin:0;padding:32px 12px;background:#f2f6f2;font-family:Arial,Helvetica,sans-serif;color:#193b2b;">
    <div
        style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2ebe4;border-radius:22px;overflow:hidden;">
        <div style="padding:30px 32px;background:#104d38;color:#ffffff;">
            <div style="font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#b9d8c5;">SK
                Coffee</div>
            <h1 style="margin:13px 0 6px;font-size:25px;line-height:1.25;color:#ffffff;">Confirm it's you</h1>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#d8e9de;">Administrator Google sign-in verification
            </p>
        </div>
        <div style="padding:32px;">
            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">Hello {{ $recipientName }},</p>
            <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#5c6f63;">Use the verification code below to
                finish signing in to your administrator workspace.</p>
            <div
                style="padding:22px 16px;text-align:center;border:1px solid #dcebe0;border-radius:16px;background:#f5faf6;">
                <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#698071;">
                    Your one-time code</div>
                <div
                    style="margin-top:10px;font-size:36px;line-height:1.2;font-weight:800;letter-spacing:10px;color:#176b4b;">
                    {{ $code }}</div>
            </div>
            <p style="margin:20px 0 0;font-size:13px;line-height:1.7;color:#66766b;">This code expires in
                <strong>{{ $expiresInMinutes }} minutes</strong>. You can request a new code from the verification page
                if it expires.</p>
            <div
                style="margin-top:23px;padding-top:19px;border-top:1px solid #edf1ed;font-size:12px;line-height:1.7;color:#7d8a80;">
                Never share this code with anyone. SK Coffee staff will never ask you to send your one-time code through
                Telegram, chat, or a phone call.
            </div>
        </div>
        <div style="padding:16px 32px;background:#f8faf8;font-size:11px;color:#8a978d;">If you did not try to sign in,
            you can ignore this email and consider changing your Google account password.</div>
    </div>
</body>

</html>
