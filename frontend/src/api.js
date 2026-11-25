export async function api(path,data,method){
 const options={credentials:'same-origin',headers:{Accept:'application/json'},method:method||(data?'POST':'GET')};
 if(options.method!=='GET'){
    const csrf=await fetch('/api/csrf',{credentials:'same-origin'}); options.headers['X-CSRF-TOKEN']=(await csrf.json()).token;
    if(data instanceof FormData) options.body=data;
    else { options.headers['Content-Type']='application/json'; options.body=JSON.stringify(data||{}); }
 }
 const res=await fetch('/api/'+path,options);
 if(!res.ok){
    const body=await res.json().catch(()=>({})); if(res.status===401) window.dispatchEvent(new Event('session-expired'));
    throw Error(Object.values(body.errors||{}).flat().join(' ')||body.message||'Falha ao comunicar com a API.');
 }
 return res.status===204?null:res.json();
}
export const money=cents=>(Number(cents)/100).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
export const day=value=>value?.slice(0,10).split('-').reverse().join('/');
export const photos=c=>Array.isArray(c.photos)?c.photos:JSON.parse(c.photos||'[]');
export const nums=value=>Array.isArray(value)?value:JSON.parse(value||'[]');
