<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign-in code') }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #111;">
    <p>{{ __('Hello :name,', ['name' => $userName]) }}</p>
    <p>{{ __('Use this code to sign in to your account:') }}</p>
    <p style="font-size: 1.5rem; font-weight: 600; letter-spacing: 0.2em;">{{ $code }}</p>
    <p style="color: #666; font-size: 0.875rem;">{{ __('This code expires in 10 minutes. If you did not request it, you can ignore this email.') }}</p>
</body>
</html>
