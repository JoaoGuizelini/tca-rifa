<script>
  import {api, money} from '../api.js';
  import {values} from '../lib/data.svelte.js';
  import Action from './Action.svelte';
  import Form from './Form.svelte';
  import Field from './Field.svelte';
  let {campaign, onEdit, onChange} = $props();
  let draft = $derived(['rascunho', 'rejeitada'].includes(campaign.status));
  let live = $derived(campaign.status === 'ativa');
  async function change(action, data = {}) {
    await api('estudio/campanhas/' + campaign.id + '/status', {action, ...data});
    onChange();
  }
</script>

<article>
  <small>{campaign.status} · {money(campaign.price)} por número</small><h3><a href={'#/campanha/' + campaign.id}>{campaign.title}</a></h3>
  {#if campaign.reason}<p>{campaign.reason}</p>{/if}
  <div class="links">
    {#if draft}<button class="secondary" onclick={() => onEdit(campaign)}>Editar</button><Action run={() => change('enviar')}>Enviar para aprovação</Action>{/if}
    {#if !['encerrada', 'cancelada'].includes(campaign.status)}
      <Action kind="secondary" run={() => { if (window.confirm('Cancelar esta campanha?')) return change('cancelar'); }}>Cancelar</Action>
    {/if}
  </div>
  {#if live}
    <details><summary>Publicar resultado</summary><Form button="Publicar número contemplado" submit={form => change('resultado', values(form))}><Field label="Número vencedor confirmado" name="winner" type="number" min="1" required /></Form></details>
  {/if}
</article>
