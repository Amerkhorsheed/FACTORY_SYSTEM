@extends('emails.layout')

@section('content')
    <h2>مرحباً {{ $name }}،</h2>
    <p>تم تحديث حالة طلبك رقم <strong>{{ $order_number }}</strong>.</p>
    
    <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bfdbfe;">
        <h3 style="margin-top: 0; color: #1e3a8a;">الحالة الجديدة: {{ $status }}</h3>
    </div>
    
    <p>يمكنك متابعة تفاصيل الطلب من خلال الرابط أدناه:</p>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">عرض تفاصيل الطلب</a>
    </div>
@endsection
