<script>
  import {onMount} from 'svelte';
  import {api} from './api.js';
  import Auth from './pages/Auth.svelte';
  import Shell from './Shell.svelte';

  let user = $state(undefined);
  let hash = $state(location.hash);
  let error = $state('');
  let route = $derived(hash.replace(/^#\/?/, '').split('?')[0].split('/'));

  function navigate() {
    hash = location.hash;
    window.scrollTo(0, 0);
  }
  function expired() { user = null; }
  onMount(() => {
    window.addEventListener('session-expired', expired);
    let active = true;
    api('eu').then(result => { if (active) user = result; })
      .catch(cause => { if (active && user !== null) error = cause.message; });
    return () => { active = false; window.removeEventListener('session-expired', expired); };
  });
</script>

<svelte:window onhashchange={navigate} />

{#if user === undefined}
  <p class="wrap" role="status">{error || 'Abrindo Bilhete…'}</p>
{:else if user}
  <Shell {user} onUser={value => user = value} page={route[0] || ''} id={route[1]} />
{:else}
  <Auth page={route[0] || ''} onUser={value => user = value} />
{/if}
