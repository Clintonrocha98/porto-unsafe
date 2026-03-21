<x-filament-widgets::widget>
    @if($this->batches->isNotEmpty())
        <div class="space-y-4">
            @foreach ($this->batches as $batch)
                @php
                    $progress = $batch->total_jobs > 0 
                        ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100) 
                        : 0;
                    $completedJobs = $batch->total_jobs - $batch->pending_jobs;
                @endphp
                
                <x-filament::section>
                    <div wire:poll.2s class="flex flex-col gap-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $batch->name }}</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $progress }}%</span>
                        </div>
                        
                        <div class="w-full h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-primary-600 transition-all duration-300" style="width: {{ $progress }}%"></div>
                        </div>
                        
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Processando registros: {{ $completedJobs }} de {{ $batch->total_jobs }}</span>
                            @if($batch->failed_jobs > 0)
                                <span class="text-danger-600 font-medium">Falhas: {{ $batch->failed_jobs }}</span>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center p-6 text-center text-gray-500">
            <x-heroicon-o-check-circle class="w-12 h-12 mb-2 text-success-500" />
            <p>Nenhuma extração em andamento.</p>
        </div>
    @endif
</x-filament-widgets::widget>
