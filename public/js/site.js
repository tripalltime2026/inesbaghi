const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';
async function post(url,payload){
  const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(payload)});
  const data=await response.json().catch(()=>({}));
  if(!response.ok){throw new Error(data.errors?Object.values(data.errors).flat()[0]:data.message||'მოთხოვნა ვერ შესრულდა');}
  return data;
}
const programs=[
  {age:'2–3',title:'პირველი აღმოჩენები',desc:'პატარების პირველი ნაბიჯები დამოუკიდებლობისკენ — მეტყველება, სენსორული განვითარება, მოძრაობა და უსაფრთხო სოციალური ურთიერთობები.',teacher:'მარიამ ხარაზი',focus:'სენსორული განვითარება',spots:3,color:'#c8e3d8',value:'2-3'},
  {age:'3–4',title:'ვიცნობ სამყაროს',desc:'ცნობისმოყვარეობის გაძლიერება თამაშით, შემოქმედებითი აქტივობებითა და ყოველდღიური აღმოჩენებით.',teacher:'ანა ბერიძე',focus:'მეტყველება და შემოქმედება',spots:4,color:'#f2dda3',value:'3-4'},
  {age:'4–5',title:'ვფიქრობ და ვქმნი',desc:'ლოგიკური აზროვნების, კომუნიკაციისა და გუნდური მუშაობის განვითარება მრავალფეროვანი პროექტებით.',teacher:'თამარ კახიძე',focus:'აზროვნება და კომუნიკაცია',spots:2,color:'#dccce1',value:'4-5'},
  {age:'5–6',title:'მზად ვარ სკოლისთვის',desc:'სასკოლო მზაობის პროგრამა — ყურადღება, დამოუკიდებლობა, კითხვა-წერის წინარე უნარები და თავდაჯერებულობა.',teacher:'ნინო ქავთარაძე',focus:'სასკოლო მზაობა',spots:5,color:'#f2c8b2',value:'5-6'}
];
const faqs=[
  ['როგორ ხდება რეგისტრაცია?','რეგისტრაციის დასაწყებად შეავსეთ ონლაინ ფორმა. განაცხადის მიღების შემდეგ ჩვენი ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და გაცნობითი ვიზიტის დროს შეგითანხმებთ.'],
  ['რა საბუთებია საჭირო?','საჭიროა ბავშვის დაბადების მოწმობის ასლი, ჯანმრთელობის ცნობა და მშობლის ან კანონიერი წარმომადგენლის პირადობის დამადასტურებელი დოკუმენტი. სრულ ჩამონათვალს ადმინისტრაცია რეგისტრაციის პროცესში მოგაწვდით.'],
  ['რომელი მეთოდებით მუშაობთ?','სასწავლო პროცესი ეფუძნება თამაშით სწავლებას, მონტესორის მეთოდის ელემენტებსა და ბავშვის ასაკობრივ და ინდივიდუალურ საჭიროებებზე მორგებულ მიდგომებს.'],
  ['სად მივიღებ საფასურის შესახებ ინფორმაციას?','საფასურის, პროგრამებისა და მომსახურების პირობების შესახებ სრულ ინფორმაციას მიიღებთ ადმინისტრაციასთან კონსულტაციისას.'],
  ['რა არის მშობელთა კლუბი?','მშობელთა კლუბი არის „ინეს ბაღის“ დახურული სივრცე, რომელიც აერთიანებს მშობლებსა და ბაღის გუნდს. წევრები იღებენ მნიშვნელოვან სიახლეებს, ერთვებიან შეხვედრებში, ღონისძიებებსა და გამოკითხვებში და აქტიურად მონაწილეობენ ბაღის ცხოვრებაში.']
];
const tabs=document.getElementById('programTabs');
function renderProgram(i){
  const p=programs[i];
  tabs?.querySelectorAll('.tab').forEach((b,n)=>b.classList.toggle('active',n===i));
  const set=(id,value)=>{const el=document.getElementById(id);if(el)el.textContent=value};
  set('bigAge',p.age+' წელი');set('programTitle',p.title);set('programDesc',p.desc);set('teacher',p.teacher);set('focus',p.focus);set('availability','ხელმისაწვდომი ადგილები: '+p.spots);
  document.getElementById('programArt')?.style.setProperty('--program',p.color);
  document.querySelectorAll('[data-program-register]').forEach(btn=>btn.dataset.group=p.value);
}
programs.forEach((p,i)=>{const b=document.createElement('button');b.className='tab';b.type='button';b.textContent=p.age+' წელი';b.addEventListener('click',()=>renderProgram(i));tabs?.appendChild(b)});renderProgram(0);
const faqList=document.getElementById('faqList');
faqs.forEach(([q,a],i)=>{const el=document.createElement('article');el.className='faq-item'+(i===0?' open':'');el.innerHTML=`<button class="faq-q" type="button"><span>${q}</span><span class="plus">+</span></button><div class="faq-a"><p>${a}</p></div>`;el.querySelector('button').addEventListener('click',()=>{faqList.querySelectorAll('.faq-item').forEach(x=>{if(x!==el)x.classList.remove('open')});el.classList.toggle('open')});faqList?.appendChild(el)});
function modalController(modal,openSelector,closeSelector){
  if(!modal)return {open:()=>{},close:()=>{}};
  const open=()=>{modal.classList.add('open');document.body.classList.add('lock')};
  const close=()=>{modal.classList.remove('open');if(!document.querySelector('.modal.open'))document.body.classList.remove('lock')};
  document.querySelectorAll(openSelector).forEach(button=>button.addEventListener('click',open));
  document.querySelector(closeSelector)?.addEventListener('click',close);
  modal.addEventListener('click',event=>{if(event.target===modal)close()});
  return {open,close};
}
const registrationModal=modalController(document.getElementById('modal'),'[data-open]','#closeModal');
document.getElementById('doneBtn')?.addEventListener('click',registrationModal.close);
const loginModal=modalController(document.getElementById('loginModal'),'[data-open-login]','#closeLoginModal');
document.addEventListener('keydown',event=>{if(event.key==='Escape'){registrationModal.close();loginModal.close()}});
const registrationForm=document.getElementById('registrationForm');
registrationForm?.addEventListener('submit',async event=>{
  event.preventDefault();
  const status=document.getElementById('registrationStatus');const button=registrationForm.querySelector('button[type="submit"]');const form=new FormData(registrationForm);
  status.className='form-status';status.textContent='';button.disabled=true;
  try{
    const data=await post(window.ines.routes.admission,{parent_name:form.get('parent_name'),phone:form.get('phone'),child_name:form.get('child_name'),birth_year:form.get('birth_year')||null,preferred_group:form.get('preferred_group'),academic_year:form.get('academic_year'),wants_tour:form.get('wants_tour')==='1',preferred_tour_date:form.get('preferred_tour_date')||null,comment:form.get('comment')||null});
    document.getElementById('formWrap').style.display='none';document.getElementById('success')?.classList.add('show');
    const message=document.getElementById('successMessage');if(message)message.textContent=`თქვენი განაცხადი მიღებულია. განაცხადის ნომერია #${data.application_id}. ჩვენი ადმინისტრაცია დაგიკავშირდებათ.`;
    registrationForm.reset();
  }catch(error){status.textContent=error.message;status.className='form-status error show'}finally{button.disabled=false}
});
let requestId=null,loginName='',loginPhone='';const loginStatus=document.getElementById('loginStatus');
const showLoginStatus=(message,type='error')=>{loginStatus.textContent=message;loginStatus.className=`form-status ${type} show`};
document.getElementById('otpRequest')?.addEventListener('submit',async event=>{
  event.preventDefault();loginStatus.className='form-status';const form=new FormData(event.currentTarget);loginName=form.get('name');loginPhone=form.get('phone');
  try{const data=await post(window.ines.routes.request,{name:loginName,phone:loginPhone});requestId=data.request_id;document.getElementById('loginStepOne').hidden=true;document.getElementById('loginStepTwo').hidden=false;if(data.debug_code){const debug=document.getElementById('debugCode');debug.hidden=false;debug.textContent=`სატესტო კოდი: ${data.debug_code}`}}catch(error){showLoginStatus(error.message)}
});
document.getElementById('otpVerify')?.addEventListener('submit',async event=>{
  event.preventDefault();const code=new FormData(event.currentTarget).get('code');
  try{const data=await post(window.ines.routes.verify,{request_id:requestId,name:loginName,phone:loginPhone,code});showLoginStatus(data.user.status==='pending'?'ანგარიში შექმნილია და ადმინისტრატორის დამტკიცებას ელოდება.':'შესვლა წარმატებულია.','ok');setTimeout(()=>{window.location.href=data.redirect_to||'/'},650)}catch(error){showLoginStatus(error.message)}
});
const menuBtn=document.getElementById('menuBtn'),navLinks=document.getElementById('navLinks');
menuBtn?.addEventListener('click',()=>{navLinks?.classList.toggle('open');menuBtn.textContent=navLinks?.classList.contains('open')?'×':'☰'});
navLinks?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{navLinks.classList.remove('open');if(menuBtn)menuBtn.textContent='☰'}));
window.addEventListener('scroll',()=>document.getElementById('header')?.classList.toggle('scrolled',scrollY>15));
