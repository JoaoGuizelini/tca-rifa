<script>
  import {api} from './api.js';
  import {useData} from './lib/data.svelte.js';
  import Action from './components/Action.svelte';
  import Market from './pages/Market.svelte';
  import Detail from './pages/Detail.svelte';
  import Area from './pages/Area.svelte';
  import Organizer from './pages/Organizer.svelte';
  import Profile from './pages/Profile.svelte';
  import Studio from './pages/Studio.svelte';
  import Admin from './pages/Admin.svelte';

  let {user, onUser, page, id} = $props();
  let version = $state(0);
  const catalog = useData(() => 'categorias', () => version);
  let categories = $derived(catalog.data || []);
  let navigation = $derived([
    ['', 'Campanhas'], ['area', 'Minha área'],
    ...(user.role === 'administrador' ? [['admin', 'Administração']] : [['estudio', 'Estúdio']]),
    ['perfil', 'Perfil'],
  ]);
  async function logout() {
    await api('logout', {});
    onUser(null);
    location.hash = '/login';
  }
</script>

<header class="top">
  <a class="brand" href="#/">▧ bilhete</a>
  <nav>
    {#each navigation as [path, label] (path)}
      <a href={'#/' + path} aria-current={page === path ? 'page' : undefined}>{label}</a>
    {/each}
  </nav>
  <Action kind="secondary" run={logout}>Sair</Action>
</header>
<main class="wrap">
  {#if catalog.error}<p role="alert">{catalog.error}</p>{/if}
  {#key page + '/' + (id || '')}
  {#if page === 'area'}<Area />
  {:else if page === 'perfil'}<Profile {user} />
  {:else if page === 'estudio'}<Studio {user} {categories} {onUser} />
  {:else if page === 'admin'}
    {#if user.role === 'administrador'}<Admin {categories} onCategories={() => version++} />
    {:else}<p>Acesso restrito.</p>{/if}
  {:else if page === 'campanha'}{#key id}<Detail {id} {user} />{/key}
  {:else if page === 'organizador'}{#key id}<Organizer {id} />{/key}
  {:else}<Market {categories} />{/if}
  {/key}
</main>
<footer>Bilhete <span>Projeto acadêmico · Desenvolvimento Web</span></footer>
