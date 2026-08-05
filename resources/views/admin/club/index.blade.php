@extends('admin.layout')

@section('title', 'მშობელთა კლუბის მართვა')
@section('heading', 'მშობელთა კლუბის მართვა')

@section('content')
<section class="club-admin-metrics" aria-label="კლუბის მთავარი მაჩვენებლები">
    <article><span>აქტიური მშობლები</span><strong>{{ $metrics['active_parents'] }}</strong><small>კლუბზე წვდომით</small></article>
    <article class="{{ $metrics['unanswered_topics'] ? 'attention' : '' }}"><span>უპასუხო კითხვები</span><strong>{{ $metrics['unanswered_topics'] }}</strong><small>ადმინისტრაციის პასუხს ელოდება</small></article>
    <article><span>მომავალი ღონისძიებები</span><strong>{{ $metrics['upcoming_events'] }}</strong><small>გამოქვეყნებული</small></article>
    <article><span>დადასტურებული მონაწილეობა</span><strong>{{ $metrics['going_responses'] }}</strong><small>ოჯახი მოდის</small></article>
</section>

<section class="club-admin-grid">
    <article class="club-admin-panel club-admin-create-event">
        <div class="club-admin-heading"><div><small>კალენდარი</small><h2>ახალი ღონისძიება</h2><p>ღონისძიება შეგიძლიათ გაუგზავნოთ ყველა მშობელს ან კონკრეტულ ჯგუფს.</p></div></div>
        <form method="post" action="{{ route('admin.club.events.store') }}" class="club-admin-form">
            @csrf
            <label><span>ვისთვის არის</span><select name="kindergarten_group_id"><option value="">ყველა მშობელი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(old('kindergarten_group_id') == $group->id)>{{ $group->name }} · {{ $group->academic_year }}</option>@endforeach</select></label>
            <label class="wide"><span>სათაური</span><input name="title" value="{{ old('title') }}" required minlength="3" maxlength="180" placeholder="მაგალითად: ოჯახური სპორტული დღე"></label>
            <label class="wide"><span>აღწერა</span><textarea name="description" rows="4" required minlength="5" maxlength="5000" placeholder="რა მოხდება, რა უნდა იქონიონ მშობლებმა და სხვა მნიშვნელოვანი დეტალები">{{ old('description') }}</textarea></label>
            <label><span>დაწყება</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required></label>
            <label><span>დასრულება</span><input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></label>
            <label><span>ადგილმდებარეობა</span><input name="location" value="{{ old('location') }}" maxlength="180" placeholder="ინეს ბაღის ეზო"></label>
            <label><span>ადგილების რაოდენობა</span><input type="number" name="capacity" value="{{ old('capacity') }}" min="1" max="10000" placeholder="შეზღუდვის გარეშე"></label>
            <label><span>სტატუსი</span><select name="status" required>@foreach(\App\Models\ClubEvent::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', 'published') === $value)>{{ $label }}</option>@endforeach</select></label>
            <input type="hidden" name="is_featured" value="0">
            <label class="club-admin-check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))><span><strong>მნიშვნელოვან ღონისძიებად მონიშვნა</strong><small>მშობლის კაბინეტში პირველ რიგში გამოჩნდება.</small></span></label>
            <button class="club-admin-primary wide" type="submit">ღონისძიების შექმნა</button>
        </form>
    </article>

    <aside class="club-admin-panel club-admin-guide">
        <small>ჭკვიანი მართვა</small><h2>როგორ მუშაობს</h2>
        <ol><li><strong>მშობელი სვამს კითხვას</strong><span>კითხვა ავტომატურად ხვდება პასუხის მოლოდინის სიაში.</span></li><li><strong>ადმინისტრაცია პასუხობს</strong><span>პასუხი ოფიციალურად ინიშნება და მშობელს პირად კაბინეტში მისდის შეტყობინება.</span></li><li><strong>ღონისძიება ქვეყნდება</strong><span>მხოლოდ შესაბამისი ჯგუფის მშობლები ხედავენ და აფიქსირებენ მონაწილეობას.</span></li><li><strong>ადმინისტრატორი ხედავს შედეგს</strong><span>რამდენი ოჯახი მოვა, ვის აქვს უპასუხო კითხვა და რომელი ინფორმაციაა მნიშვნელოვანი.</span></li></ol>
    </aside>
</section>

<section class="club-admin-panel club-admin-events">
    <div class="club-admin-heading"><div><small>ღონისძიებების მართვა</small><h2>კალენდარი და მონაწილეობა</h2><p>შეცვალეთ დეტალები, ხელახლა გაუგზავნეთ შეტყობინება ან ნახეთ მონაწილეთა პასუხები.</p></div></div>
    <div class="club-admin-event-list">
        @forelse($events as $event)
            <article class="club-event-admin-card {{ $event->is_featured ? 'featured' : '' }}">
                <header><div><span>{{ $event->audienceLabel() }}</span><h3>{{ $event->title }}</h3><small>{{ $event->starts_at?->format('d.m.Y H:i') }}{{ $event->location ? ' · '.$event->location : '' }}</small></div><b class="status-{{ $event->status }}">{{ \App\Models\ClubEvent::STATUSES[$event->status] ?? $event->status }}</b></header>
                <div class="club-event-response-summary"><span><strong>{{ $event->going_count }}</strong> მოვალთ</span><span><strong>{{ $event->maybe_count }}</strong> ჯერ არ ვიცით</span><span><strong>{{ $event->not_going_count }}</strong> ვერ მოვალთ</span><span><strong>{{ $event->responses_count }}</strong> სულ პასუხი</span></div>
                @if($event->responses->isNotEmpty())<details class="club-response-people"><summary>მშობლების პასუხების ნახვა</summary><div>@foreach($event->responses as $response)<span><strong>{{ $response->user?->name }}</strong> · {{ \App\Models\ClubEvent::RESPONSE_STATUSES[$response->status] ?? $response->status }}</span>@endforeach</div></details>@endif
                <details class="club-event-edit"><summary>ღონისძიების რედაქტირება</summary>
                    <form method="post" action="{{ route('admin.club.events.update', $event) }}" class="club-admin-form">
                        @csrf @method('PATCH')
                        <label><span>ჯგუფი</span><select name="kindergarten_group_id"><option value="">ყველა მშობელი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected($event->kindergarten_group_id === $group->id)>{{ $group->name }}</option>@endforeach</select></label>
                        <label><span>სათაური</span><input name="title" value="{{ $event->title }}" required minlength="3" maxlength="180"></label>
                        <label class="wide"><span>აღწერა</span><textarea name="description" rows="3" required minlength="5" maxlength="5000">{{ $event->description }}</textarea></label>
                        <label><span>დაწყება</span><input type="datetime-local" name="starts_at" value="{{ $event->starts_at?->format('Y-m-d\TH:i') }}" required></label>
                        <label><span>დასრულება</span><input type="datetime-local" name="ends_at" value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}"></label>
                        <label><span>ადგილი</span><input name="location" value="{{ $event->location }}" maxlength="180"></label>
                        <label><span>ტევადობა</span><input type="number" name="capacity" value="{{ $event->capacity }}" min="1" max="10000"></label>
                        <label><span>სტატუსი</span><select name="status">@foreach(\App\Models\ClubEvent::STATUSES as $value => $label)<option value="{{ $value }}" @selected($event->status === $value)>{{ $label }}</option>@endforeach</select></label>
                        <input type="hidden" name="is_featured" value="0"><label class="club-admin-check"><input type="checkbox" name="is_featured" value="1" @checked($event->is_featured)><span>მნიშვნელოვანი</span></label>
                        <label class="club-admin-check"><input type="checkbox" name="notify_parents" value="1"><span>განახლების შეტყობინების გაგზავნა</span></label>
                        <button class="club-admin-primary wide" type="submit">ცვლილებების შენახვა</button>
                    </form>
                    <form method="post" action="{{ route('admin.club.events.destroy', $event) }}" onsubmit="return confirm('ნამდვილად წავშალოთ ღონისძიება?')">@csrf @method('DELETE')<button class="club-admin-danger" type="submit">ღონისძიების წაშლა</button></form>
                </details>
            </article>
        @empty
            <div class="club-admin-empty"><strong>ღონისძიება ჯერ არ არის შექმნილი</strong><p>პირველი ღონისძიება ზემოთ მოცემული ფორმიდან დაამატეთ.</p></div>
        @endforelse
    </div>
</section>

<section class="club-admin-panel club-admin-questions">
    <div class="club-admin-heading"><div><small>კითხვები და ოფიციალური პასუხები</small><h2>მშობელთა კლუბის საუბრები</h2><p>უპასუხეთ მშობლებს, მიანიჭეთ პრიორიტეტი, დაამაგრეთ მნიშვნელოვანი თემა ან დახურეთ საუბარი.</p></div></div>
    <form method="get" action="{{ route('admin.club.index') }}" class="club-question-filters">
        <input name="q" value="{{ request('q') }}" placeholder="მოძებნეთ სათაური ან ტექსტი">
        <select name="topic_status"><option value="">ყველა სტატუსი</option>@foreach(\App\Models\ForumTopic::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('topic_status') === $value)>{{ $label }}</option>@endforeach</select>
        <select name="group_id"><option value="">ყველა ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string)request('group_id') === (string)$group->id)>{{ $group->name }}</option>@endforeach</select>
        <button type="submit">გაფილტვრა</button><a href="{{ route('admin.club.index') }}">გასუფთავება</a>
    </form>

    <div class="club-question-list">
        @forelse($topics as $topic)
            <article class="club-question-admin-card status-{{ $topic->status }} priority-{{ $topic->priority }}">
                <header><div class="club-question-labels"><span>{{ $topic->group?->name }}</span><span>{{ \App\Models\ForumTopic::CATEGORIES[$topic->category] ?? $topic->category }}</span>@if($topic->is_pinned)<b>დამაგრებული</b>@endif</div><div><strong>{{ \App\Models\ForumTopic::STATUSES[$topic->status] ?? $topic->status }}</strong><small>{{ \App\Models\ForumTopic::PRIORITIES[$topic->priority] ?? $topic->priority }}</small></div></header>
                <h3>{{ $topic->title }}</h3><p>{{ $topic->body }}</p><div class="club-question-author"><strong>{{ $topic->author?->name }}</strong><span>{{ $topic->created_at?->format('d.m.Y H:i') }} · {{ $topic->comments_count }} პასუხი</span></div>

                @if($topic->comments->isNotEmpty())
                    <details class="club-question-thread"><summary>არსებული პასუხების ნახვა</summary><div>@foreach($topic->comments->sortBy('created_at') as $comment)<article class="{{ $comment->is_official_answer ? 'official-answer' : '' }}"><strong>{{ $comment->author?->name }} @if($comment->is_official_answer)<span>ოფიციალური პასუხი</span>@endif</strong><p>{{ $comment->body }}</p><small>{{ $comment->created_at?->format('d.m.Y H:i') }}</small></article>@endforeach</div></details>
                @endif

                <div class="club-question-actions">
                    <form method="post" action="{{ route('admin.club.topics.reply', $topic) }}" class="club-official-reply">@csrf<label><span>ოფიციალური პასუხი</span><textarea name="body" rows="3" required minlength="2" maxlength="5000" placeholder="უპასუხეთ მშობელს გასაგებად და პროფესიონალურად"></textarea></label><button class="club-admin-primary" type="submit">პასუხის გამოქვეყნება</button></form>
                    <form method="post" action="{{ route('admin.club.topics.update', $topic) }}" class="club-topic-settings">@csrf @method('PATCH')
                        <label><span>სტატუსი</span><select name="status">@foreach(\App\Models\ForumTopic::STATUSES as $value => $label)<option value="{{ $value }}" @selected($topic->status === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label><span>პრიორიტეტი</span><select name="priority">@foreach(\App\Models\ForumTopic::PRIORITIES as $value => $label)<option value="{{ $value }}" @selected($topic->priority === $value)>{{ $label }}</option>@endforeach</select></label>
                        <input type="hidden" name="is_pinned" value="0"><label class="club-admin-check"><input type="checkbox" name="is_pinned" value="1" @checked($topic->is_pinned)><span>დამაგრება</span></label>
                        <input type="hidden" name="is_locked" value="0"><label class="club-admin-check"><input type="checkbox" name="is_locked" value="1" @checked($topic->is_locked)><span>პასუხების დახურვა</span></label>
                        <button type="submit">სტატუსის შენახვა</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="club-admin-empty"><strong>კითხვა ვერ მოიძებნა</strong><p>ფილტრი შეცვალეთ ან დაელოდეთ მშობლების ახალ კითხვებს.</p></div>
        @endforelse
    </div>
</section>
@endsection
