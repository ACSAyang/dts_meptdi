<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Creation Notification</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.7;">
    <div style="max-width: 600px; margin: auto; padding: 24px;">
        <h2>Official Account Creation Notification</h2>

        <p>Dear {{ $username }},</p>

        <p>Greetings!</p>

        <p>
            We are pleased to inform you that your account has been
            successfully created in our system.
        </p>

        <h3>Account Details</h3>

        <p><strong>Username:</strong> {{ $username }}</p>
        <p><strong>Account Status:</strong> Active</p>

        <p>
            <strong>Login Portal:</strong>
            <a href="{{ $loginUrl }}">Access Your Account</a>
        </p>

        <p>
            For security purposes, your password is not included in
            this email. Please use the password you provided during
            registration to access your account.
        </p>

        <p>
            Should you encounter any difficulties accessing your
            account, please contact the system administrator.
        </p>

        <p>
            Thank you, and we look forward to providing you with a
            secure and reliable service.
        </p>

        <p>
            Respectfully,<br>
            <strong>System Administrator</strong>
        </p>
    </div>
</body>

</html>
