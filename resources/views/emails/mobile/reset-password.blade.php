@component('mail::message')
# {{ __('emails.reset_title') }}

<div style="padding: 20px 0;">
{{ __('emails.reset_greeting', ['name' => $customerName]) }}
</div>

<div style="padding: 20px 0; font-size: 16px; line-height: 1.8;">
{{ __('emails.reset_body') }}
</div>

<div style="padding: 30px 0; text-align: center;">
@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
{{ __('emails.reset_action') }}
@endcomponent
</div>

<div style="padding: 20px 0; font-size: 16px; line-height: 1.8;">
{{ __('emails.reset_expire', ['count' => $expireMinutes]) }}
</div>

<div style="padding: 20px 0; font-size: 14px; color: #666; line-height: 1.6;">
{{ __('emails.reset_footer') }}
</div>

<div style="padding: 30px 0; border-top: 1px solid #e8e8e8; margin-top: 20px;">
<p style="font-size: 14px; color: #888;">
{{ __('emails.reset_help') }}
</p>
<p style="word-break: break-all; font-size: 12px; color: #009900; background: #f5f5f5; padding: 15px; border-radius: 8px;">
{{ $actionUrl }}
</p>
</div>

{{ __('emails.salutation') }}<br>
**{{ config('app.mobile_name') }}**
@endcomponent
