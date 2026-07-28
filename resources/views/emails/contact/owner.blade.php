<x-mail::message>
# Новое обращение

Поступило новое сообщение с формы обратной связи.

**Имя:** {{ $contact->name }}  
**Email:** {{ $contact->email }}  
**Телефон:** {{ $contact->phone }}

**Сообщение:**  
{{ $contact->comment }}

---

**Анализ:** {{ $analysis->sentiment }} / {{ $analysis->type }}  
@if($analysis->autoReply)
**Автоответ:** {{ $analysis->autoReply }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
