<script>
  import {onMount} from 'svelte';
  import {api, money, nums} from '../api.js';
  import {useData} from '../lib/data.svelte.js';
  import Heading from '../components/Heading.svelte';
  import Wait from '../components/Wait.svelte';
  import Action from '../components/Action.svelte';
  let version = $state(0);
  const resource = useData(() => 'area', () => version);
  onMount(() => { const timer = setInterval(() => version++, 60000); return () => clearInterval(timer); });
  const expires = value => new Date(value.replace(' ', 'T') + 'Z').toLocaleString('pt-BR');
</script>

<Heading label="PARTICIPANTE">Minha área</Heading>
<h2>Minhas reservas</h2>
{#if !resource.data}<Wait {resource} />
{:else}
  {#each resource.data.reservations as reservation (reservation.id)}
    <article>
      <small>RESERVA #{reservation.id} · {reservation.status}</small><h3><a href={'#/campanha/' + reservation.campaign_id}>{reservation.title} ↗</a></h3>
      <p>Números {nums(reservation.numbers).join(', ')} · {money(reservation.total)}</p>
      {#if reservation.winner}<p>Resultado publicado · Nº {reservation.winner}</p>{/if}
      {#if reservation.status === 'reservada'}<p>Expira em {expires(reservation.expires)}</p><Action run={async () => { await api('reservas/' + reservation.id + '/confirmar', {}); version++; }}>Confirmar participação</Action>{/if}
    </article>
  {:else}<p>Suas reservas aparecerão aqui. <a href="#/">Explorar campanhas</a></p>{/each}
{/if}
