@extends('admin.layout')

@section('title', 'ადმინ პანელი')
@section('heading', 'ადმინ პანელი')

@section('content')
@php($panel = request('panel', 'analytics'))

@if($panel === 'analytics')
<section class="admin-section compact">
    <div class="metric-grid">
        <article class="metric-card"><span>ბავშვები სულ</span><strong>{{ $children }}</strong><small>აქტიური ჩარიცხვა: {{ $activeEnrollments }}</small></article>
        <article class="metric-card"><span>მშობლები</span><strong>{{ $parents }}</strong><small>დარეგისტრირებული</small></article>
        <article class="metric-card"><span>კლუბის წევრები</span><strong>{{ $members }}</strong><small>მშობელი და წევრი</small></article>
        <article class="metric-card"><span>დამტკიცების ლოდინში</span><strong>{{ $pendingUsers }}</strong><small>ახალი რეგისტრაცია</small></article>
    </div>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">ჯგუფების დატვირთვა</p><h2>მიმდინარე მდგომარეობა</h2></div><a class="secondary" href="{{ route('admin.groups.index') }}">ჯგუფების მართვა</a></div>
    <div class="metric-grid">
        @forelse($groupStats as $group)
            @php($count=(int)$group->active_enrollments_count)
            @php($capacity=max(1,(int)$group->capacity))
            @php($percent=min(100,round(($count/$capacity)*100)))
            <article class="metric-card"><span>{{ $group->name }} · {{ $group->academic_year }}</span><strong>{{ $count }} / {{ $capacity }}</strong><small>{{ max(0,$capacity-$count) }} თავისუფალი ადგილი · {{ $percent }}%</small></article>
        @empty
            <article class="metric-card"><span>ჯგუფები</span><strong>0</strong><small>ჯგუფი ჯერ არ შექმნილა</small></article>
        @endforelse
    </div>
</section>

<section class="admin-section compact">
    <div class="metric-grid small">
        <article class="metric-card"><span>მიმდინარე თვეში მიღებული</span><strong>{{ number_format($monthlyCollected,2) }} ₾</strong><a href="{{ route('admin.payments.index') }}">გადახდები</a></article>
        <article class="metric-card"><span>მიმდინარე დავალიანება</span><strong>{{ number_format($monthlyOutstanding,2) }} ₾</strong><a href="{{ route('admin.payments.index',['status'=>'overdue']) }}">დეტალები</a></article>
        <article class="metric-card"><span>ყველა განაცხადი</span><strong>{{ $applications }}</strong><small>ახალი: {{ $newApplications }} · დღევანდელი ტურები: {{ $toursToday }}</small></article>
    </div>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">ჩარიცხვის ვორონკა</p><h2>ბოლო განაცხადები</h2></div><a class="secondary" href="{{ route('admin.admissions.index') }}">ყველა განაცხადი</a></div>
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>ბავშვი / მშობელი</th><th>ჯგუფი</th><th>სტატუსი</th><th>პასუხისმგებელი</th><th>თარიღი</th><th></th></tr></thead><tbody>
        @forelse ($recentApplications as $application)
            <tr><td><strong>{{ $application->child_name }}</strong><small>{{ $application->parent_name }} · {{ $application->phone }}</small></td><td>{{ $application->preferred_group }} წელი</td><td><span class="status status-{{ $application->status }}">{{ \App\Models\AdmissionApplication::STATUSES[$application->status] ?? $application->status }}</span></td><td>{{ $application->assignedTo?->name ?? 'არ არის დანიშნული' }}</td><td>{{ $application->created_at->format('d.m.Y H:i') }}</td><td><a class="row-link" href="{{ route('admin.admissions.show', $application) }}">გახსნა →</a></td></tr>
        @empty<tr><td colspan="6" class="empty-state">განაცხადები ჯერ არ არის.</td></tr>@endforelse
    </tbody></table></div>
</section>

@elseif($panel === 'approvals')
<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">დამტკიცებები</p><h2>დამტკიცების მოლოდინში ({{ $pendingUsers }})</h2></div><span class="status status-new">SMS რეგისტრაციები</span></div>
    <div class="panel"><p class="customer-comment">ახლადრეგისტრირებული მომხმარებლებისთვის განსაზღვრეთ — არის თუ არა ბაღის მშობელი. მშობლად დამტკიცებისას ანგარიში დაუკავშირდება ბავშვის რეალურ ჩანაწერს.</p>
        <div class="table-wrap"><table class="admin-table"><thead><tr><th>მომხმარებელი</th><th>ტელეფონი</th><th>როლი</th><th>სტატუსი</th><th>მოქმედება</th></tr></thead><tbody>
        @forelse($pendingApprovals as $user)<tr><td><strong>{{ $user->name }}</strong><small>დარეგისტრირდა {{ $user->created_at?->diffForHumans() }}</small></td><td>{{ $user->phone }}</td><td>{{ $user->role }}</td><td><span class="status status-new">დამტკიცების მოლოდინში</span></td><td><span class="status">დიზაინის preview</span></td></tr>@empty<tr><td colspan="5" class="empty-state">დასამტკიცებელი ანგარიში არ არის.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</section>

@elseif($panel === 'news')
<section class="admin-section compact"><div class="panel-heading"><div><p class="eyebrow">კონტენტის მართვა</p><h2>ახალი სიახლის გამოქვეყნება</h2></div><span class="status status-approved">საბოლოო დიზაინი</span></div><div class="detail-grid"><div class="panel"><form class="admin-form"><label><span>სათაური</span><input placeholder="სიახლის სათაური"></label><label><span>ტექსტი</span><textarea rows="7" placeholder="დეტალური ინფორმაცია მშობლებისთვის"></textarea></label><label><span>ვის ენახება</span><select><option>კლუბის წევრები</option><option>მხოლოდ ბაღის მშობლები</option><option>კონკრეტული ჯგუფი</option></select></label><button class="primary" type="button">გამოქვეყნება</button></form></div><aside class="panel"><p class="eyebrow">უახლესი გამოქვეყნებული</p><h2>საზაფხულო ზეიმის დეტალები</h2><p>ხუთშაბათს, 20 ივლისს, ეზოში გაიმართება საზაფხულო ზეიმი.</p><span class="status status-approved">კლუბის წევრები</span></aside></div></section>

@elseif($panel === 'blog')
<section class="admin-section compact"><div class="panel-heading"><div><p class="eyebrow">ბლოგი</p><h2>ახალი ბლოგპოსტი</h2></div><span class="status">დიზაინის preview</span></div><div class="detail-grid"><div class="panel"><form class="admin-form"><label><span>სათაური</span><input placeholder="სტატიის სათაური"></label><label><span>მოკლე აღწერა</span><textarea rows="3"></textarea></label><label><span>სრული ტექსტი</span><textarea rows="8"></textarea></label><label><span>კატეგორია</span><select><option>აღზრდა</option><option>კვება</option><option>განვითარება</option><option>სკოლა</option></select></label><button class="primary" type="button">გამოქვეყნება</button></form></div><aside class="panel"><p class="eyebrow">გამოქვეყნებული სტატიები</p><h2>როგორ ვამზადოთ ბავშვი ბაღისთვის — 5 რჩევა</h2><p>პირველი დღეები ბაღში შეიძლება რთული იყოს — ვიზიარებთ პრაქტიკულ რჩევებს.</p><span class="status status-approved">აღზრდა</span></aside></div></section>

@elseif($panel === 'photos')
<section class="admin-section compact"><div class="panel-heading"><div><p class="eyebrow">გალერეა</p><h2>ფოტოების ატვირთვა</h2></div><span class="status status-approved">მხოლოდ კლუბის წევრებისთვის</span></div><div class="detail-grid"><div class="panel"><form class="admin-form"><label><span>ჯგუფი</span><select><option>ყველა ჯგუფი</option>@foreach($groupStats as $group)<option>{{ $group->name }}</option>@endforeach</select></label><label><span>ფოტოების არჩევა</span><input type="file" multiple accept="image/jpeg,image/png"></label><div class="customer-comment">📷 დააჭირეთ ან ჩააგდეთ ფოტოები — JPG ან PNG, ერთდროულად რამდენიმე.</div><button class="primary" type="button">ატვირთვა</button></form></div><aside class="panel"><p class="eyebrow">გალერეა</p><div style="height:180px;border-radius:18px;background:var(--mint);display:grid;place-items:center;font-weight:800">ზაფხულის სახალისო დღე</div><p>ყველა ჯგუფი · 10 ივლისი, 2026</p></aside></div></section>

@elseif($panel === 'events')
<section class="admin-section compact"><div class="panel-heading"><div><p class="eyebrow">ღონისძიებები</p><h2>ახალი ღონისძიება</h2></div><span class="status status-approved">კლუბის კალენდარი</span></div><div class="detail-grid"><div class="panel"><form class="admin-form"><label><span>სათაური</span><input placeholder="ღონისძიების დასახელება"></label><label><span>თარიღი და დრო</span><input type="datetime-local"></label><label><span>ადგილმდებარეობა</span><input placeholder="ბაღის ეზო"></label><label><span>აღწერა</span><textarea rows="5"></textarea></label><button class="primary" type="button">გამოქვეყნება</button></form></div><aside class="panel"><p class="eyebrow">ღონისძიებები (ცოცხალი)</p><h2>საზაფხულო ზეიმი</h2><p>20 ივლისი, 17:00 · ბაღის ეზო</p><span class="status status-approved">18 მოზრდილი · 22 ბავშვი</span></aside></div></section>

@elseif($panel === 'messages')
<section class="admin-section compact"><div class="panel-heading"><div><p class="eyebrow">შეტყობინებები</p><h2>ინდივიდუალური შეტყობინება</h2></div><span class="status">SMS / კაბინეტი</span></div><div class="detail-grid"><div class="panel"><form class="admin-form"><label><span>მიმღები</span><select><option>აირჩიეთ მომხმარებელი</option>@foreach($recentUsers as $user)<option>{{ $user->name }} · {{ $user->phone }}</option>@endforeach</select></label><label><span>შეტყობინება</span><textarea rows="7" placeholder="შეტყობინების ტექსტი"></textarea></label><button class="primary" type="button">გაგზავნა</button></form></div><aside class="panel"><p class="eyebrow">შაბლონი</p><h2>გადახდის შეხსენება</h2><p>გთხოვთ, გადაამოწმოთ მიმდინარე თვის დარიცხვა მშობლის კაბინეტში.</p></aside></div></section>

@elseif($panel === 'settings')
<section class="admin-section compact"><div class="panel-heading"><div><p class="eyebrow">პარამეტრები</p><h2>ბაღის ძირითადი ინფორმაცია</h2></div><span class="status status-approved">საბოლოო დიზაინი</span></div><div class="detail-grid"><div class="panel"><form class="admin-form"><label><span>ბავშვების მაქსიმალური რაოდენობა ჯგუფში</span><input type="number" value="20"></label><label><span>ყოველთვიური საფასური</span><input type="number" value="600"></label><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><label><span>სამუშაოს დაწყება</span><input type="time" value="08:00"></label><label><span>სამუშაოს დასრულება</span><input type="time" value="19:00"></label></div><label><span>მისამართი</span><input value="ლერმონტოვის 53, ბათუმი"></label><label><span>ტელეფონი</span><input value="+995 555 41 18 31"></label><button class="primary" type="button">შენახვა</button></form></div><aside class="panel"><p class="eyebrow">სისტემა</p><h2>Demo Auth</h2><p>დროებითი ადმინისტრატორი: 555411831. რეალური SMS პროვაიდერის ჩართვისას Demo Auth გარემოს პარამეტრით გაითიშება.</p><span class="status status-new">დროებითი რეჟიმი</span></aside></div></section>
@endif
@endsection
