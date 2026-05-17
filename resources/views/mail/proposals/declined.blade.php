<x-mail::message>
# ❌ {{ __('ui.proposals.emails.declined.heading') }}

{{ __('ui.proposals.emails.declined.intro', ['client' => $proposal->client->name]) }}

<x-mail::panel>
**{{ __('ui.proposals.emails.fields.title') }}:** {{ $proposal->title }} <br />
**{{ __('ui.proposals.emails.fields.client') }}:** {{ $proposal->client->name }} <br />
**{{ __('ui.proposals.emails.fields.total') }}:** {{ $total }}
</x-mail::panel>

{{ __('ui.proposals.emails.declined.outro') }}

{{ __('ui.proposals.emails.signature') }}<br />
{{ config('app.name') }}
</x-mail::message>
