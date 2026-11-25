# Arquitetura do Bilhete

O Bilhete utiliza Svelte 5, Laravel e MySQL. A organização do back-end segue o laravel-base.zip fornecido pelo usuário: Controller -> Service -> Repository -> Model, com injeção de dependências pelo construtor, Requests e Policies. Os requisitos do PDF continuam preservados.

## Escopo atual

O sistema mantém cadastro, login/logout, perfil de consulta, papéis e permissões, catálogo, categorias, perfis de organizador, verificação de identidade, criação e aprovação de campanhas, uma foto por campanha, reservas, confirmação, expiração, resultado manual, suspensão e reativação de contas.

Por solicitação do usuário em 15/11/2026, foram retirados favoritos, avaliações, denúncias, destaque, galeria múltipla, notificações internas e atividades/auditoria. As classes, endpoints, controles e tabelas desses módulos foram removidos. Os logs operacionais do Laravel continuam disponíveis; não são o antigo histórico de atividades nem a central de notificações internas.

## Front-end

- frontend/src/main.js monta App.svelte, responsável por sessão e navegação por hash.
- Shell.svelte apresenta menu, rodapé e a página correspondente à rota. A chave da rota reinicia o estado da página ao navegar.
- src/pages possui Auth (login e cadastro), Market, Detail, Area, Organizer, Profile, Studio e Admin. São nove telas considerando os modos de Auth.
- src/components concentra Field, Form, Action, Heading, Wait, Art, Cards, CampaignForm, CampaignActions e DecisionForm.
- Estado usa $state; valores derivados usam $derived; consultas usam $effect em src/lib/data.svelte.js. Respostas obsoletas são ignoradas quando a consulta muda ou a página é desmontada.
- api.js envia cookies, busca o token CSRF antes de mutações, serializa JSON ou FormData e traduz erros da API.
- Minha área mostra reservas, participações e resultados. A administração possui Campanhas, Organizadores, Usuários e Categorias.
- A foto do prêmio é opcional e única. O campo multipart permanece photos[] e o JSON photos continua sendo um array de zero ou um item. Não existe seleção múltipla no formulário; Requests e Service rejeitam mais de uma imagem.
- Autorizações são executadas no Laravel; o menu por papel apenas organiza a interface.

Na pasta frontend: npm install, npm run check, npm run build e npm run dev. O endereço de desenvolvimento é http://localhost:5173, com proxy /api para o Laravel na porta 8000. Não há React nem JSX na aplicação atual.

## Módulos do back-end

| Módulo | Controller | Service | Repositories e Models |
| --- | --- | --- | --- |
| Cadastro, acesso e consulta de perfil | AccountController | AccountService | UserRepository / User |
| Catálogo e campanhas | CampaignController | CampaignService | CampaignRepository / Campaign; TicketRepository / Ticket |
| Reservas e confirmação | ReservationController | ReservationService | ReservationRepository / Reservation; Campaign e Ticket |
| Organizador e identidade | OrganizerController | OrganizerService | OrganizerRepository / Organizer; User e Campaign |
| Minha área e estúdio | AreaController | AreaService | Coordena os Services de reserva, campanha e organizador |
| Aprovação e suspensão | AdminController | AdminService | User, Organizer, Campaign e Ticket |
| Categorias | CategoryController | CategoryService | CategoryRepository / Category |
| Papéis | Consultados pelo back-end | RoleService | RoleRepository / Role |
| Permissões | Middleware e Policies | PermissionService | PermissionRepository / Permission; Resource |

Controllers recebem dados validados e retornam respostas HTTP. Services aplicam regras e delimitam transações; não recebem Request nem devolvem Response. Repositories fazem as consultas e gravações com Eloquent ou Query Builder iniciado pelos Models. Models definem atributos, casts e relacionamentos.

BaseService preserva all, allPaginate, find, store, update e remove. BaseRepository preserva list, listPaginate, find, store, update e remove, além de query. getRepository e getModel são os métodos protegidos implementados pelas subclasses. Operações específicas de campanhas e reservas usam métodos próprios, sem expor CRUD que contorne regras de domínio. remove retorna bool, acompanhando o delete do Eloquent.

## Papéis e permissões

users.role_id referencia roles.id. User.role() é belongsTo(Role), e Role possui users(), resource() e permissions(). O cadastro recebe participante; aprovação de identidade atribui organizador. role_id não é editável por cadastro ou perfil. A API apresenta role como nome e role_id como identificador; em PHP user->role é o Model relacionado.

Resource descreve uma ação; Permission associa role_id a resource_id por chave primária composta. Permission possui role() e resource(), e Resource.role() e Role.resource() são belongsToMany.

PermissionService fornece getPermissions(role_id), loadPermissions(role_id) e isAuthorized(resource, user), como no exemplo do ZIP. Os dois primeiros devolvem um mapa atualizado de nomes para true; não guardam uma decisão de autorização na sessão. isAuthorized consulta o papel atual do usuário e as associações no banco, verificando suspensão. Revogações e mudanças de papel não dependem de novo login para serem reconhecidas pelo servidor.

As 22 rotas protegidas possuem nome e middlewares access, auth.session e permission. O recurso adicional campaign.viewPrivate controla análise de rascunhos de terceiros. Recursos ausentes, não nomeados ou sem associação são negados. Autenticação, cadastro, CSRF e logout permanecem disponíveis para acesso e encerramento de sessão.

config/permissions.php define apenas os grants iniciais: 23 recursos e 41 associações, sendo 11 para participante, 13 para organizador e 17 para administrador. Runtime consulta o banco. PermissionSeeder não duplica registros, mas reaplica os grants padrão se executado, inclusive os que foram revogados. Não há tela para editar permissões.

Policies complementam o acesso com regras de propriedade e visibilidade. Permissão de editar campanha não autoriza editar a de outra pessoa; confirmar reserva exige que ela pertença ao usuário. Uma conta administrativa continua protegida contra suspensão por regra de domínio.

## Regras principais

- Autenticação usa sessão/cookie, hash de senha e CSRF. Cadastro e login são mantidos; edição de perfil e recuperação/redefinição foram removidas. User mantém o trait Notifiable por orientação do projeto, sem fluxo de recuperação ativo.
- Campanhas passam por rascunho, pendente, ativa, rejeitada, cancelada ou encerrada, conforme transições permitidas. Os números são gerados na aprovação.
- Até 50 números por reserva; valores são armazenados em centavos. A confirmação é acadêmica e não processa pagamento.
- Reservas duram 15 minutos. A criação, confirmação e expiração preservam transações e a ordem de bloqueio: campanha antes de reservas/números. A disputa concorrente aceita somente uma reserva para o mesmo número.
- A expiração libera números sem enviar notificações. O comando reservas:expirar e o schedule a cada minuto permanecem; a API também libera reservas vencidas nas consultas relevantes.
- Cancelamento exige ausência de reservas vigentes e participações confirmadas. Resultado é informado manualmente pelo organizador após o período e deve corresponder a um número confirmado.
- Documentos são privados e passam por autorização antes do download. A identidade é aprovada manualmente. Arquivos recém-enviados são removidos se a operação falhar.
- Exclusão de categoria em uso continua bloqueada. Categorias e papéis não possuem CRUD público que ignore permissões.

## Banco e atualização

O schema final possui nove tabelas de domínio/infraestrutura, além de migrations: users, roles, resources, permissions, organizers, categories, campaigns, reservations e tickets.

As migrations antigas foram mantidas como histórico para atualizar instalações anteriores. As oito migrations 2026_11_15_100000 a 100007 removem as cinco tabelas opcionais, o campo featured de campaigns, as fotos além da primeira e os quatro recursos/permissões removidos. Cada arquivo modifica sua tabela correspondente. Permissões principais não são recriadas nem alteradas durante essa limpeza.

Antes de atualizar, faça backup do banco. Depois execute php artisan migrate na pasta backend. Em instalação nova, execute php artisan migrate --seed. O rollback dessas oito migrations restaura estruturas vazias, featured=false e os grants antigos padrão; não recupera os registros opcionais, destaques ou fotos excedentes. Para recuperar dados removidos é necessário o backup e uma versão de código compatível.

As quatro migrations de 20/11/2026 removem password_reset_tokens, convertem pausada em ativa e removem apenas profile.update e seus grants. Não reaplicam grants revogados. O rollback recria tokens vazios e o recurso/grants padrão, sem restaurar tokens anteriores ou pausar campanhas novamente. Perfil é somente para consulta; pausa/retomada são rejeitadas pela API.

## Verificação

Na pasta backend:

- php tests/permission_architecture.php: suíte funcional e autorização em SQLite isolado.
- php tests/role_migration.php: conversão e rollback de Role.
- php tests/simplification_migration.php: atualização com registros legados, preservação de dados principais, foto única, rollback estrutural e reaplicação em SQLite.
- php -d extension=pdo_mysql tests/mysql_architecture.php: suíte funcional, permissões e concorrência em schema MySQL descartável.
- php -d extension=pdo_mysql tests/mysql_architecture.php simplification: o cenário de atualização em MySQL isolado.

Os testes de API usam modo testing, que ignora CSRF; não validam isoladamente essa proteção. O teste de navegador utiliza sessões e tokens CSRF reais.

Na pasta frontend, npm run test:e2e usa Edge, PHP e MySQL do Compose. Cria banco bilhete_browser_test_*, armazenamento e servidores nas portas 8001 e 5174; remove o banco e encerra os servidores ao terminar. Cobre o fluxo principal, foto única, ausência dos controles retirados, documentos e perfis distintos. Artefatos ficam em frontend/test-results, fora do versionamento. Não altera o banco bilhete nem inicia o site principal.

O runner MySQL usa por padrão a senha root do .env do Compose. Outros ambientes podem fornecer BILHETE_TEST_MYSQL_USERNAME, BILHETE_TEST_MYSQL_PASSWORD, BILHETE_TEST_MYSQL_HOST e BILHETE_TEST_MYSQL_PORT. Uma auditoria completa de acessibilidade permanece fora dessa validação.
