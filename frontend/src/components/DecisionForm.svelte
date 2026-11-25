<script>
  import {values} from '../lib/data.svelte.js';
  import Form from './Form.svelte';
  import Field from './Field.svelte';
  let {path, update} = $props();
  function submit(form) {
    const data = values(form);
    return update(path, {...data, approve: data.approve === '1'});
  }
</script>

<Form {submit} button="Registrar decisão">
  <label>Decisão<select aria-label="Decisão" name="approve"><option value="1">Aprovar</option><option value="0">Rejeitar</option></select></label>
  <Field label="Justificativa" name="reason" required />
</Form>
