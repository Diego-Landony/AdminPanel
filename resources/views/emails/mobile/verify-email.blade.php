@component('mail::message')
# {{ __('emails.verify_title') }}

<div style="padding: 20px 0;">
{{ __('emails.verify_greeting', ['name' => $customerName]) }}
</div>

<div style="padding: 20px 0; font-size: 16px; line-height: 1.8;">
{{ __('emails.verify_body') }}
</div>

<div style="padding: 30px 0; text-align: center;">
@component('mail::button', ['url' => $actionUrl, 'color' => 'success'])
{{ __('emails.verify_action') }}
@endcomponent
</div>

<div style="padding: 20px 0; font-size: 16px; line-height: 1.8;">
{{ __('emails.verify_expire', ['count' => $expireMinutes]) }}
</div>

<div style="padding: 20px 0; font-size: 14px; color: #666; line-height: 1.6;">
{{ __('emails.verify_footer') }}
</div>

<div style="padding: 30px 0; border-top: 1px solid #e8e8e8; margin-top: 20px;">
<p style="font-size: 14px; color: #888;">
{{ __('emails.verify_help') }}
</p>
<p style="word-break: break-all; font-size: 12px; color: #009900; background: #f5f5f5; padding: 15px; border-radius: 8px;">
{{ $actionUrl }}
</p>
</div>

{{ __('emails.salutation') }}<br>
**{{ config('app.mobile_name') }}**
@endcomponent
