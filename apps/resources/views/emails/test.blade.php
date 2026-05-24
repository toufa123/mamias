<x-mail::message>
# Welcome to MAMIAS!

This is a test email to verify the new **MAMIAS-branded** email template.

<x-mail::button :url="config('app.url')">
Visit MAMIAS
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
