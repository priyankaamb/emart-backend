
<x-mail::message>
<table style="width: 100%; max-width: 600px; margin: auto; border-radius: 8px; font-family: Arial, sans-serif; background-color: #f9f9f9;">
    <!-- <tr>
        <td style="text-align: center; padding: 20px;">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="max-width: 150px;">
        </td>
    </tr> -->
    <tr>
        <td style="padding: 30px; background-color: #ffffff; border-radius: 8px;">
            <h2 style="color: #333; text-align: center;">Verify Your Email Address</h2>
            <p style="color: #555; text-align: center; font-size: 16px;">
                Thank you for signing up! Please confirm your email address by clicking the button below.
            </p>
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $actionUrl }}" 
                   style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                    Verify Email
                </a>
            </div>
            <p style="color: #777; text-align: center; font-size: 14px;">
                If you did not create an account, no further action is required.
            </p>
        </td>
    </tr>
    <tr>
        <td style="text-align: center; padding: 20px; font-size: 12px; color: #888;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </td>
    </tr>
</table>
</x-mail::message>
