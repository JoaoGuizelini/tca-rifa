<script>let {resource} = $props();</script>

<p role={resource.error ? 'alert' : 'status'}>{resource.error || 'Carregando…'}</p>
