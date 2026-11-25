<script>
  let {run, children, kind = ''} = $props();
  let busy = $state(false);
  let message = $state('');
  async function perform() {
    if (busy) return;
    busy = true;
    message = '';
    try { await run(); }
    catch (error) { message = error.message; }
    finally { busy = false; }
  }
</script>

<span>
  <button type="button" class={kind} disabled={busy} onclick={perform}>{@render children?.()}</button>
  {#if message}<small role="alert">{message}</small>{/if}
</span>
