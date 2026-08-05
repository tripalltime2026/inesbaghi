const tabButtons=[...document.querySelectorAll('[data-club-tab]')];
const panels=[...document.querySelectorAll('[data-club-panel]')];
function normalizeClubTab(hash){
  if(hash.startsWith('forum-topic-')||hash.startsWith('my-topic-'))return 'forum';
  return tabButtons.some(button=>button.dataset.clubTab===hash)?hash:'feed';
}
function showClubTab(name,updateHash=true){
  const resolved=normalizeClubTab(name);
  tabButtons.forEach(button=>button.classList.toggle('active',button.dataset.clubTab===resolved));
  panels.forEach(panel=>panel.classList.toggle('active',panel.dataset.clubPanel===resolved));
  if(updateHash)history.replaceState(null,'',`#${resolved}`);
  window.scrollTo({top:0,behavior:'smooth'});
}
tabButtons.forEach(button=>button.addEventListener('click',()=>showClubTab(button.dataset.clubTab)));
document.querySelectorAll('[data-club-tab-link]').forEach(button=>button.addEventListener('click',()=>showClubTab(button.dataset.clubTabLink)));
const initialHash=location.hash.replace('#','');
showClubTab(initialHash||'feed',!initialHash);
if(initialHash.startsWith('forum-topic-')){
  const targetId=initialHash;
  const observer=new MutationObserver(()=>{
    const target=document.getElementById(targetId);
    if(target){target.scrollIntoView({behavior:'smooth',block:'center'});observer.disconnect();}
  });
  observer.observe(document.body,{childList:true,subtree:true});
  window.setTimeout(()=>observer.disconnect(),10000);
}
