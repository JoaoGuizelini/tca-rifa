<script>
  import {useData} from '../lib/data.svelte.js';
  import Heading from '../components/Heading.svelte';
  import Cards from '../components/Cards.svelte';
  import Wait from '../components/Wait.svelte';
  let {categories} = $props();
  let query = $state('');
  let category = $state('');
  let sort = $state('recentes');
  const resource = useData(() => 'campanhas?' + new URLSearchParams({q: query, category, sort}));
  let list = $derived(resource.data || []);
</script>

<Heading label="MARKETPLACE">Explore as campanhas</Heading>
<section>
  <h2>Todas as rifas</h2>
  <div class="filters">
    <input aria-label="Pesquisar campanha" placeholder="Pesquisar por nome…" bind:value={query} />
    <select aria-label="Categoria" bind:value={category}><option value="">Todas as categorias</option>{#each categories as item (item.id)}<option value={String(item.id)}>{item.name}</option>{/each}</select>
    <select aria-label="Ordenação" bind:value={sort}><option value="recentes">Mais recentes</option><option value="popular">Mais populares</option></select>
  </div>
  {#if resource.data}<Cards {list} />{:else}<Wait {resource} />{/if}
</section>
