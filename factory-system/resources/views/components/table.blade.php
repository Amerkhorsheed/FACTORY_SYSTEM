@props(['headers' => []])

<div class="table-scroll">
    <table {{ $attributes->merge(['class' => 'table']) }}>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th scope="col">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
