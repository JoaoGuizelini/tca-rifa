<script>
  import {onMount} from 'svelte';
  import {api} from '../api.js';
  import {useData} from '../lib/data.svelte.js';
  import Heading from '../components/Heading.svelte';
  import Wait from '../components/Wait.svelte';
  import Field from '../components/Field.svelte';
  import Form from '../components/Form.svelte';
  import CampaignForm from '../components/CampaignForm.svelte';
  import CampaignActions from '../components/CampaignActions.svelte';
  let {user, categories, onUser} = $props();
  let version = $state(0);
  let edit = $state(null);
  const resource = useData(() => 'estudio', () => version);
  let organizer = $derived(resource.data?.organizer);
  function reload() { edit = null; version++; }
  onMount(() => {
    let active = true;
    api('eu').then(current => { if (active) onUser(current); }).catch(() => {});
    return () => { active = false; };
  });
</script>

<Heading label="ORGANIZADOR">Meu estúdio</Heading>
{#if !resource.data}<Wait {resource} />
{:else}
  {#if organizer}<article><small>VERIFICAÇÃO · {organizer.status}</small><h3>{organizer.public_name}</h3><p>{organizer.reason || organizer.bio}</p></article>{/if}
  {#if user.role === 'participante' && (!organizer || organizer.status === 'rejeitado')}
    <Form button="Solicitar verificação" submit={async form => { await api('verificacao', form); reload(); }}>
      <h2>Cadastro de organizador</h2><Field label="Nome público" name="public_name" required /><Field label="Apresentação" area name="bio" required />
      <Field label="Documento de identidade (PDF, JPG ou PNG; até 5 MB)" type="file" name="document" accept=".png,.jpg,.jpeg,.pdf" required />
    </Form>
  {/if}
  {#if user.role === 'organizador'}
    <div class="row"><h2>Minhas campanhas</h2><button onclick={() => edit = {}}>Criar campanha</button></div>
    {#if edit}
      <article><h3>{edit.id ? 'Editar campanha' : 'Nova campanha'}</h3>
        {#key edit.id || 'new'}<CampaignForm campaign={edit} {categories} onSave={reload} />{/key}
        <button class="secondary" onclick={() => edit = null}>Fechar edição</button>
      </article>
    {/if}
    {#each resource.data.campaigns as campaign (campaign.id)}<CampaignActions {campaign} onEdit={value => edit = value} onChange={reload} />{/each}
  {/if}
{/if}
