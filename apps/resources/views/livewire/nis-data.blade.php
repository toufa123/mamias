<div class="space-y-6">
    @section ('title', 'Data')

    @section ('breadcrumbs')
        {{ Breadcrumbs::render('data') }}
    @endsection

    @php
        $table = $this->getTable();
    @endphp

    <div class="flex justify-end">{{ $this->guideAction }}</div>

    <x-block heading="Filters" icon="tabler-filter">
        <x-slot name="afterHeader">
            {{ $table->getFiltersResetAction()->defaultView($table->getFiltersResetAction()::LINK_VIEW) }}
        </x-slot>

        <div class="[&>.fi-grid]:items-center space-y-4 px-3">
            {{ $this->getTableFiltersForm() }}

            {{ $table->getFiltersApplyAction() }}
        </div>
    </x-block>

    <x-block heading="Non-indigenous species" icon="tabler-list" table> {{ $this->table }} </x-block>
</div>
