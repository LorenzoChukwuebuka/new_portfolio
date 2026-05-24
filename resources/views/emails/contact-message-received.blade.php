<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New portfolio message</title>
</head>
<body style="margin:0;padding:32px;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;padding:32px;">
        <p style="margin:0 0 8px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#b45309;">
            Portfolio enquiry
        </p>
        <h1 style="margin:0 0 24px;font-size:24px;">New contact message</h1>

        <p style="margin:0 0 8px;"><strong>From:</strong> {{ $contactMessage->name }}</p>
        <p style="margin:0 0 8px;"><strong>Email:</strong> {{ $contactMessage->email }}</p>
        <p style="margin:0 0 24px;"><strong>Subject:</strong> {{ $contactMessage->subject ?: 'No subject' }}</p>

        <div style="white-space:pre-wrap;background:#f8fafc;border:1px solid #e2e8f0;padding:20px;line-height:1.6;">{{ $contactMessage->message }}</div>

        <p style="margin:24px 0 0;font-size:13px;color:#64748b;">
            Reply directly to this email to respond to {{ $contactMessage->name }}.
        </p>
    </div>
</body>
</html>
