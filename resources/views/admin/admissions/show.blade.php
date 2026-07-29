@extends('admin.layout')

@section('title', 'განაცხადი #'.$application->id)
@section('heading', 'განაცხადი #'.$application->id)

@section('content')
<section class="admin-section compact">
    <div class="detail-heading">
        <div>
            <a class="back-link" href="{{ route('admin.admissions.index') }}">← ყველა განაცხადი</a>
            <h2>{{ $application->child_name }}</h2>
            <p>{{ $application->parent_name }} · {{ $application->phone }}</p>
        </div>
        <span class="status status-{{ $application->status }} large">{{ $statuses[$application->status] ?? $application->status }}</span>
    </div>
</section>

<div class="detail-grid">
    <div>
        <section class="admin-section compact panel">
            <div class="panel-heading"><div><p class="eyebrow">განაცხადის მონაცემები</p><h2>ოჯახის ინფორმაცია</h2></div></div>
            <dl class="detail-list">
                <div><dt>მშობელი / მეურვე</dt><dd>{{ $application->parent_name }}</dd></div>
                <div><dt>ტელეფონი</dt><dd><a href="tel:{{ $application->phone }}">{{ $application->phone }}</a></dd></div>
                <div><dt>ბავშვი</dt><dd>{{ $application->child_name }}</dd></div>
                <div><dt>დაბადების წელი</dt><dd>{{ $application->birth_year ?? 'არ არის მითითებული' }}</dd></div>
                <div><dt>სასურველი ჯგუფი</dt><dd>{{ $application->preferred_group }} წელი</dd></div>
                <div><dt>სასწავლო წელი</dt><dd>{{ $application->academic_year }}</dd></div>
                <div><dt>ტური მოთხოვნილია</dt><dd>{{ $application->wants_tour ? 'დიახ' : 'არა' }}</dd></div>
                <div><dt>სასურველი ტურის თარიღი</dt><dd>{{ $application->preferred_tour_date?->format('d.m.Y') ?? '—' }}</dd></div>
                <div><dt>წყარო</dt><dd>{{ $application->source }}</dd></div>
                <div><dt>შემოსვლის დრო</dt><dd>{{ $application->created_at->format('d.m.Y H:i') }}</dd></div>
            </dl>
            @if ($application->comment)
                <div class="customer-comment"><strong>მშობლის კომენტარი</strong><p>{{ $application->comment }}</p></div>
            @endif
        </section>

        <section class="admin-section compact panel">
            <div class="panel-heading"><div><p class="eyebrow">ისტორია</p><h2>შიდა კომენტარები</h2></div></div>
            <form class="note-form" method="post" action="{{ route('admin.admissions.notes.store', $application) }}">
                @csrf
                <textarea name="body" rows="4" required maxlength="3000" placeholder="მაგალითად: მშობელს დავუკავშირდი, ტური შეთანხმებულია პარასკევს...">{{ old('body') }}</textarea>
                <button class="primary" type="submit">კომენტარის დამატება</button>
            </form>

            <div class="timeline">
                @forelse ($application->notes as $note)
                    <article>
                        <span class="timeline-dot"></span>
                        <div><strong>{{ $note->author?->name ?? 'წაშლილი მომხმარებელი' }}</strong><time>{{ $note->created_at->format('d.m.Y H:i') }}</time><p>{{ $note->body }}</p></div>
                    </article>
                @empty
                    <p class="empty-state">შიდა კომენტარები ჯერ არ არის.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside>
        <section class="admin-section compact panel sticky-panel">
            <div class="panel-heading"><div><p class="eyebrow">CRM მოქმედებები</p><h2>განაცხადის მართვა</h2></div></div>
            <form class="admin-form" method="post" action="{{ route('admin.admissions.update', $application) }}">
                @csrf
                @method('PATCH')
                <label><span>სტატუსი</span>
                    <select name="status" required>
                        @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(old('status', $application->status) === $key)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label><span>პასუხისმგებელი თანამშრომელი</span>
                    <select name="assigned_to_user_id">
                        <option value="">არ არის დანიშნული</option>
                        @foreach ($assignableUsers as $user)<option value="{{ $user->id }}" @selected((string) old('assigned_to_user_id', $application->assigned_to_user_id) === (string) $user->id)>{{ $user->name }} · {{ $user->role }}</option>@endforeach
                    </select>
                </label>
                <label><span>შემდეგი დაკავშირება</span><input type="datetime-local" name="follow_up_at" value="{{ old('follow_up_at', $application->follow_up_at?->format('Y-m-d\TH:i')) }}"></label>
                <label><span>ტურის დრო</span><input type="datetime-local" name="tour_scheduled_at" value="{{ old('tour_scheduled_at', $application->tour_scheduled_at?->format('Y-m-d\TH:i')) }}"></label>
                <button class="primary full" type="submit">ცვლილებების შენახვა</button>
            </form>

            <div class="conversion-box">
                <strong>ჩარიცხვად გარდაქმნა</strong>
                @if ($application->converted_at)
                    <p>გარდაქმნილია {{ $application->converted_at->format('d.m.Y H:i') }}.</p>
                    <span class="status status-enrolled">ბავშვი #{{ $application->converted_child_id }}</span>
                @else
                    <p>შეიქმნება მშობლის პროფილი, ბავშვის ჩანაწერი და pending ჩარიცხვა შესაბამის ჯგუფში.</p>
                    <form method="post" action="{{ route('admin.admissions.convert', $application) }}" onsubmit="return confirm('ნამდვილად გსურთ განაცხადის ჩარიცხვად გარდაქმნა?')">
                        @csrf
                        <button class="danger-button" type="submit">გარდაქმნა და ჩარიცხვა</button>
                    </form>
                @endif
            </div>
        </section>
    </aside>
</div>
@endsection
