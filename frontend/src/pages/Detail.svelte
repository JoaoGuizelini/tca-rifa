<script>
  import {api, day, money} from '../api.js';
  import {useData} from '../lib/data.svelte.js';
  import Heading from '../components/Heading.svelte';
  import Art from '../components/Art.svelte';
  import Wait from '../components/Wait.svelte';
  import Form from '../components/Form.svelte';
  let {id, user} = $props();
  let version = $state(0);
  let picked = $state([]);
  let page = $state(0);
  const resource = useData(() => 'campanhas/' + id, () => version);
  let campaign = $derived(resource.data?.campaign);
  let canBook = $derived(resource.data?.open && ['participante', 'organizador'].includes(user.role) && campaign?.owner_id !== user.id);
  function toggle(number) { picked = picked.includes(number) ? picked.filter(n => n !== number) : [...picked, number]; }
  async function reserve() {
    if (!picked.length) throw Error('Selecione pelo menos um número.');
    try { await api('campanhas/' + id + '/reservar', {numbers: picked}); location.hash = '/area'; }
    finally { picked = []; version++; }
  }
</script>

{#if !resource.data}<Wait {resource} />
{:else}
  <a href="#/">← Campanhas</a>
  <Heading label={campaign.category + ' · ' + campaign.status}>{campaign.title}</Heading>
  <div class="detail">
    <div>
      <Art {campaign} />
      <h2>Sobre a campanha</h2><p>{campaign.description}</p><h3>Prêmio</h3><p>{campaign.prize}</p><h3>Regulamento</h3><p>{campaign.rules}</p>
    </div>
    <aside class="box">
      <small>ORGANIZADOR</small><h3><a href={'#/organizador/' + campaign.owner_id}>{campaign.public_name} ↗</a></h3>
      <p>{day(campaign.starts)} a {day(campaign.ends)}</p><h2>{money(campaign.price)}</h2><p>por número · {campaign.quantity} números</p>
      {#if campaign.winner}<h3>Resultado · Nº {campaign.winner}</h3>{/if}
    </aside>
  </div>
  <section>
    <h2>Escolha seus números</h2><p>Disponível · <b class="amber">Reservado</b> · <b class="gray">Indisponível</b></p>
    <div class="numbers">
      {#each resource.data.tickets.slice(page * 100, (page + 1) * 100) as ticket (ticket.number)}
        <button type="button" disabled={!canBook || !!ticket.status || (picked.length >= 50 && !picked.includes(ticket.number))}
          aria-pressed={picked.includes(ticket.number)} title={ticket.status || 'Disponível'}
          class={ticket.status || (picked.includes(ticket.number) ? 'picked' : 'available')} onclick={() => toggle(ticket.number)}>
          {String(ticket.number).padStart(3, '0')}
        </button>
      {/each}
    </div>
    {#if resource.data.tickets.length > 100}
      <div class="links"><button disabled={!page} onclick={() => page--}>Anterior</button><button disabled={(page + 1) * 100 >= resource.data.tickets.length} onclick={() => page++}>Próxima</button></div>
    {/if}
    {#if canBook}
      <Form submit={reserve} button={'Reservar · ' + money(picked.length * campaign.price)}><span>{picked.length} selecionado(s) · Reserva válida por 15 minutos</span></Form>
    {/if}
  </section>
{/if}
