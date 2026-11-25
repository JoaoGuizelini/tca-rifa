<script>
  import {api} from '../api.js';
  import Form from './Form.svelte';
  import Field from './Field.svelte';
  let {campaign = {}, categories, onSave} = $props();
  async function submit(form) {
    form.set('price', String(Math.round(Number(form.get('reais')) * 100)));
    form.delete('reais');
    if (!form.get('photos[]')?.size) form.delete('photos[]');
    await api('estudio/campanhas' + (campaign.id ? '/' + campaign.id : ''), form);
    onSave();
  }
</script>

<Form {submit} button="Salvar rascunho">
  <Field label="Nome da campanha" name="title" value={campaign.title || ''} required />
  <Field label="Descrição" area name="description" value={campaign.description || ''} required />
  <Field label="Prêmio" name="prize" value={campaign.prize || ''} required />
  <label>Categoria<select aria-label="Categoria" name="category_id" value={campaign.category_id ?? categories[0]?.id} required>{#each categories as category (category.id)}<option value={category.id}>{category.name}</option>{/each}</select></label>
  <Field label="Quantidade de números" name="quantity" type="number" min="1" max="2000" value={campaign.quantity || 100} required />
  <Field label="Valor por número (R$)" name="reais" type="number" step=".01" min=".01" value={campaign.price ? campaign.price / 100 : 5} required />
  <Field label="Data inicial" type="date" name="starts" value={campaign.starts || ''} required />
  <Field label="Data final" type="date" name="ends" value={campaign.ends || ''} required />
  <Field label="Regulamento" area name="rules" value={campaign.rules || ''} required />
  <Field label="Foto do prêmio (uma imagem)" type="file" name="photos[]" accept="image/png,image/jpeg,image/webp" />
</Form>
