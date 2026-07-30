@extends('admin.layout')

@section('title', 'მხარდაჭერის ჩატები')
@section('heading', 'მხარდაჭერის ჩატები')

@section('content')
<section class="admin-section compact">
    <div class="support-stats">
        @foreach (['waiting_admin' => 'პასუხს ელოდება', 'new' => 'ახალი', 'in_progress' => 'მიმდინარეობს', 'ai_active' => 'AI პასუხობს', 'resolved' => 'დასრულებული'] as $key => $label)
            <a class="support-stat" href="{{ route('admin.support.index', ['status' => $key]) }}"><span>{{ $label }}</span><strong>{{ $statusCounts[$key] ?? 0 }}</strong></a>
        @endforeach
    </div>
</section>

<section class="admin-section compact">
    <form class="support-filter" method="get" action="{{ route('admin.support.index') }}">
        <label><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="სახელი, ნომერი, თემა ან შეტყობინება"></label>
        <label><span>სტატუსი</span><select name="status"><option value="">ყველა სტატუსი</option>@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label><span>რეჟიმი</span><select name="mode"><option value="">AI და ადამიანი</option><option value="ai" @selected(($filters['mode'] ?? '') === 'ai')>Ines AI</option><option value="human" @selected(($filters['mode'] ?? '') === 'human')>ადმინისტრატორი</option></select></label>
        <button class="primary" type="submit">გაფილტვრა</button>
        <a class="text-button" href="{{ route('admin.support.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">{{ $conversations->total() }} საუბარი</p><h2>Ines AI და ადმინისტრატორის inbox</h2></div></div>
    <div class="support-list">
        @forelse($conversations as $conversation)
            @php($lastMessage = $conversation->messages->first())
            <a class="support-row support-priority-{{ $conversation->priority }}" href="{{ route('admin.support.show', $conversation) }}">
                <div>
                    <small>#{{ $conversation->id }} · {{ $conversation->mode === 'ai' ? 'Ines AI' : 'ადმინისტრატორი' }}</small>
                    <h3>{{ $conversation->user?->name ?? $conversation->guest_name ?? 'სტუმარი მომხმარებელი' }}</h3>
                    <small>{{ $conversation->user?->phone ?? $conversation->guest_phone ?? 'ნომერი არ არის მითითებული' }}</small>
                </div>
                <div><strong>{{ $conversation->topic ?? 'ზოგადი კითხვა' }}</strong><p class="support-row-preview">{{ $lastMessage?->body ?? 'შეტყობინება ჯერ არ არის' }}</p></div>
                <div class="support-row-meta"><span class="support-badge {{ $conversation->status }}">{{ $statuses[$conversation->status] ?? $conversation->status }}</span><small>{{ $conversation->last_message_at?->format('d.m.Y H:i') ?? $conversation->created_at->format('d.m.Y H:i') }}</small></div>
            </a>
        @empty
            <div class="support-empty">ამ ფილტრებით საუბარი ვერ მოიძებნა.</div>
        @endforelse
    </div>

    @if($conversations->hasPages())
        <nav class="pagination" aria-label="გვერდები">
            @if($conversations->onFirstPage())<span>← წინა</span>@else<a href="{{ $conversations->previousPageUrl() }}">← წინა</a>@endif
            <strong>{{ $conversations->currentPage() }} / {{ $conversations->lastPage() }}</strong>
            @if($conversations->hasMorePages())<a href="{{ $conversations->nextPageUrl() }}">შემდეგი →</a>@else<span>შემდეგი →</span>@endif
        </nav>
    @endif
</section>
@endsection
