@props(['headers' => []])

<div class="overflow-x-auto bg-white rounded-lg shadow ring-1 ring-black ring-opacity-5">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-ink-500 uppercase tracking-wider font-cairo">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-200 bg-white">
            {{ $slot }}
        </tbody>
    </table>
</div>
