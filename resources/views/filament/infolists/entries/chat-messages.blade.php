<div class="space-y-3">
    @foreach ($getRecord()->chats()->orderBy('created_at', 'asc')->get() as $message)
        <div class="rounded-lg p-3 {{ $message->is_instruction_sheet ? 'border-l-4 border-green-500 bg-green-50 dark:bg-green-950' : ($message->role === 'user' ? 'bg-gray-100 dark:bg-gray-800' : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700') }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-semibold {{ $message->role === 'user' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ $message->role === 'user' ? 'Buyer' : 'Guide' }}
                </span>
                @if ($message->is_instruction_sheet)
                    <span class="text-xs bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded">Instruction Sheet</span>
                @endif
                <span class="text-xs text-gray-400">
                    {{ $message->created_at->format('M j, Y g:ia') }}
                </span>
                <span class="text-xs text-gray-400">
                    Phase {{ $message->phase ?? '—' }}
                </span>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300 prose prose-sm max-w-none dark:prose-invert">
                @if ($message->role === 'assistant')
                    {!! Str::markdown($message->content) !!}
                @else
                    {{ $message->content }}
                @endif
            </div>
        </div>
    @endforeach

    @if ($getRecord()->chats()->count() === 0)
        <p class="text-sm text-gray-400 italic">No messages yet.</p>
    @endif
</div>
