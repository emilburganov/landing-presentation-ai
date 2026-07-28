<x-mail::message>
# Мы получили ваше обращение

Здравствуйте, {{ $contact->name }}!

Это копия вашего сообщения:

**Имя:** {{ $contact->name }}  
**Email:** {{ $contact->email }}  
**Телефон:** {{ $contact->phone }}

**Сообщение:**  
{{ $contact->comment }}

@if($analysis->autoReply)
---

{{ $analysis->autoReply }}
@endif

Мы свяжемся с вами в ближайшее время.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
