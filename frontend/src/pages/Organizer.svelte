<script>
  import {useData} from '../lib/data.svelte.js';
  import Heading from '../components/Heading.svelte';
  import Cards from '../components/Cards.svelte';
  import Wait from '../components/Wait.svelte';
  let {id} = $props();
  const resource = useData(() => 'organizadores/' + id);
</script>

{#if !resource.data}<Wait {resource} />
{:else}
  <Heading label="ORGANIZADOR VERIFICADO">{resource.data.organizer.public_name}</Heading><p>{resource.data.organizer.bio}</p>
  <h2>Histórico de campanhas</h2><Cards list={resource.data.campaigns} />
{/if}
