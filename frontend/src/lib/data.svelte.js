import {api} from '../api.js';

export function useData(getPath, getVersion = () => 0) {
  const resource = $state({data: null, error: ''});
  $effect(() => {
    const path = getPath();
    getVersion();
    resource.data = null;
    resource.error = '';
    let active = true;
    api(path).then(data => { if (active) resource.data = data; })
      .catch(error => { if (active) resource.error = error.message; });
    return () => { active = false; };
  });
  return resource;
}

export const values = form => Object.fromEntries(form.entries());
