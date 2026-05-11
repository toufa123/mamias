<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Database Backup Control
            </x-slot>

            <x-slot name="description">
                Trigger an immediate database backup or restore from an existing snapshot.
            </x-slot>

            <div class="flex flex-wrap gap-4">
                {{ $this->backupAction }}
                {{ $this->restoreAction }}
                {{ $this->fullRestoreAction }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Available Backups
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Filename</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($this->getBackupFiles() as $file)
                            <tr>
                                <td class="px-4 py-2 font-mono text-sm">
                                    {{ $file }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        {{ ($this->downloadAction)(['file' => $file]) }}
                                        {{ ($this->deleteAction)(['file' => $file]) }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-2 text-center text-gray-500">
                                    No backups found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
