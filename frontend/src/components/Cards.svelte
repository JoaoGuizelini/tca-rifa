<script>
  import Art from './Art.svelte';
  import {money} from '../api.js';
  let {list = []} = $props();
</script>

{#if list.length}
  <div class="cards">
    {#each list as campaign (campaign.id)}
      <a class="card" href={'#/campanha/' + campaign.id}>
        <Art {campaign} />
        <div class="cardbody">
          <small>{campaign.category} · {campaign.status}</small>
          <h3>{campaign.title}</h3><p>{campaign.public_name}</p>
          <div class="foot"><strong>{money(campaign.price)}</strong><span>por número ↗</span></div>
        </div>
      </a>
    {/each}
  </div>
{:else}
  <p>Nenhuma campanha encontrada.</p>
{/if}
