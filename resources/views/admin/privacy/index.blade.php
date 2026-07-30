@extends('admin.layout')

@section('title', 'მონაცემთა დაცვა')
@section('heading', 'მონაცემთა დაცვა და მოთხოვნები')

@section('content')
<section class="admin-section compact">
    <div class="metric-grid small">
        <article class="metric-card"><span>ახალი მოთხოვნები</span><strong>{{ $newCount }}</strong><small>საჭიროებს რეაგირებას</small></article>
        <article class="metric-card"><span>თანხმობის ჩანაწერები</span><strong>{{ $consentCount }}</strong><small>აუდიტის მტკიცებულება</small></article>
        <article class="metric-card"><span>გამოხმობილი თანხმობა</span><strong>{{ $withdrawnCount }}</strong><small>შემდგომი დამუშავება შესამოწმებელია</small></article>
    </div>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">მონაცემთა სუბიექტის უფლებები</p><h2>მოთხოვნების ჟურნალი</h2></div><a class="secondary" href="{{ route('privacy') }}" target="_blank" rel="noopener">პოლიტიკის ნახვა ↗</a></div>
    <form class="admin-filters" method="get">
        <label><span>სტატუსი</span><select name="status"><option value="">ყველა</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>ტიპი</span><select name="type"><option value="">ყველა</option>@foreach($requestTypes as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select></label>
        <button class="secondary" type="submit">გაფილტვრა</button>
    </form>

    <div class="privacy-request-list">
        @forelse($requests as $item)
            <article class="panel privacy-request-card">
                <div class="panel-heading">
                    <div><p class="eyebrow">მოთხოვნა #{{ $item->id }} · {{ $item->created_at->format('d.m.Y H:i') }}</p><h2>{{ $requestTypes[$item->request_type] ?? $item->request_type }}</h2></div>
                    <span class="status status-{{ $item->status }}">{{ $statuses[$item->status] ?? $item->status }}</span>
                </div>
                <div class="detail-grid">
                    <div>
                        <dl class="legal-details admin-legal-details"><div><dt>მომთხოვნი</dt><dd>{{ $item->name }}</dd></div><div><dt>ტელეფონი</dt><dd>{{ $item->phone }}</dd></div><div><dt>ელფოსტა</dt><dd>{{ $item->email ?: '—' }}</dd></div><div><dt>ვინაობა</dt><dd>{{ $item->verified_at ? 'დადასტურებულია · '.$item->verified_at->format('d.m.Y H:i') : 'დასადასტურებელია' }}</dd></div></dl>
                        <div class="customer-comment"><strong>მოთხოვნის დეტალები</strong><p>{{ $item->details ?: 'დამატებითი აღწერა არ არის.' }}</p></div>
                    </div>
                    <form class="admin-form" method="post" action="{{ route('admin.privacy.requests.update', $item) }}">
                        @csrf
                        @method('patch')
                        <label><span>სტატუსი</span><select name="status">@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected($item->status===$value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="switch-row"><input type="hidden" name="identity_verified" value="0"><input type="checkbox" name="identity_verified" value="1" {{ $item->verified_at ? 'checked disabled' : '' }}><span>ვინაობა / წარმომადგენლობა დადასტურებულია</span></label>
                        <label><span>შიდა პასუხი და შესრულებული მოქმედება</span><textarea name="response_notes" rows="6">{{ $item->response_notes }}</textarea></label>
                        <button class="primary" type="submit">მოთხოვნის განახლება</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state">მონაცემთა სუბიექტის მოთხოვნები ჯერ არ არის.</div>
        @endforelse
    </div>

    {{ $requests->links() }}
</section>
@endsection
