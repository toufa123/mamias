@php
    $isEnabled = $isEnabled();
    $honeypotName = $getHoneypotName();
    $unrandomizedName = $getUnrandomizedName();
    $validFromFieldName = $getValidFromFieldName();
    $validFromTimestamp = $getValidFromTimestamp();
@endphp

@if($isEnabled)
    <div id="{{ $unrandomizedName }}_wrap" style="display: none" aria-hidden="true" x-ignore>
        <input
            id="{{ $honeypotName }}"
            name="{{ $honeypotName }}"
            type="text"
            value=""
            autocomplete="nope"
            tabindex="-1"
            x-data
            wire:model.defer="honeypotData.{{ $unrandomizedName }}"
        />
        <input
            name="{{ $validFromFieldName }}"
            type="text"
            value="{{ $validFromTimestamp }}"
            autocomplete="off"
            tabindex="-1"
            x-data
            wire:model.defer="honeypotData.{{ $validFromFieldName }}"
        />
    </div>
@endif
