<script>
  import Heading from '../components/Heading.svelte';
  let {user} = $props();
</script>
<Heading label={user.role}>Meu perfil</Heading>
<article><dl>
  <dt>Nome</dt><dd>{user.name}</dd>
  <dt>E-mail</dt><dd>{user.email}</dd>
  <dt>CPF</dt><dd>{user.cpf}</dd>
  <dt>Telefone</dt><dd>{user.phone}</dd>
</dl></article>
