<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SK Coffee password reset</title></head>
<body style="margin:0;padding:32px 12px;background:#f3f7f3;font-family:Arial,Helvetica,sans-serif;color:#203128">
    <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e3ece4;border-radius:20px;overflow:hidden">
        <div style="padding:25px 30px;background:#176b4b;color:#fff">
            <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;opacity:.85">SK Coffee</div>
            <div style="font-size:12px;opacity:.85;margin-top:6px">A better coffee experience, made personal.</div>
        </div>
        <div style="padding:30px">
            <p style="font-size:14px;margin:0 0 10px">Hello {{ $customerName }},</p>
            <h1 style="font-size:22px;line-height:1.3;margin:0 0 12px;color:#203128">Reset your password</h1>
            <p style="font-size:14px;line-height:1.7;color:#657269;margin:0 0 22px">Use this one-time code to reset your SK Coffee customer account password.</p>
            <div style="padding:20px 16px;border-radius:14px;background:#f1f8f2;border:1px solid #dcecdf;text-align:center">
                <div style="font-size:10px;font-weight:800;letter-spacing:2px;color:#64766a;text-transform:uppercase">Your password reset code</div>
                <div style="font-size:34px;font-weight:800;letter-spacing:9px;color:#176b4b;margin:12px 0 6px">{{ $otp }}</div>
                <div style="font-size:11px;color:#718075">Expires in {{ $expiresInMinutes }} minutes · One use only</div>
            </div>
            <p style="font-size:12px;line-height:1.7;color:#7a877e;margin:22px 0 0">Never share this code with anyone. SK Coffee staff will never ask you to tell them your verification code. If you did not request this, you can safely ignore this email.</p>
        </div>
        <div style="padding:17px 30px;border-top:1px solid #edf1ed;font-size:10px;color:#98a29a">© {{ date('Y') }} SK Coffee. Automated security email.</div>
    </div>
</body>
</html>
