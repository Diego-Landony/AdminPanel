@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 30px 0;">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="border-radius: 8px; background-color: #009639;">
<a href="{{ $url }}" target="_blank" rel="noopener" style="display: inline-block; padding: 14px 32px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; background-color: #009639;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
