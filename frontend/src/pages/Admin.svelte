<script>
  import {api} from '../api.js';
  import {useData, values} from '../lib/data.svelte.js';
  import Heading from '../components/Heading.svelte';
  import Wait from '../components/Wait.svelte';
  import Field from '../components/Field.svelte';
  import Form from '../components/Form.svelte';
  import Action from '../components/Action.svelte';
  import DecisionForm from '../components/DecisionForm.svelte';
  let {categories, onCategories} = $props();
  let version = $state(0);
  let tab = $state('Campanhas');
  const resource = useData(() => 'admin', () => version);
  async function update(path, data, method) {
    await api('admin/' + path, data, method);
    version++;
    onCategories();
  }
</script>

<Heading label="ADMINISTRAÇÃO">Painel administrativo</Heading>
<div class="tabs">{#each ['Campanhas', 'Organizadores', 'Usuários', 'Categorias'] as name}<button class={tab === name ? 'selected' : 'secondary'} onclick={() => { tab = name; version++; }}>{name}</button>{/each}</div>
{#if !resource.data}<Wait {resource} />
{:else if tab === 'Campanhas'}
  {#each resource.data.campaigns as campaign (campaign.id)}
    <article><small>{campaign.status} · {campaign.public_name}</small><h3><a href={'#/campanha/' + campaign.id}>{campaign.title} ↗</a></h3>
      {#if campaign.status === 'pendente'}<DecisionForm path={'campanhas/' + campaign.id} {update} />{/if}
    </article>
  {/each}
{:else if tab === 'Organizadores'}
  {#each resource.data.organizers as organizer (organizer.user_id)}
    <article><small>{organizer.status}</small><h3>{organizer.public_name}</h3><p>{organizer.bio}</p>
      {#if organizer.status === 'pendente'}<a href={'/api/admin/documentos/' + organizer.user_id}>Baixar documento para análise</a><DecisionForm path={'organizadores/' + organizer.user_id} {update} />{/if}
    </article>
  {/each}
{:else if tab === 'Usuários'}
  {#each resource.data.users as user (user.id)}
    <article><small>{user.role} · {user.suspended ? 'suspensa' : 'ativa'}</small><h3>{user.name}</h3><p>{user.email}</p>
      {#if user.role !== 'administrador'}<Action run={() => update('usuarios/' + user.id, {suspended: !user.suspended})}>{user.suspended ? 'Reativar' : 'Suspender'} conta</Action>{/if}
    </article>
  {/each}
{:else if tab === 'Categorias'}
  <Form button="Adicionar categoria" submit={form => update('categorias', values(form))}><Field label="Nova categoria" name="name" required /></Form>
  {#each categories as category (category.id)}
    <article><Form submit={form => update('categorias/' + category.id, values(form))}><Field label="Nome da categoria" name="name" value={category.name} required /></Form><Action kind="secondary" run={() => update('categorias/' + category.id, {}, 'DELETE')}>Excluir</Action></article>
  {/each}
{/if}
