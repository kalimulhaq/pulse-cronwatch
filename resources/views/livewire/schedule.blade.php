<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Scheduled Commands"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.clock />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-cronwatch-order-by"
                label="Sort by"
                :options="[
                    'next_due' => 'next due',
                    'failed' => 'failed',
                    'success' => 'success',
                    'avg_duration' => 'avg duration',
                    'last_run' => 'last run',
                ]"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($schedules->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Command</x-pulse::th>
                        <x-pulse::th class="text-right">Cron</x-pulse::th>
                        <x-pulse::th class="text-right">Next due</x-pulse::th>
                        <x-pulse::th class="text-right">Last run</x-pulse::th>
                        <x-pulse::th class="text-right">Avg</x-pulse::th>
                        <x-pulse::th class="text-right">Success</x-pulse::th>
                        <x-pulse::th class="text-right">Failed</x-pulse::th>
                        <x-pulse::th class="text-right">Skipped</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($schedules->take(100) as $row)
                        <tr wire:key="{{ md5($row->command) }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ md5($row->command) }}-row">
                            <x-pulse::td class="max-w-[1px]">
                                <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $row->command }}">
                                    {{ $row->command }}
                                </code>
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 whitespace-nowrap font-mono">
                                @if ($row->cron)
                                    {{ $row->cron }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                @if ($row->next_due)
                                    <span title="{{ $row->next_due }}">
                                        {{ \Carbon\CarbonImmutable::parse($row->next_due)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                @if ($row->last_run)
                                    <span title="{{ $row->last_run }}">
                                        {{ \Carbon\CarbonImmutable::parse($row->last_run)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">Never</span>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($row->avg_duration !== null)
                                    {{ number_format($row->avg_duration) }}<span class="text-gray-400 dark:text-gray-600 text-xs">ms</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                {{ number_format($row->success) }}
                            </x-pulse::td>
                            <x-pulse::td numeric @class([
                                'font-bold',
                                'text-red-600 dark:text-red-400' => $row->failed > 0,
                                'text-gray-700 dark:text-gray-300' => $row->failed === 0,
                            ])>
                                {{ number_format($row->failed) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-500 dark:text-gray-400">
                                {{ number_format($row->skipped) }}
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
