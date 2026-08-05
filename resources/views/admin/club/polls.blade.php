@extends('admin.layout')

@section('title', 'ჯგუფის გამოკითხვები')
@section('heading', 'ჯგუფის გამოკითხვები')

@section('content')
<section class="club-admin-panel poll-admin-intro">
    <div class="club-admin-heading">
        <div>
            <small>მარტივი გამოკითხვები</small>
            <h2>ერთი გამოკითხვა — ერთი ასაკობრივი ჯგუფი</h2>
            <p>2–3 წლის ჯგუფისთვის შექმნილ გამოკითხვას სხვა ჯგუფის მშობლები ვერ ნახავენ და ხმას ვერ მისცემენ.</p>
        </div>
        <a class="club-admin-secondary" href="{{ route('admin.club.index') }}">← კითხვები და ღონისძიებები</a>
    </div>
</section>

<section class="club-admin-grid poll-admin-grid">
    <article class="club-admin-panel">
        <div class="club-admin-heading"><div><small>ახალი გამოკითხვა</small><h2>შექმენით რამდენიმე წამში</h2><p>მიუთითეთ ჯგუფი, შეკითხვა და მინიმუმ ორი პასუხი.</p></div></div>
        <form method="post" action="{{ route('admin.club.polls.store') }}" class="club-admin-form poll-create-form">
            @csrf
            <label><span>ასაკობრივი ჯგუფი</span><select name="kindergarten_group_id" required><option value="">აირჩიეთ ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(old('kindergarten_group_id') == $group->id)>{{ $group->name }} · {{ $group->academic_year }}</option>@endforeach</select></label>
            <label><span>სტატუსი</span><select name="status" required>@foreach(\App\Models\ClubPoll::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', 'published') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="wide"><span>შეკითხვა</span><input name="question" value="{{ old('question') }}" minlength="4" maxlength="240" required placeholder="მაგალითად: რომელ დღეს გირჩევნიათ მშობელთა შეხვედრა?"></label>
            <label class="wide"><span>მოკლე განმარტება — სურვილისამებრ</span><textarea name="description" rows="3" maxlength="2000" placeholder="დამატებითი დეტალი მშობლებისთვის">{{ old('description') }}</textarea></label>
            <label class="wide"><span>პასუხები</span><div class="poll-option-inputs">
                @for($index = 0; $index < 6; $index++)
                    <input name="options[]" value="{{ old('options.'.$index) }}" maxlength="180" @required($index < 2) placeholder="პასუხი {{ $index + 1 }}{{ $index >= 2 ? ' — სურვილისამებრ' : '' }}">
                @endfor
            </div></label>
            <label><span>დახურვის დრო — სურვილისამებრ</span><input type="datetime-local" name="closes_at" value="{{ old('closes_at') }}"></label>
            <button class="club-admin-primary" type="submit">გამოკითხვის შექმნა</button>
        </form>
    </article>

    <aside class="club-admin-panel club-admin-guide">
        <small>ჯგუფების დაცვა</small><h2>რა ხდება გამოქვეყნების შემდეგ</h2>
        <ol>
            <li><strong>ჩანს მხოლოდ არჩეულ ჯგუფში</strong><span>სხვა ასაკობრივი ჯგუფი არც გამოკითხვას ხედავს და არც API-დან იღებს.</span></li>
            <li><strong>ერთი მშობელი — ერთი ხმა</strong><span>მშობელს შეუძლია პასუხის შეცვლა, მაგრამ ორი ხმის მიცემა ვერ მოხდება.</span></li>
            <li><strong>შედეგი ითვლება ავტომატურად</strong><span>პროცენტები და ხმების რაოდენობა მყისიერად ახლდება.</span></li>
            <li><strong>ფიდში ჩანს კითხვებთან ერთად</strong><span>მშობელს არ სჭირდება სხვადასხვა რთულ გვერდზე გადასვლა.</span></li>
        </ol>
    </aside>
</section>

<section class="club-admin-panel">
    <div class="club-admin-heading"><div><small>შედეგები</small><h2>შექმნილი გამოკითხვები</h2><p>ნახეთ პასუხები, დახურეთ კენჭისყრა ან შეცვალეთ ტექსტი.</p></div></div>
    <div class="poll-admin-list">
        @forelse($polls as $poll)
            <article class="poll-admin-card status-{{ $poll->status }}">
                <header>
                    <div><span>{{ $poll->group?->name }}</span><h3>{{ $poll->question }}</h3><small>{{ $poll->created_at?->format('d.m.Y H:i') }} · {{ $poll->votes_count }} ხმა</small></div>
                    <b>{{ \App\Models\ClubPoll::STATUSES[$poll->status] ?? $poll->status }}</b>
                </header>
                @if($poll->description)<p>{{ $poll->description }}</p>@endif
                <div class="poll-admin-results">
                    @foreach($poll->options as $option)
                        @php($percent = $poll->votes_count > 0 ? (int) round(($option->votes_count / $poll->votes_count) * 100) : 0)
                        <div><span><strong>{{ $option->label }}</strong><small>{{ $option->votes_count }} ხმა</small></span><i><b style="width:{{ $percent }}%"></b></i><em>{{ $percent }}%</em></div>
                    @endforeach
                </div>
                <details class="club-event-edit"><summary>რედაქტირება და დახურვა</summary>
                    <form method="post" action="{{ route('admin.club.polls.update', $poll) }}" class="club-admin-form">
                        @csrf @method('PATCH')
                        <label class="wide"><span>შეკითხვა</span><input name="question" value="{{ $poll->question }}" required minlength="4" maxlength="240"></label>
                        <label class="wide"><span>განმარტება</span><textarea name="description" rows="3" maxlength="2000">{{ $poll->description }}</textarea></label>
                        <label><span>სტატუსი</span><select name="status">@foreach(\App\Models\ClubPoll::STATUSES as $value => $label)<option value="{{ $value }}" @selected($poll->status === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label><span>დახურვის დრო</span><input type="datetime-local" name="closes_at" value="{{ $poll->closes_at?->format('Y-m-d\TH:i') }}"></label>
                        <label class="club-admin-check wide"><input type="checkbox" name="notify_parents" value="1"><span><strong>მშობლებისთვის განახლების შეტყობინება</strong></span></label>
                        <button class="club-admin-primary wide" type="submit">ცვლილებების შენახვა</button>
                    </form>
                    <form method="post" action="{{ route('admin.club.polls.destroy', $poll) }}" onsubmit="return confirm('ნამდვილად წავშალოთ გამოკითხვა?')">@csrf @method('DELETE')<button class="club-admin-danger" type="submit">გამოკითხვის წაშლა</button></form>
                </details>
            </article>
        @empty
            <div class="club-admin-empty"><strong>გამოკითხვა ჯერ არ არის შექმნილი</strong><p>პირველი გამოკითხვა ზემოთ მოცემული ფორმიდან დაამატეთ.</p></div>
        @endforelse
    </div>
</section>
@endsection
