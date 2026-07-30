@extends('admin.layout')

@section('title', 'ჩატი #'.$conversation->id)
@section('heading', 'Ines AI · ჩატი #'.$conversation->id)

@section('content')
<section class="admin-section compact">
    <div class="detail-heading">
        <div><a class="back-link" href="{{ route('admin.support.index') }}">← ყველა ჩატი</a><h2>{{ $conversation->topic ?? 'ზოგადი კითხვა' }}</h2><p>{{ $conversation->user?->name ?? $conversation->guest_name ?? 'სტუმარი მომხმარებელი' }} · {{ $conversation->user?->phone ?? $conversation->guest_phone ?? 'ნომერი არ არის მითითებული' }}</p></div>
        <span class="support-badge {{ $conversation->status }}">{{ $statuses[$conversation->status] ?? $conversation->status }}</span>
    </div>
</section>

<div class="support-shell">
    <section class="support-chat-card">
        <header class="support-chat-head"><div><small>{{ $conversation->mode === 'ai' ? 'Ines AI პასუხობს' : 'ადმინისტრატორის რეჟიმი' }}</small><h2>საუბრის ისტორია</h2></div><span class="support-badge {{ $conversation->status }}">{{ $statuses[$conversation->status] ?? $conversation->status }}</span></header>
        <div class="support-thread" data-support-thread>
            @foreach($conversation->messages as $message)
                <article class="support-thread-message {{ $message->sender_type }} {{ $message->is_internal ? 'internal' : '' }}">
                    <header><span>@switch($message->sender_type) @case('ai') Ines AI @break @case('admin') {{ $message->sender?->name ?? 'ადმინისტრაცია' }} @break @case('system') სისტემა @break @default მომხმარებელი @endswitch</span><time>{{ $message->created_at->format('d.m.Y H:i') }}</time></header>
                    <p>{{ $message->body }}</p>
                    @if($message->sender_type === 'admin')
                        <details><summary>AI-ის ცოდნაში დამატება</summary><form method="post" action="{{ route('admin.support.messages.knowledge', $message) }}">@csrf<div class="knowledge-grid"><input name="title" required placeholder="ცოდნის სათაური"><input name="category" required value="general" placeholder="კატეგორია"></div><button type="submit">დამტკიცებულ ცოდნაში დამატება</button></form></details>
                    @endif
                </article>
            @endforeach
        </div>
        <form class="support-reply" method="post" action="{{ route('admin.support.messages.store', $conversation) }}">
            @csrf
            <textarea name="body" rows="5" maxlength="4000" required data-support-reply placeholder="დაწერეთ ადმინისტრატორის პასუხი…">{{ old('body') }}</textarea>
            <div class="support-reply-actions"><button class="ai-draft" type="button" data-support-draft="{{ route('admin.support.draft', $conversation) }}">✦ AI მონახაზი</button><button type="submit">პასუხის გაგზავნა</button></div>
        </form>
    </section>

    <aside class="support-side">
        <section class="support-side-card">
            <h3>საუბრის მართვა</h3>
            <form class="support-control" method="post" action="{{ route('admin.support.update', $conversation) }}">
                @csrf @method('PATCH')
                <label><span>სტატუსი</span><select name="status">@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($conversation->status === $key)>{{ $label }}</option>@endforeach</select></label>
                <label><span>რეჟიმი</span><select name="mode"><option value="ai" @selected($conversation->mode === 'ai')>Ines AI</option><option value="human" @selected($conversation->mode === 'human')>ადმინისტრატორი</option></select></label>
                <label><span>პრიორიტეტი</span><select name="priority"><option value="low" @selected($conversation->priority === 'low')>დაბალი</option><option value="normal" @selected($conversation->priority === 'normal')>ჩვეულებრივი</option><option value="high" @selected($conversation->priority === 'high')>მაღალი</option><option value="urgent" @selected($conversation->priority === 'urgent')>გადაუდებელი</option></select></label>
                <label><span>პასუხისმგებელი</span><select name="assigned_to_user_id"><option value="">არ არის დანიშნული</option>@foreach($assignableUsers as $user)<option value="{{ $user->id }}" @selected($conversation->assigned_to_user_id === $user->id)>{{ $user->name }} · {{ $user->role }}</option>@endforeach</select></label>
                <button type="submit">ცვლილებების შენახვა</button>
            </form>
        </section>

        <section class="support-side-card">
            <h3>მომხმარებელი</h3>
            <div class="support-contact"><span>სახელი: <strong>{{ $conversation->user?->name ?? $conversation->guest_name ?? '—' }}</strong></span><span>ტელეფონი: <strong>{{ $conversation->user?->phone ?? $conversation->guest_phone ?? '—' }}</strong></span><span>ანგარიში: <strong>{{ $conversation->user ? $conversation->user->membershipLabel() : 'სტუმარი' }}</strong></span><span>საუბარი დაიწყო: <strong>{{ $conversation->created_at->format('d.m.Y H:i') }}</strong></span></div>
        </section>

        <section class="support-side-card">
            <h3>AI-ის ამოცნობილი კონტექსტი</h3>
            @if($conversation->context)
                <ul class="support-context">@foreach($conversation->context as $key => $value)<li><strong>{{ $key }}:</strong> {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</li>@endforeach</ul>
            @else
                <p class="empty-state">კონტექსტი ჯერ არ არის შეგროვებული.</p>
            @endif
        </section>
    </aside>
</div>

<section class="admin-section compact" id="ines-ai-knowledge">
    <div class="panel-heading"><div><p class="eyebrow">ადმინისტრატორის მიერ დამტკიცებული ინფორმაცია</p><h2>Ines AI-ის ცოდნის ბაზა</h2></div></div>
    <div class="knowledge-list">
        @foreach($knowledgeArticles as $article)
            <article class="knowledge-card">
                <form method="post" action="{{ route('admin.support.knowledge.update', $article) }}">@csrf @method('PATCH')<div class="knowledge-grid"><input name="title" value="{{ $article->title }}" required><input name="category" value="{{ $article->category }}" required></div><textarea name="content" rows="4" required>{{ $article->content }}</textarea><label class="knowledge-toggle"><input type="checkbox" name="is_active" value="1" @checked($article->is_active)> აქტიური ცოდნა</label><button type="submit">ცოდნის განახლება</button></form>
                <form class="knowledge-delete" method="post" action="{{ route('admin.support.knowledge.destroy', $article) }}" onsubmit="return confirm('წავშალოთ ეს ცოდნის ჩანაწერი?')">@csrf @method('DELETE')<button type="submit">წაშლა</button></form>
            </article>
        @endforeach
        <article class="knowledge-create"><form method="post" action="{{ route('admin.support.knowledge.store') }}">@csrf<div class="knowledge-grid"><input name="title" required placeholder="ახალი ცოდნის სათაური"><input name="category" required value="general" placeholder="კატეგორია"></div><textarea name="content" rows="4" required placeholder="მხოლოდ ადმინისტრაციის მიერ დადასტურებული ინფორმაცია"></textarea><label class="knowledge-toggle"><input type="checkbox" name="is_active" value="1" checked> დაუყოვნებლივ გააქტიურება</label><button type="submit">ცოდნის დამატება</button></form></article>
    </div>
</section>
<script src="{{ asset('js/support-admin.js') }}" defer></script>
@endsection
