@extends('emails.layout')

@section('content')
    <h2 style="color: #dc2626;">تنبيه: فاتورة متأخرة الدفع</h2>
    <p>مرحباً {{ $name }}،</p>
    <p>نود تذكيركم بوجود فاتورة مستحقة لم يتم سدادها بعد (رقم <strong>{{ $invoice_number }}</strong>).</p>
    
    <div style="background-color: #fef2f2; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #fecaca;">
        <table style="width: 100%;">
            <tr>
                <td style="color: #4b5563; padding: 5px 0;">تاريخ الاستحقاق:</td>
                <td style="font-weight: bold; text-align: left; color: #991b1b;">{{ $due_date }}</td>
            </tr>
            <tr>
                <td style="color: #4b5563; padding: 5px 0;">المبلغ المتبقي:</td>
                <td style="font-weight: bold; text-align: left; color: #991b1b;">{{ $balance }}</td>
            </tr>
        </table>
    </div>
    
    <p>يرجى ترتيب عملية الدفع في أقرب وقت ممكن. تجاهل هذه الرسالة في حال تم السداد مؤخراً.</p>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn" style="background-color: #dc2626;">عرض الفاتورة</a>
    </div>
@endsection
