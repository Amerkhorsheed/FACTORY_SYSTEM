@extends('emails.layout')

@section('content')
    <h2>مرحباً {{ $name }}،</h2>
    <p>تم إصدار فاتورة جديدة برقم <strong>{{ $invoice_number }}</strong>.</p>
    
    <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bfdbfe;">
        <table style="width: 100%;">
            <tr>
                <td style="color: #4b5563;">إجمالي الفاتورة:</td>
                <td style="font-weight: bold; text-align: left;">{{ $total }}</td>
            </tr>
        </table>
    </div>
    
    <p>يمكنك تحميل وعرض الفاتورة من خلال الرابط أدناه:</p>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">عرض الفاتورة</a>
    </div>
@endsection
