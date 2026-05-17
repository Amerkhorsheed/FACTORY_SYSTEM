@extends('emails.layout')

@section('content')
    <h2>{{ __('notifications.low_stock.subject', ['count' => $products->count()]) }}</h2>
    <p>{{ __('notifications.low_stock.body') }}</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr>
                <th style="text-align: right; border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ __('notifications.low_stock.name') }}</th>
                <th style="text-align: center; border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ __('notifications.low_stock.current') }}</th>
                <th style="text-align: center; border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ __('notifications.low_stock.threshold') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $product->name }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6; text-align: center;">{{ $product->stock_quantity }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6; text-align: center;">{{ $product->low_stock_threshold }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">{{ __('notifications.actions.open_product') }}</a>
    </div>
@endsection
