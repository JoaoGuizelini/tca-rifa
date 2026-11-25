<script>
  import {photos} from '../api.js';
  let {campaign} = $props();
  let source = $derived(photos(campaign)[0]);
</script>

{#if source}
  <img class="art" src={source} alt={campaign.prize} />
{:else}
  <div class="art placeholder">{campaign.prize}</div>
{/if}
