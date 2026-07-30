@extends('legal.layout')

@section('title', 'მონაცემთა სუბიექტის მოთხოვნა')
@section('description', 'პერსონალურ მონაცემებზე წვდომის, გასწორების, წაშლის ან თანხმობის გამოხმობის მოთხოვნის ფორმა.')

@section('content')
<section class="legal-hero compact">
    <span>თქვენი უფლებები</span>
    <h1>მონაცემთა სუბიექტის მოთხოვნა</h1>
    <p>ფორმით შეგიძლიათ მოითხოვოთ ინფორმაცია, მონაცემების ასლი, გასწორება, წაშლა, დამუშავების შეზღუდვა ან თანხმობის გამოხმობა.</p>
</section>

<div class="rights-layout">
    <form class="rights-form" method="post" action="{{ route('privacy.request.store') }}">
        @csrf
        <div class="rights-form-grid">
            <label><span>სახელი და გვარი *</span><input name="name" value="{{ old('name', auth()->user()?->name) }}" required autocomplete="name"></label>
            <label><span>ტელეფონის ნომერი *</span><input name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required type="tel" autocomplete="tel"></label>
            <label><span>ელფოსტა</span><input name="email" value="{{ old('email', auth()->user()?->email) }}" type="email" autocomplete="email"></label>
            <label><span>მოთხოვნის ტიპი *</span><select name="request_type" required><option value="">აირჩიეთ</option>@foreach($requestTypes as $value => $label)<option value="{{ $value }}" @selected(old('request_type')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label class="wide"><span>დეტალები</span><textarea name="details" rows="7" maxlength="5000" placeholder="აღწერეთ, რომელ მონაცემს ან ანგარიშს ეხება მოთხოვნა. ბავშვის მონაცემებზე მოთხოვნისას მიუთითეთ ბავშვის სახელი და თქვენი კავშირი.">{{ old('details') }}</textarea></label>
        </div>
        <label class="legal-check"><input type="checkbox" name="privacy_accepted" value="1" required><span>გავეცანი <a href="{{ route('privacy') }}" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და ვეთანხმები, რომ მოთხოვნის განხილვისა და ჩემი ვინაობის დადასტურების მიზნით დამუშავდეს ამ ფორმაში მითითებული ინფორმაცია.</span></label>
        <p class="rights-note">სხვა პირის ან ბავშვის მონაცემების დასაცავად შეიძლება დაგიკავშირდეთ ვინაობისა და კანონიერი წარმომადგენლობის დასადასტურებლად. მოთხოვნის გაგზავნა უფასოა.</p>
        <button class="legal-submit" type="submit">მოთხოვნის რეგისტრაცია</button>
    </form>

    <aside class="rights-help">
        <span>ℹ</span>
        <h2>რა ხდება შემდეგ?</h2>
        <ol>
            <li>მოთხოვნა მიიღებს უნიკალურ ნომერს.</li>
            <li>საჭიროების შემთხვევაში დაგიკავშირდებით ვინაობის დასადასტურებლად.</li>
            <li>პასუხს მოგაწვდით კანონით დადგენილ ვადაში, ჩვეულებრივ 10 სამუშაო დღეში.</li>
        </ol>
        <p>თანხმობის გამოხმობისას თანხმობაზე დაფუძნებული შემდგომი დამუშავება შეწყდება. სხვა სამართლებრივი საფუძვლით შესანახი მონაცემები შეიძლება დარჩეს აუცილებელი მოცულობით.</p>
        <a href="tel:+995555411831">{{ $companyPhone }}</a>
    </aside>
</div>
@endsection
