<script>
  let {label, area = false, value = '', type = 'text', ...attributes} = $props();
</script>

<label>
  {label}
  {#if area}
    <textarea {...attributes} {value}></textarea>
  {:else if type === 'file'}
    <input type="file" {...attributes} />
  {:else}
    <input {type} {...attributes} {value} />
  {/if}
</label>
