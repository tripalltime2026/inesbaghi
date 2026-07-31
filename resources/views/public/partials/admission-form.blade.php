<section class="public-admission-form content-width" aria-labelledby="admissionFormTitle">
    <div class="public-admission-intro">
        <span>მარტივი განაცხადი</span>
        <h2 id="admissionFormTitle">დატოვეთ საკონტაქტო ინფორმაცია</h2>
        <p>ბავშვის სახელს ამ ეტაპზე არ გთხოვთ. ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და შემდეგ ნაბიჯებს.</p>
    </div>

    @if(session('admission_success'))
        <div class="public-form-success" role="status">{{ session('admission_success') }}</div>
    @endif
    @if($errors->any())
        <div class="public-form-errors" role="alert">
            @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
        </div>
    @endif

    <form method="post" action="{{ route('admissions.store') }}">
        @csrf
        <input type="hidden" name="privacy_policy_version" value="{{ \App\Support\PrivacyPolicy::VERSION }}">
        <div class="public-form-grid">
            <label><span>მშობლის სახელი და გვარი</span><input name="parent_name" value="{{ old('parent_name', auth()->user()?->name) }}" required maxlength="120" autocomplete="name"></label>
            <label><span>მობილურის ნომერი</span><input name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required inputmode="tel" autocomplete="tel" placeholder="5XX XX XX XX"></label>
            <label><span>ბავშვის დაბადების წელი</span><input name="birth_year" value="{{ old('birth_year') }}" type="number" min="2018" max="2026" placeholder="მაგ. 2022"></label>
            <label><span>სასურველი ჯგუფი</span><select name="preferred_group" required>
                @foreach(['2-3'=>'2–3 წელი','3-4'=>'3–4 წელი','4-5'=>'4–5 წელი','5-6'=>'5–6 წელი'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('preferred_group', '3-4') === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
            <label><span>სასწავლო წელი</span><select name="academic_year" required><option value="2026" @selected(old('academic_year', '2026') === '2026')>2026–2027</option><option value="2027" @selected(old('academic_year') === '2027')>2027–2028</option></select></label>
            <label><span>სასურველი ვიზიტის თარიღი</span><input name="preferred_tour_date" value="{{ old('preferred_tour_date') }}" type="date" min="{{ now()->toDateString() }}"></label>
        </div>

        <input type="hidden" name="wants_tour" value="0">
        <label class="public-form-check"><input type="checkbox" name="wants_tour" value="1" @checked(old('wants_tour', '1') === '1')><span>მსურს გაცნობითი ვიზიტის დაგეგმვა</span></label>
        <label class="public-form-comment"><span>დამატებითი კითხვა — სურვილის შემთხვევაში</span><textarea name="comment" maxlength="2000" placeholder="მოგვწერეთ, რა გაინტერესებთ">{{ old('comment') }}</textarea></label>

        <div class="public-form-consents">
            <label><input type="checkbox" name="guardian_authority_confirmed" value="1" required @checked(old('guardian_authority_confirmed'))><span>ვადასტურებ, რომ ვარ ბავშვის მშობელი ან კანონიერი წარმომადგენელი.</span></label>
            <label><input type="checkbox" name="privacy_accepted" value="1" required @checked(old('privacy_accepted'))><span>გავეცანი <a href="{{ route('privacy') }}" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და ვეთანხმები განაცხადის განხილვისთვის მონაცემების დამუშავებას.</span></label>
            <label><input type="checkbox" name="special_category_consent" value="1" required @checked(old('special_category_consent'))><span>ვეთანხმები ჩემ მიერ ნებაყოფლობით მითითებული განსაკუთრებული კატეგორიის ინფორმაციის დამუშავებას ბავშვის უსაფრთხოების მიზნით.</span></label>
            <label><input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent'))><span>მსურს მივიღო ბაღის სიახლეები და ღონისძიებების ინფორმაცია.</span></label>
        </div>

        <button type="submit">განაცხადის გაგზავნა</button>
    </form>
</section>
