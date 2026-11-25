import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {spawn, spawnSync} from 'node:child_process';
import {randomBytes} from 'node:crypto';
import {chromium} from 'playwright-core';

const frontend = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const project = path.dirname(frontend);
const backend = path.join(project, 'backend');
const database = 'bilhete_browser_test_' + randomBytes(8).toString('hex');
const artifacts = path.join(frontend, 'test-results', database);
fs.mkdirSync(artifacts, {recursive: true});
const envFile = fs.readFileSync(path.join(project, '.env'), 'utf8');
const rootPassword = envFile.match(/^DB_ROOT_PASSWORD=(.+)$/m)?.[1].trim();
assert(rootPassword, 'Configure o .env do Compose local antes do teste.');
const env = {...process.env, DB_CONNECTION: 'mysql', DB_DATABASE: database, DB_USERNAME: 'root', DB_PASSWORD: rootPassword,
  DB_HOST: '127.0.0.1', DB_PORT: '3306', BILHETE_BROWSER_DATABASE: database,
  LARAVEL_STORAGE_PATH: path.join(artifacts, 'storage'), SESSION_COOKIE: 'bilhete_browser_test_session',
  APP_ENV: 'local', APP_URL: 'http://localhost:5174', API_TARGET: 'http://127.0.0.1:8001'};
const base = 'http://localhost:5174';
const children = [];
let browser;
let currentPage;
const errors = [];
function fixture(mode) {
  const result = spawnSync('php', ['-d', 'extension=pdo_mysql', path.join(backend, 'tests/browser_database.php'), mode], {env, encoding: 'utf8'});
  assert.equal(result.status, 0, result.stderr || result.stdout);
  return result.stdout;
}
function start(command, args, cwd, label) {
  const child = spawn(command, args, {cwd, env, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe']});
  const log = fs.createWriteStream(path.join(artifacts, label + '.log'));
  child.stdout.pipe(log); child.stderr.pipe(log);
  children.push(child);
  return child;
}
async function ready(url) {
  const deadline = Date.now() + 20000;
  while (Date.now() < deadline) {
    try { if ((await fetch(url)).ok) return; } catch {}
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  throw Error('Servidor indisponível: ' + url);
}
async function text(page, value) { await page.getByText(value, {exact: true}).first().waitFor(); }
async function heading(page, name) { await page.getByRole('heading', {name, exact: true}).waitFor(); }
async function route(page, hash) {
  await page.evaluate(value => { location.hash = '/' + value; }, hash);
  await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
}
async function categoryArticle(page, name) {
  await page.waitForFunction(value => [...document.querySelectorAll('article input[name="name"]')].some(input => input.value === value), name);
  const index = await page.locator('article input[name="name"]').evaluateAll((inputs, value) => inputs.findIndex(input => input.value === value), name);
  return page.locator('article').filter({has: page.locator('input[name="name"]')}).nth(index);
}
async function login(alias) {
  const context = await browser.newContext();
  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  page.on('pageerror', error => { errors.push(error.message); console.error('Erro no navegador:', error.message); });
  await page.goto(base);
  await page.getByLabel('E-mail', {exact: true}).fill(alias + '@bilhete.test');
  await page.getByLabel('Senha', {exact: true}).fill('TesteBrowser123');
  await page.getByRole('button', {name: 'Entrar', exact: true}).click();
  await heading(page, 'Explore as campanhas');
  return page;
}

try {
  fixture('prepare');
  start('php', ['-d', 'extension=pdo_mysql', '-S', '127.0.0.1:8001', path.join(backend, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], path.join(backend, 'public'), 'backend');
  start(process.execPath, [path.join(frontend, 'node_modules/vite/bin/vite.js'), '--host', '127.0.0.1', '--port', '5174'], frontend, 'frontend');
  await ready(base);
  await ready(base + '/api/csrf');
  browser = await chromium.launch({channel: 'msedge', headless: true});

  const participant = currentPage = await login('alex');
  assert.equal(await participant.locator('.hero').count(), 0, 'Destaque ainda aparece no catálogo');
  await participant.getByLabel('Pesquisar campanha').fill('Câmera');
  await participant.locator('.cards .card').filter({hasText: 'Câmera analógica'}).waitFor();
  assert.equal(await participant.locator('.cards .card').count(), 1);
  await participant.getByLabel('Pesquisar campanha').fill('');
  await participant.getByLabel('Categoria', {exact: true}).selectOption({label: 'Música'});
  await participant.locator('.cards .card').filter({hasText: 'Som de estúdio'}).waitFor();
  await participant.getByLabel('Categoria', {exact: true}).selectOption('');
  await route(participant, 'campanha/1');
  await heading(participant, 'Bicicleta de passeio');
  assert.equal(await participant.getByRole('button', {name: /Favoritar/}).count(), 0);
  assert.equal(await participant.getByText('Avaliar experiência', {exact: true}).count(), 0);
  assert.equal(await participant.getByText('Denunciar campanha', {exact: true}).count(), 0);
  await participant.getByRole('button', {name: '001', exact: true}).click();
  await participant.getByRole('button', {name: '002', exact: true}).click();
  await participant.getByRole('button', {name: /^Reservar ·/}).click();
  await heading(participant, 'Minha área');
  await participant.getByRole('button', {name: 'Confirmar participação'}).click();
  await participant.locator('article small').filter({hasText: 'confirmada'}).waitFor();
  assert.equal(await participant.getByRole('button', {name: 'Favoritos', exact: true}).count(), 0);
  assert.equal(await participant.getByRole('button', {name: 'Notificações', exact: true}).count(), 0);
  await route(participant, 'organizador/2');
  await heading(participant, 'Oficina Aurora');
  await heading(participant, 'Histórico de campanhas');
  assert.equal(await participant.getByRole('heading', {name: /Avaliações/}).count(), 0);
  await route(participant, 'perfil');
  await heading(participant, 'Meu perfil');
  await text(participant, 'Alex Silva');
  assert.equal(await participant.locator('form').count(), 0);
  await participant.setViewportSize({width: 390, height: 844});
  await participant.screenshot({path: path.join(artifacts, 'mobile-profile.png'), fullPage: true});
  assert(await participant.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'Overflow horizontal no celular');
  console.log('OK: login, filtros, reserva/confirmação, perfil de organizador, perfil do usuário e tela pequena; recursos opcionais removidos.');

  const organizer = currentPage = await login('aurora');
  await route(organizer, 'estudio');
  await organizer.getByRole('button', {name: 'Criar campanha'}).click();
  await organizer.getByLabel('Nome da campanha').fill('Campanha Svelte');
  await organizer.getByLabel('Descrição', {exact: true}).fill('Descrição de teste da interface Svelte.');
  await organizer.getByLabel('Prêmio', {exact: true}).fill('Livro de teste');
  await organizer.getByLabel('Categoria', {exact: true}).selectOption({label: 'Fotografia'});
  await organizer.getByLabel('Quantidade de números').fill('25');
  await organizer.getByLabel('Valor por número (R$)').fill('2.50');
  const today = new Date().toLocaleDateString('en-CA', {timeZone: 'America/Sao_Paulo'});
  await organizer.getByLabel('Data inicial').fill(today);
  await organizer.getByLabel('Data final').fill('2027-12-31');
  await organizer.getByLabel('Regulamento').fill('Regulamento da campanha de teste.');
  const photoInput = organizer.getByLabel('Foto do prêmio (uma imagem)');
  assert.equal(await photoInput.getAttribute('multiple'), null, 'Campo ainda permite galeria múltipla');
  await photoInput.setInputFiles({name: 'foto.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64')});
  await organizer.getByRole('button', {name: 'Salvar rascunho'}).click();
  const campaign = organizer.locator('article').filter({has: organizer.getByRole('link', {name: 'Campanha Svelte', exact: true})});
  await campaign.waitFor();
  await campaign.getByRole('link', {name: 'Campanha Svelte', exact: true}).click();
  await heading(organizer, 'Campanha Svelte');
  await organizer.locator('.detail img.art').waitFor();
  assert.equal(await organizer.locator('.detail img.art').count(), 1, 'A campanha deve exibir uma única foto');
  await organizer.getByRole('link', {name: 'Estúdio', exact: true}).click();
  await campaign.getByRole('button', {name: 'Editar', exact: true}).click();
  assert.equal(await organizer.getByLabel('Categoria', {exact: true}).inputValue(), '3');
  assert.equal(await organizer.getByLabel('Quantidade de números').inputValue(), '25');
  await organizer.getByRole('button', {name: 'Fechar edição'}).click();
  await campaign.getByRole('button', {name: 'Enviar para aprovação'}).click();
  await campaign.getByText(/pendente/).waitFor();

  const admin = currentPage = await login('admin');
  await route(admin, 'admin');
  await heading(admin, 'Painel administrativo');
  assert.equal(await admin.getByRole('button', {name: 'Denúncias', exact: true}).count(), 0);
  assert.equal(await admin.getByRole('button', {name: 'Atividades', exact: true}).count(), 0);
  const pending = admin.locator('article').filter({has: admin.getByRole('link', {name: 'Campanha Svelte ↗', exact: true})});
  assert.equal(await pending.getByRole('checkbox').count(), 0, 'Destaque ainda pode ser marcado na aprovação');
  await pending.getByLabel('Justificativa').fill('Aprovada pela interface Svelte.');
  await pending.getByRole('button', {name: 'Registrar decisão'}).click();
  await pending.locator('small').filter({hasText: /^ativa ·/i}).waitFor();
  await organizer.getByRole('link', {name: 'Campanhas', exact: true}).click();
  await heading(organizer, 'Explore as campanhas');
  await organizer.getByRole('link', {name: 'Estúdio', exact: true}).click();
  assert.equal(await organizer.getByRole('button', {name: /^(Pausar|Retomar)$/}).count(), 0);
  organizer.once('dialog', dialog => dialog.accept());
  await campaign.getByRole('button', {name: 'Cancelar', exact: true}).click();
  await campaign.getByText(/cancelada/).waitFor();

  await admin.getByRole('button', {name: 'Categorias', exact: true}).click();
  await admin.getByLabel('Nova categoria').fill('Categoria Svelte');
  await admin.getByRole('button', {name: 'Adicionar categoria'}).click();
  const category = await categoryArticle(admin, 'Categoria Svelte');
  await category.waitFor();
  await category.getByLabel('Nome da categoria').fill('Categoria Editada');
  await category.getByRole('button', {name: 'Salvar', exact: true}).click();
  const edited = await categoryArticle(admin, 'Categoria Editada');
  await edited.getByRole('button', {name: 'Excluir', exact: true}).click();
  await admin.waitForFunction(() => ![...document.querySelectorAll('article input[name="name"]')].some(input => input.value === 'Categoria Editada'));
  await admin.getByRole('button', {name: 'Usuários', exact: true}).click();
  await admin.locator('article').first().waitFor();
  await admin.screenshot({path: path.join(artifacts, 'admin-desktop.png'), fullPage: true});
  console.log('OK: campanha com uma foto, edição, aprovação, cancelamento, categorias e usuários.');

  await participant.getByRole('button', {name: 'Sair', exact: true}).click();
  await heading(participant, 'Boas-vindas.');
  assert.equal(await participant.getByRole('link', {name: 'Esqueci minha senha'}).count(), 0);
  await route(participant, 'cadastro');
  await participant.getByLabel('Nome', {exact: true}).fill('Pessoa Svelte');
  await participant.getByLabel('E-mail').fill('svelte@example.test');
  await participant.getByLabel('CPF', {exact: true}).fill('93541134780');
  await participant.getByLabel('Telefone com DDD').fill('11912345678');
  await participant.getByLabel('Senha (mínimo de 10 caracteres)', {exact: true}).fill('TesteBrowser123');
  await participant.getByLabel('Confirme a senha').fill('TesteBrowser123');
  await participant.getByRole('button', {name: 'Cadastrar', exact: true}).click();
  await heading(participant, 'Explore as campanhas');
  await route(participant, 'estudio');
  await participant.getByLabel('Nome público').fill('Organizador Svelte');
  await participant.getByLabel('Apresentação').fill('Apresentação do organizador de teste.');
  await participant.getByLabel('Documento de identidade (PDF, JPG ou PNG; até 5 MB)').setInputFiles({name: 'identidade.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF')});
  await participant.getByRole('button', {name: 'Solicitar verificação'}).click();
  await participant.getByText(/VERIFICAÇÃO · pendente/).waitFor();
  await admin.getByRole('button', {name: 'Organizadores', exact: true}).click();
  const application = admin.locator('article').filter({has: admin.getByRole('heading', {name: 'Organizador Svelte', exact: true})});
  await application.waitFor();
  const [download] = await Promise.all([
    admin.waitForEvent('download'),
    application.getByRole('link', {name: 'Baixar documento para análise'}).click(),
  ]);
  assert.match(download.suggestedFilename(), /identidade\.pdf/);
  await application.getByLabel('Justificativa').fill('Documento aprovado.');
  await application.getByRole('button', {name: 'Registrar decisão'}).click();
  await application.getByText('aprovado', {exact: true}).waitFor();
  await participant.getByRole('link', {name: 'Campanhas', exact: true}).click();
  await heading(participant, 'Explore as campanhas');
  await participant.getByRole('link', {name: 'Estúdio', exact: true}).click();
  await participant.getByRole('button', {name: 'Criar campanha', exact: true}).waitFor();
  console.log('OK: logout, cadastro, upload/download de documento e aprovação de organizador.');
  assert.deepEqual(errors, [], 'Erros JavaScript no navegador');
  console.log('OK: nenhuma exceção JavaScript no navegador.');
  console.log('Artefatos: ' + artifacts);
} catch (error) {
  if (browser) {
    for (const [index, context] of browser.contexts().entries()) {
      const page = context.pages()[0];
      if (page) {
        fs.writeFileSync(path.join(artifacts, 'page-' + index + '.txt'), page.url() + '\n' + await page.locator('body').innerText().catch(() => ''));
      }
    }
  }
  if (currentPage) await currentPage.screenshot({path: path.join(artifacts, 'failure.png'), fullPage: true}).catch(() => {});
  throw error;
} finally {
  await browser?.close();
  for (const child of children) child.kill();
  fixture('cleanup');
}
