@extends('admin.layout')

@section('title', 'ჯგუფები')
@section('heading', 'ჯგუფები და ადგილები')

@section('content')
<section class="admin-section compact">
    <div class="group-admin-grid">
        @foreach($groups as $group)
            @php($free=max(0,$group->capacity-$group->active_enrollments_count))
            @php($occupancy=$group->capacity>0?min(100,round($group->active_enrollments_count/$group->capacity*100)):0)
            <article class="group-admin-card">
                <div class="group-card-top"><div><span class="status {{ $group->is_active ? 'status-approved' : 'status-archived' }}">{{ $group->is_active ? 'აქტიური' : 'გამორთული' }}</span><h2>{{ $group->name }}</h2><p>{{ $group->academic_year }}</p></div><strong>{{ $free }}</strong></div>
                <div class="capacity-bar"><span style="width:{{ $occupancy }}%"></span></div>
                <div class="capacity-stats"><span>აქტიური <strong>{{ $group->active_enrollments_count }}</strong></span><span>მოლოდინში <strong>{{ $group->pending_enrollments_count }}</strong></span><span>Capacity <strong>{{ $group->capacity }}</strong></span></div>
                <div class="group-card-footer"><span>{{ number_format((float)$group->monthly_fee,2) }} GEL / თვე</span><a class="row-link" href="{{ route('admin.groups.show',$group) }}">მართვა →</a></div>
            </article>
        @endforeach
    </div>
</section>
@endsection
