<script>
  let {submit, children, button = 'Salvar', success = 'Alterações salvas.'} = $props();
  let busy = $state(false);
  let message = $state('');
  let failed = $state(false);

  async function send(event) {
    event.preventDefault();
    if (busy) return;
    const data = new FormData(event.currentTarget);
    busy = true;
    message = '';
    failed = false;
    try {
      await submit(data);
      message = success;
    } catch (error) {
      failed = true;
      message = error.message;
    } finally {
      busy = false;
    }
  }
</script>

<form class="form" onsubmit={send}>
  {@render children?.()}
  <button type="submit" disabled={busy}>{busy ? 'Aguarde…' : button}</button>
  {#if message}<p class="feedback" role={failed ? 'alert' : 'status'}>{message}</p>{/if}
</form>
