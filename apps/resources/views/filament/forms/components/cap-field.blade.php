@php
    $isConfigured = $isConfigured();
    $apiEndpoint = $getApiEndpoint();
    $statePath = $getStatePath();
@endphp

@if($isConfigured)
    <div
        class="w-full"
        x-data="{
            capToken: null,
            onSolve(event) {
                this.capToken = event.detail.token;
                $wire.set('{{ $statePath }}', this.capToken);
            },
            onError(event) {
                console.error('Cap challenge error:', event.detail.message);
            },
        }"
        x-init="$wire.set('{{ $statePath }}', null)"
    >
        <cap-widget
            data-cap-api-endpoint="{{ $apiEndpoint }}"
            x-on:solve="onSolve"
            x-on:error="onError"
        ></cap-widget>
    </div>
@endif
