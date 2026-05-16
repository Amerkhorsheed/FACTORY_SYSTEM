@extends('emails.layout')

@section('content')
    <h2>مرحباً {{ $name }}،</h2>
    <p>لقد استلمنا دفعتك بنجاح. شكراً لك!</p>
    
    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bbf7d0;">
        <table style="width: 100%;">
            <tr>
                <td style="color: #4b5563;">المبلغ المستلم:</td>
                <td style="font-weight: bold; text-align: left; color: #166534;">{{ $amount }}</td>
            </tr>
        </table>
    </div>
    
    <p>تم تسجيل هذه الدفعة في حسابك. يمكنك مراجعة الفاتورة المتعلقة بها من خلال الرابط أدناه:</p>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">عرض الفاتورة</a>
    </div>
@endsection
