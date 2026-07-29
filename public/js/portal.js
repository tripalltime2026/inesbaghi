const tabButtons=[...document.querySelectorAll('[data-club-tab]')];
const panels=[...document.querySelectorAll('[data-club-panel]')];
function showClubTab(name){
  tabButtons.forEach(button=>button.classList.toggle('active',button.dataset.clubTab===name));
  panels.forEach(panel=>panel.classList.toggle('active',panel.dataset.clubPanel===name));
  history.replaceState(null,'',`#${name}`);
  window.scrollTo({top:0,behavior:'smooth'});
}
tabButtons.forEach(button=>button.addEventListener('click',()=>showClubTab(button.dataset.clubTab)));
document.querySelectorAll('[data-club-tab-link]').forEach(button=>button.addEventListener('click',()=>showClubTab(button.dataset.clubTabLink)));
showClubTab(location.hash.replace('#','')||'feed');
