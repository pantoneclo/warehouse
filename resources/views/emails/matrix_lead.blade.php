<!DOCTYPE html>
<html>

<head>
    <title>New Matrix Lead</title>
</head>

<body>
    <h2>New Lead Information</h2>
    <p><strong>Name:</strong> {{ $lead->name }}</p>
    <p><strong>Company Name:</strong> {{ $lead->company_name }}</p>
    <p><strong>Email:</strong> {{ $lead->email }}</p>
    <p><strong>Profile Name:</strong> {{ $lead->profile_name }}</p>
    <p><strong>Note:</strong> {{ $lead->note }}</p>
    <p><strong>File Name:</strong> {{ $lead->file_name }}</p>
    <p><strong>Status:</strong> {{ $lead->status }}</p>
</body>

</html>