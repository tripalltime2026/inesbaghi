const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

async function post(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
    body: JSON.stringify(payload),
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.errors ? Object.values(data.errors).flat()[0] : data.message || 'მოთხოვნა ვერ შესრულდა');
  }
  return data;
}

const pages = [...document.querySelectorAll('[data-page]')];
const pageButtons = [...document.querySelectorAll('[data-page-target]')];
const nav = document.getElementById('siteNav');
const menuToggle = document.getElementById('menuToggle');

function showPage(pageName, pushHash = true) {
  const page = pages.find((item) => item.dataset.page === pageName) || pages[0];
  pages.forEach((item) => item.classList.toggle('active', item === page));
  pageButtons.forEach((item) => item.classList.toggle('active', item.dataset.pageTarget === page.dataset.page));
  nav?.classList.remove('open');
  if (menuToggle) menuToggle.textContent = '☰';
  if (pushHash) history.replaceState(null, '', `#${page.dataset.page}`);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

pageButtons.forEach((button) => button.addEventListener('click', () => showPage(button.dataset.pageTarget)));
menuToggle?.addEventListener('click', () => {
  nav?.classList.toggle('open');
  menuToggle.textContent = nav?.classList.contains('open') ? '×' : '☰';
});
showPage(location.hash.replace('#', '') || 'home', false);
window.addEventListener('hashchange', () => showPage(location.hash.replace('#', '') || 'home', false));

const groups = [
  {
    key: '2-3', label: '2-3 წელი', color: '#D3BDD3',
    desc: 'პირველი ნაბიჯები განვითარებაში — მეტყველება, თამაში და სენსორული აქტივობები.',
    teacher: 'მარიამ ხარაზი', free: 3, total: 20,
    schedule: [['08:00', 'მიღება'], ['09:30', 'საუზმე'], ['10:00', 'სენსორული თამაშები'], ['12:30', 'სადილი'], ['13:00', 'დღის ძილი'], ['16:00', 'თავისუფალი თამაში'], ['18:00', 'გატანა']],
  },
  {
    key: '3-4', label: '3-4 წელი', color: '#EFE6A9',
    desc: 'დამოუკიდებლობის ჯგუფი — შემოქმედება, საწყისი სწავლა და მეგობრობა.',
    teacher: 'ანა წერეთელი', free: 2, total: 20,
    schedule: [['08:00', 'მიღება'], ['09:30', 'საუზმე'], ['10:00', 'შემოქმედებითი აქტივობა'], ['12:30', 'სადილი'], ['13:00', 'დღის ძილი'], ['16:00', 'გარე თამაშები'], ['18:00', 'გატანა']],
  },
  {
    key: '4-5', label: '4-5 წელი', color: '#A9D3C9',
    desc: 'აღმოჩენების ჯგუფი — ბუნება, კითხვა და მუსიკის საწყისები.',
    teacher: 'თამარ გელაშვილი', free: 4, total: 20,
    schedule: [['08:00', 'მიღება'], ['09:30', 'საუზმე'], ['10:00', 'საგანმანათლებლო აქტივობა'], ['12:30', 'სადილი'], ['13:00', 'დასვენება'], ['16:00', 'სპორტული აქტივობა'], ['18:00', 'გატანა']],
  },
  {
    key: '5-6', label: '5-6 წელი', color: '#7EB5C1',
    desc: 'სკოლისწინა ჯგუფი — ლოგიკური აზროვნება და საწყისი წერა-კითხვა.',
    teacher: 'ნინო ბერიძე', free: 2, total: 20,
    schedule: [['08:00', 'მიღება'], ['09:15', 'საუზმე'], ['10:00', 'წერა-კითხვის საწყისები'], ['12:30', 'სადილი'], ['13:00', 'დასვენება'], ['15:30', 'ლოგიკური თამაშები'], ['18:00', 'გატანა']],
  },
];

let activeGroup = '3-4';
const groupTabs = document.getElementById('groupTabs');
const groupSummary = document.getElementById('groupSummary');
const groupSchedule = document.getElementById('groupSchedule');
const admissionGroups = document.getElementById('admissionGroups');

function renderGroups() {
  const group = groups.find((item) => item.key === activeGroup) || groups[1];
  if (groupTabs) {
    groupTabs.innerHTML = groups.map((item) => `<button type="button" class="${item.key === group.key ? 'active' : ''}" data-group="${item.key}">${item.label}</button>`).join('');
    groupTabs.querySelectorAll('button').forEach((button) => button.addEventListener('click', () => { activeGroup = button.dataset.group; renderGroups(); }));
  }
  if (groupSummary) {
    groupSummary.style.background = group.color;
    groupSummary.innerHTML = `<span class="section-badge" style="background:#fff">${group.label}</span><h2>ჯგუფის შესახებ</h2><p>${group.desc}</p><div class="group-meta"><div><span>ხელმისაწვდომი ადგილები</span><strong>${group.free} / ${group.total}</strong></div><div><span>აღმზრდელი</span><strong>${group.teacher}</strong></div></div>`;
  }
  if (groupSchedule) {
    groupSchedule.innerHTML = group.schedule.map(([time, activity]) => `<div class="schedule-row"><time>${time}</time><strong>${activity}</strong></div>`).join('');
  }
}

function renderAdmissionGroups() {
  if (!admissionGroups) return;
  admissionGroups.innerHTML = groups.map((item, index) => `<label class="choice ${index === 1 ? 'active' : ''}"><input type="radio" name="preferred_group" value="${item.key}" ${index === 1 ? 'checked' : ''}>${item.label}</label>`).join('');
  admissionGroups.querySelectorAll('label').forEach((label) => label.addEventListener('click', () => {
    admissionGroups.querySelectorAll('label').forEach((item) => item.classList.remove('active'));
    label.classList.add('active');
  }));
}
renderGroups();
renderAdmissionGroups();

document.querySelectorAll('.choice-row').forEach((row) => {
  row.querySelectorAll('.choice').forEach((choice) => choice.addEventListener('click', () => {
    row.querySelectorAll('.choice').forEach((item) => item.classList.remove('active'));
    choice.classList.add('active');
  }));
});

const staff = [
  ['ნინო ბერიძე', 'დირექტორი', 'პედაგოგი 15 წლიანი გამოცდილებით.', 'ნ', '#A9D3C9'],
  ['მარიამ ხარაზი', 'აღმზრდელი · 2-3 წელი', 'ადრეული განვითარების სპეციალისტი.', 'მ', '#D3BDD3'],
  ['ანა წერეთელი', 'აღმზრდელი · 3-4 წელი', 'შემოქმედებითი აქტივობების წამყვანი.', 'ა', '#EFE6A9'],
  ['თამარ გელაშვილი', 'აღმზრდელი · 4-5 წელი', 'მუსიკის და ხელოვნების პედაგოგი.', 'თ', '#EFC49A'],
  ['გიორგი ლომიძე', 'სპორტის მასწავლებელი', 'აქტიური თამაშები და მოძრაობა.', 'გ', '#7EB5C1'],
  ['ეკა ონიანი', 'ფსიქოლოგი', 'ინდივიდუალური მიდგომა თითოეულ ბავშვს.', 'ე', '#CCE8C4'],
];
const teamGrid = document.getElementById('teamGrid');
if (teamGrid) teamGrid.innerHTML = staff.map(([name, role, bio, initial, color]) => `<article class="team-card"><div class="team-avatar" style="background:${color}">${initial}</div><h3>${name}</h3><small>${role}</small><p>${bio}</p></article>`).join('');

const photos = [
  ['ზაფხულის სახალისო დღე', '10 ივლისი, 2026', 'ყველა ჯგუფი', '#A9D3C9'],
  ['ხელოვნების გაკვეთილი', '5 ივლისი, 2026', '3-4 წელი', '#D3BDD3'],
  ['ეზოში თამაშები', '1 ივლისი, 2026', '2-3 წელი', '#EFE6A9'],
  ['ღია კარის დღე', '24 ივნისი, 2026', 'ყველა ჯგუფი', '#EFC49A'],
  ['მუსიკის საათი', '18 ივნისი, 2026', '4-5 წელი', '#CCE8C4'],
  ['ბაღის დღესასწაული', '10 ივნისი, 2026', '5-6 წელი', '#7EB5C1'],
];
const galleryGrid = document.getElementById('galleryGrid');
if (galleryGrid) galleryGrid.innerHTML = photos.map(([title, date, group, color]) => `<article class="gallery-card"><div class="gallery-art" style="background:${color}">ფოტო</div><div class="gallery-copy"><small>${group} · ${date}</small><h3>${title}</h3></div></article>`).join('');

const posts = [
  ['როგორ ვამზადოთ ბავშვი ბაღისთვის — 5 რჩევა', 'პირველი დღეები ბაღში შეიძლება რთული იყოს — ვიზიარებთ პრაქტიკულ რჩევებს.', '8 ივლისი, 2026', 'აღზრდა', '#A9D3C9'],
  ['ჯანსაღი კვება პატარებისთვის', 'რას ვთავაზობთ ბავშვებს ბაღში და როგორ შევქმნათ თბილი კვების რიტმი სახლშიც.', '2 ივლისი, 2026', 'კვება', '#EFE6A9'],
  ['თამაშის მნიშვნელობა 3-4 წლის ასაკში', 'თამაშით ბავშვი სოციალურ უნარებს, ენას და ემოციურ ბალანსს იძენს.', '25 ივნისი, 2026', 'განვითარება', '#D3BDD3'],
  ['სკოლისთვის მზადება — რას აქცევს ბაღი ყურადღებას', 'ლოგიკური აზროვნება, ინდივიდუალობა და პასუხისმგებლობა.', '18 ივნისი, 2026', 'სკოლა', '#EFC49A'],
];
const blogGrid = document.getElementById('blogGrid');
if (blogGrid) blogGrid.innerHTML = posts.map(([title, excerpt, date, category, color]) => `<article class="blog-card"><div class="blog-art" style="background:${color}">${category}</div><div class="blog-copy"><small>${date} · ${category}</small><h3>${title}</h3><p>${excerpt}</p></div></article>`).join('');

const faqs = [
  ['როგორ ხდება ჩარიცხვა?', 'ჩარიცხვის დასაწყებად შეავსეთ ონლაინ განაცხადი. განაცხადის მიღების შემდეგ ჩვენი ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და ვიზიტის დროს შეგითანხმებთ.'],
  ['რა საბუთებია საჭირო?', 'ჩარიცხვისთვის საჭიროა ბავშვის დაბადების მოწმობის ასლი, ჯანმრთელობის ცნობა და მშობლის ან კანონიერი წარმომადგენლის პირადობის დამადასტურებელი დოკუმენტი. სრულ ჩამონათვალს ადმინისტრაცია რეგისტრაციის პროცესში მოგაწვდით.'],
  ['რომელი მეთოდით ვმუშაობთ?', 'სასწავლო პროცესი ეფუძნება თამაშით სწავლებას, მონტესორის მეთოდის ელემენტებსა და ბავშვის ასაკობრივ და ინდივიდუალურ საჭიროებებზე მორგებულ მიდგომებს.'],
  ['სად შემიძლია საფასურის დეტალების ნახვა?', 'საფასურის, პროგრამებისა და მომსახურების პირობების შესახებ სრულ ინფორმაციას მიიღებთ ადმინისტრაციასთან კონსულტაციისას ან ავტორიზაციის შემდეგ — თქვენს პირად პროფილში.'],
  ['რა არის მშობელთა კლუბი?', 'მშობელთა კლუბი არის „ინეს ბაღის“ დახურული სივრცე, რომელიც აერთიანებს მშობლებსა და ბაღის გუნდს. კლუბის წევრებს შესაძლებლობა აქვთ მიიღონ მნიშვნელოვანი სიახლეები, ჩაერთონ თემატურ შეხვედრებში, ღონისძიებებსა და გამოკითხვებში, ერთმანეთს გაუზიარონ გამოცდილება და აქტიურად მიიღონ მონაწილეობა ბაღის ყოველდღიურ ცხოვრებაში.'],
];
const faqList = document.getElementById('faqList');
if (faqList) {
  faqList.innerHTML = faqs.map(([question, answer], index) => `<article class="faq-item ${index === 0 ? 'open' : ''}"><button type="button"><span>${question}</span><span class="faq-plus">+</span></button><div class="faq-answer">${answer}</div></article>`).join('');
  faqList.querySelectorAll('.faq-item').forEach((item) => item.querySelector('button')?.addEventListener('click', () => {
    faqList.querySelectorAll('.faq-item').forEach((other) => { if (other !== item) other.classList.remove('open'); });
    item.classList.toggle('open');
  }));
}

const registrationForm = document.getElementById('registrationForm');
registrationForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const status = document.getElementById('registrationStatus');
  const button = registrationForm.querySelector('button[type="submit"]');
  const form = new FormData(registrationForm);
  status.className = 'form-status';
  button.disabled = true;
  try {
    const data = await post(window.ines.routes.admission, {
      parent_name: form.get('parent_name'),
      phone: form.get('phone'),
      child_name: form.get('child_name'),
      birth_year: form.get('birth_year') || null,
      preferred_group: form.get('preferred_group'),
      academic_year: form.get('academic_year'),
      wants_tour: form.get('wants_tour') === '1',
      preferred_tour_date: form.get('preferred_tour_date') || null,
      comment: form.get('comment') || null,
    });
    status.textContent = `გმადლობთ! განაცხადი მიღებულია. თქვენი განაცხადის ნომერია #${data.application_id}. ადმინისტრაცია მალე დაგიკავშირდებათ.`;
    status.className = 'form-status ok show';
    registrationForm.reset();
    renderAdmissionGroups();
  } catch (error) {
    status.textContent = error.message;
    status.className = 'form-status error show';
  } finally {
    button.disabled = false;
  }
});

const loginModal = document.getElementById('loginModal');
const loginStatus = document.getElementById('loginStatus');
let authMode = { demo_enabled: false, admin_phone: '555411831' };
let requestId = null;
let loginName = '';
let loginPhone = '';

function openLogin() { loginModal?.classList.add('open'); document.body.classList.add('body-lock'); }
function closeLogin() { loginModal?.classList.remove('open'); document.body.classList.remove('body-lock'); }
document.querySelectorAll('[data-open-login]').forEach((button) => button.addEventListener('click', openLogin));
document.querySelector('[data-close-login]')?.addEventListener('click', closeLogin);
loginModal?.addEventListener('click', (event) => { if (event.target === loginModal) closeLogin(); });
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeLogin(); });

function showLoginStatus(message, type = 'error') {
  if (!loginStatus) return;
  loginStatus.textContent = message;
  loginStatus.className = `form-status ${type} show`;
}

async function loadAuthMode() {
  try {
    const response = await fetch(window.ines.routes.demoStatus, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    authMode = await response.json();
    if (authMode.demo_enabled) {
      document.getElementById('loginLead').textContent = 'SMS კოდი დროებით გამორთულია. შეიყვანეთ სახელი და ტელეფონის ნომერი და პირდაპირ გადადით კაბინეტში.';
      document.getElementById('loginSubmit').textContent = 'კოდის გარეშე შესვლა →';
      const note = document.getElementById('demoAuthNote');
      note.hidden = false;
      note.textContent = `დემო ადმინისტრატორი: ${authMode.admin_phone}. სხვა სწორი ქართული ნომერი მშობლის კაბინეტს გახსნის.`;
    }
  } catch (_) { /* OTP fallback remains available. */ }
}
loadAuthMode();

document.getElementById('otpRequest')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  loginStatus.className = 'form-status';
  const form = new FormData(event.currentTarget);
  loginName = form.get('name');
  loginPhone = form.get('phone');
  try {
    if (authMode.demo_enabled) {
      const data = await post(window.ines.routes.demoLogin, { name: loginName, phone: loginPhone });
      showLoginStatus(data.user.role === 'admin' ? 'ადმინისტრატორის კაბინეტი იხსნება.' : 'მშობლის კაბინეტი იხსნება.', 'ok');
      setTimeout(() => { window.location.href = data.redirect_to || '/'; }, 450);
      return;
    }
    const data = await post(window.ines.routes.request, { name: loginName, phone: loginPhone });
    requestId = data.request_id;
    document.getElementById('loginStepOne').hidden = true;
    document.getElementById('loginStepTwo').hidden = false;
    document.getElementById('otpPhone').textContent = loginPhone;
    if (data.debug_code) {
      const debug = document.getElementById('debugCode');
      debug.hidden = false;
      debug.textContent = `სადემონსტრაციო კოდი: ${data.debug_code}`;
    }
  } catch (error) { showLoginStatus(error.message); }
});

document.getElementById('otpVerify')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const code = new FormData(event.currentTarget).get('code');
  try {
    const data = await post(window.ines.routes.verify, { request_id: requestId, name: loginName, phone: loginPhone, code });
    showLoginStatus('შესვლა წარმატებულია.', 'ok');
    setTimeout(() => { window.location.href = data.redirect_to || '/'; }, 450);
  } catch (error) { showLoginStatus(error.message); }
});
