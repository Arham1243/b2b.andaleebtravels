<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #1a1a2e; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .body h2 { margin-top: 0; color: #1a1a2e; }
        .credentials { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .credentials table { width: 100%; border-collapse: collapse; }
        .credentials td { padding: 8px 0; }
        .credentials td:first-child { font-weight: 600; color: #555; width: 120px; }
        .credentials td:last-child { color: #1a1a2e; font-weight: 500; }
        .btn { display: inline-block; background: #cd1b4f; color: #fff !important; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 10px; }
        .footer { background: #f8f9fa; padding: 20px 30px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
        .note { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px; margin-top: 20px; font-size: 13px; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <h2>Welcome, {{ $vendor->name }}!</h2>
            <p>Your vendor account has been created. Below are your login credentials:</p>

            <div class="credentials">
                <table>
                    <tr>
                        <td>Agent Code</td>
                        <td>{{ $vendor->agent_code }}</td>
                    </tr>
                    <tr>
                        <td>Username</td>
                        <td>{{ $vendor->username }}</td>
                    </tr>
                    <tr>
                        <td>Password</td>
                        <td>{{ $plainPassword }}</td>
                    </tr>
                </table>
            </div>

            <p>Please use the button below to login to your account:</p>
            <a href="{{ route('auth.login') }}" class="btn">Login to Your Account</a>

            <div class="note">
                <strong>Important:</strong> Please change your password after your first login for security purposes.
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
