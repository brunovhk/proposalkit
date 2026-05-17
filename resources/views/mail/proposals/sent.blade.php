<x-mail::message>
# 📄 {{ __('ui.proposals.emails.sent.greeting', ['client' => $proposal->client->name]) }}

{{ __('ui.proposals.emails.sent.intro') }}

<x-mail::panel>
**{{ __('ui.proposals.emails.fields.title') }}:** {{ $proposal->title }}<br />
**{{ __('ui.proposals.emails.fields.total') }}:** {{ $total }}
</x-mail::panel>

<x-mail::button :url="$publicUrl" color="primary">
{{ __('ui.proposals.emails.sent.button') }}
</x-mail::button>

{{ __('ui.proposals.emails.sent.outro') }}

{{ __('ui.proposals.emails.signature') }} <br />
{{ $proposal->tenant->name }}
</x-mail::message>
