<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Matrix Lead Notification</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 40px;
        }

        .lead-info {
            width: 100%;
            border-collapse: collapse;
        }

        .lead-info td {
            padding: 12px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .lead-info td:first-child {
            width: 160px;
            font-weight: 600;
            color: #4a5568;
        }

        .lead-info td:last-child {
            color: #2d3748;
        }

        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #718096;
            border-top: 1px solid #edf2f7;
        }

        .note-box {
            background-color: #fffaf0;
            border-left: 4px solid #ed8936;
            padding: 15px;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>New Lead Received</h1>
        </div>
        <div class="content">
            <table class="lead-info">
                <tr>
                    <td>Name</td>
                    <td>: {{ $lead->name }}</td>
                </tr>
                <tr>
                    <td>Company Name</td>
                    <td>: {{ $lead->company_name }}</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td>: <a href="mailto:{{ $lead->email }}"
                            style="color: #3182ce; text-decoration: none;">{{ $lead->email }}</a></td>
                </tr>
                <tr>
                    <td>Profile Name</td>
                    <td>: {{ $lead->profile_name }}</td>
                </tr>
                <tr>
                    <td style="vertical-align: top; padding-top: 15px;">Note</td>
                    <td>
                        <div class="note-box">
                            {{ $lead->note }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <p>This is an automated notification from Matrix Platform Management System.</p>
            <p>&copy; {{ date('Y') }} Matrix Platform Ltd. All rights reserved.</p>
        </div>
    </div>
</body>

</html>