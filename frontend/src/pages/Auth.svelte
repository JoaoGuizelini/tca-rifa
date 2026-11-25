<script>
  import {api} from '../api.js';
  import {values} from '../lib/data.svelte.js';
  import Field from '../components/Field.svelte';
  import Form from '../components/Form.svelte';
  let {page, onUser} = $props();
  let mode = $derived(page === 'cadastro' ? 'cadastro' : 'login');
  async function submit(form) {
    const user = await api(mode, values(form));
    onUser(user);
    location.hash = '/';
  }
</script>

<div class="auth">
  <aside><span class="brand">▧ bilhete</span><div class="poster">
    <small>OFICINA AURORA</small><img src="/art/bike.svg" alt="Ilustração de uma bicicleta urbana" />
    <h2>Bicicleta<br />de passeio</h2><div class="perforation"></div><span>ESPORTE <b>№ 001</b></span>
  </div></aside>
  <main><div><small>ACESSO À PLATAFORMA</small><h1>{mode === 'login' ? 'Boas-vindas.' : 'Crie sua conta.'}</h1>
    {#key mode}<Form {submit} button={mode === 'login' ? 'Entrar' : 'Cadastrar'} success="Concluído.">
      {#if mode === 'cadastro'}<Field label="Nome" name="name" required />{/if}
      <Field label="E-mail" type="email" name="email" required />
      {#if mode === 'cadastro'}<Field label="CPF" name="cpf" required /><Field label="Telefone com DDD" name="phone" required />{/if}
      <Field label={mode === 'login' ? 'Senha' : 'Senha (mínimo de 10 caracteres)'} type="password" name="password" minlength={mode === 'login' ? 1 : 10} required />
      {#if mode === 'cadastro'}<Field label="Confirme a senha" type="password" name="password_confirmation" required />{/if}
    </Form>{/key}
    <div class="links">{#if mode === 'login'}<a href="#/cadastro">Criar conta</a>{:else}<a href="#/login">Voltar para entrar</a>{/if}</div>
  </div></main>
</div>
