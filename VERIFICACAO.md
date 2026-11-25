# Verificação da entrega

## Perfil de consulta e remoções adicionais — 20/11/2026

- Retirados edição de perfil, recuperação/redefinição de senha e pausa/retomada de campanhas. O perfil conserva os dados do cadastro e fica somente para consulta. Cadastro e login permanecem disponíveis.
- A API retorna 404 para os endpoints de edição e recuperação removidos e 422 para ações de pausa/retomada. As permissões continuam verificadas no banco, com preservação de propriedade e proteção de atributos.
- Suítes de permissões, migração de papéis e atualização com dados legados passaram em SQLite. As suítes funcional, de permissões e de atualização passaram em MySQL descartável, incluindo concorrência real: uma reserva aceita e outra rejeitada para o mesmo número.
- `npm run check` passou com zero erros e avisos; `npm run build` concluiu a compilação.
- `npm run test:e2e` passou no Edge com banco e servidores temporários, incluindo perfil sem formulário, ausência de recuperação e pausa/retomada, login/cadastro, reservas/confirmação, campanhas, categorias e documentos. Nenhuma exceção JavaScript foi registrada. Artefatos: `frontend/test-results/bilhete_browser_test_4360fc9a1f202130`.
- As quatro migrations de 20/11 foram aplicadas no MySQL local após backup em `.runtime/bilhete-before-account-removal-20261120-145845.sql`. Dados principais e grants preservados por comparação antes/depois; não havia campanhas pausadas nessa instalação. Não foram reaplicados seeders.
- Schema final: nove tabelas além de migrations. Matriz padrão: 23 recursos e 41 permissões. Svelte mantém nove telas; os requisitos mínimos do PDF permanecem atendidos. O rollback recria tokens vazios e não recupera o estado pausado anterior; recuperação desses dados exige backup e código compatível.
- As seções seguintes são históricas e podem descrever funcionalidades removidas posteriormente.

## Simplificação solicitada — 15/11/2026

- Retirados favoritos, avaliações, denúncias, destaque, galeria múltipla, notificações internas e atividades. Foram removidos controles da interface, rotas, permissões e classes dos módulos; cada campanha aceita até uma foto.
- `php tests/permission_architecture.php`, `php tests/role_migration.php` e `php -d extension=pdo_mysql tests/mysql_architecture.php` passaram, preservando cadastro, acesso, perfil, papéis, permissões, campanhas, documentos, reservas, confirmação, expiração, resultado e concorrência real no MySQL.
- Testes verificam endpoints retirados retornando 404, ausência das tabelas/campos opcionais, foto única e rejeição de duas fotos com HTTP 422, inclusive na edição, preservando a foto atual.
- `php tests/simplification_migration.php` e `php -d extension=pdo_mysql tests/mysql_architecture.php simplification` passaram com dados legados. Validaram a preservação dos dados principais, da primeira foto e dos grants principais, além de rollback estrutural e reaplicação.
- `npm run check` passou sem erros/avisos e `npm run build` gerou a compilação atualizada. `npm run test:e2e` passou no Edge com schema e storage temporários, verificando os fluxos principais, foto única e ausência dos recursos retirados. Não houve exceções JavaScript. Artefatos: frontend/test-results/bilhete_browser_test_394a806b46dd3448.
- O MySQL local recebeu as oito migrations de remoção após backup SQL em `.runtime/bilhete-before-simplification-20261115-042350.sql`, na pasta de trabalho. Usuários, papéis, organizadores, categorias, campanhas (exceto destaque/fotos excedentes), reservas, números e permissões principais foram comparados e preservados.
- A matriz atual possui 24 recursos e 44 permissões. O schema final possui 10 tabelas, além de migrations. Registros opcionais foram removidos; rollback restaura estrutura, não esses dados. O backup permite recuperação com código compatível.
- As seções abaixo são histórico de versões anteriores e podem mencionar funcionalidades que já foram retiradas. O site principal permanece desligado.

## Resource, Permission e PermissionService — 15/11/2026

- Criados Models, Repositories, PermissionService, duas migrações e seeders de recursos e permissões. As 27 rotas protegidas possuem nome e middleware de permissão; Policies usam o serviço e mantêm as regras de domínio.
- `php tests/permission_architecture.php`: passou em SQLite em memória, incluindo a suíte funcional anterior, concessão/revogação imediata, revogação administrativa, troca de papel, suspensão, propriedade, recursos desconhecidos, seeders, integridade e rollback.
- `php tests/role_migration.php`: passou; teste restrito às migrações de Role para continuar independente das migrações posteriores.
- `php -d extension=pdo_mysql tests/mysql_architecture.php`: passou em schema MySQL descartável, incluindo a nova suíte de permissões e dois processos disputando o mesmo número.
- Sintaxe PHP verificada em 109 arquivos sem erros. Migrações e grants iniciais aplicados no MySQL local após backup; dados das tabelas anteriores comparados e preservados. Foram criados 28 recursos e 53 permissões.
- `npm run test:e2e`: passou no Edge com banco, armazenamento e servidores temporários, cobrindo os fluxos da interface Svelte e as novas verificações de permissão. Nenhuma exceção JavaScript registrada. Artefatos em frontend/test-results/bilhete_browser_test_59affca3600654fc. O schema e os servidores de teste foram encerrados ao final.

## Front-end migrado para Svelte — 13/11/2026

- React e JSX substituídos por Svelte 5.57.3, com páginas e componentes separados e estado em runes. APIs, sessões, autorização e banco da aplicação preservados.
- `npm run check`: zero erros e zero avisos. `npm run build`: compilação de produção concluída.
- `npm run test:e2e`: passou no Edge sem janela, com MySQL e armazenamento temporários. Cobriu login/logout, filtros, favoritos, reserva/confirmacão, notificações, avaliação, denúncia, perfil, criação de campanha com foto, edição, aprovação, pausa/retomada/cancelamento, categorias, resposta a denúncia, recuperação, redefinição com token inválido, cadastro e upload/download/aprovação de documento.
- A interface atualiza o papel ao abrir o Estúdio e atualiza os dados ao trocar a aba administrativa. Nenhuma exceção JavaScript foi registrada no navegador durante a suíte.
- Capturas de perfil em tela de 390px e administração em desktop foram geradas. A verificação de largura não encontrou overflow horizontal no perfil móvel. Isso não substitui uma auditoria completa de acessibilidade ou de todos os tamanhos de tela.
- Schema de teste removido ao terminar. Logs e capturas ficam em frontend/test-results e não entram no versionamento. SMTP externo não foi validado.

## Papéis com Model Role — 13/11/2026

- Criados Model, Repository, Service e Seeder de Role. User se relaciona por role_id; middleware e Policies verificam o relacionamento.
- Migração dos usuários existentes aplicada no MySQL local após backup; dados, senhas, suspensão e nomes dos papéis comparados antes/depois e preservados.
- Teste isolado de conversão, rollback, reaplicação e seeder idempotente passou.
- Suíte funcional em MySQL com concorrência passou usando o novo relacionamento. Cadastro com role e role_id administrativos continua recebendo participante.

## Migração completa da arquitetura — 13/11/2026

- Todos os módulos da API e o comando de expiração migrados para Controllers, Services, Repositories e Models. Validações em Requests e regra CPF; permissões por middlewares e Policies.
- Suíte `tests/application_architecture.php` passou em SQLite em memória e em schema MySQL descartável, cobrindo os fluxos descritos em `ARQUITETURA.md`, inclusive uploads, documentos privados e invalidação de sessão após recuperação de senha.
- Concorrência real no MySQL, com dois processos disputando o mesmo número: uma reserva aceita e uma rejeitada com conflito; apenas uma reserva persistida.
- Verificação HTTP no servidor local com MySQL: login, sessão, categorias, catálogo, detalhes, organizador, minha área, estúdio e logout passaram. Mutação sem token CSRF rejeitada com HTTP 419.
- Comando `reservas:expirar` executado com sucesso. As 12 migrações individuais seguem aplicadas no banco local, sem mudanças de esquema nesta refatoração.
- Revisão visual completa e entrega SMTP externa continuam pendentes. Os blocos abaixo mantêm o histórico das verificações anteriores.

## Verificação local da primeira etapa da arquitetura — 13/11/2026

- Sintaxe PHP de app, routes e tests conferida, sem erros.
- `php tests/category_architecture.php`: passou em SQLite em memória, incluindo rotas HTTP, papéis, conta suspensa, validação, CRUD, contrato JSON, relacionamentos, atividades, filtro, paginação e rollback transacional.
- Consulta, criação, edição e exclusão de categoria por HTTP no MySQL local passaram, com sessão de administrador e token CSRF renovado entre mutações. A categoria de teste foi removida.
- A reorganização não modifica o esquema do banco. Os demais testes e limites abaixo descrevem a entrega anterior; não indicam uma nova execução completa neste computador.

## Testes executados com sucesso

- Compilação de produção da interface React pelo Vite.
- Verificação de sintaxe dos arquivos PHP da aplicação.
- Migração e carga inicial em banco SQLite isolado.
- Login, sessão, consulta do usuário e logout.
- Acesso anônimo negado e mutação sem CSRF rejeitada.
- Participante bloqueado no painel administrativo e no download de documentos pessoais.
- Cadastro com CPF inválido rejeitado e tentativa de escolher papel administrativo ignorada.
- Edição do perfil exige a senha atual.
- Consulta do catálogo, números e categorias.
- Reserva de vários números, rejeição de duplicidade, confirmação e rejeição de confirmação repetida.
- Favoritos, avaliações de participante confirmado, denúncias e resposta administrativa.
- Criação, envio, aprovação, pausa, retomada e cancelamento de campanha.
- Geração da quantidade correta de números após aprovação.
- Bloqueio de reserva pelo proprietário e em campanha pausada.
- Recuperação de senha em log, rejeição de token inválido e bloqueio de reutilização.
- Agendamento de liberação de reservas identificado pelo comando schedule:list.

## Limitações registradas na entrega original

- A conexão local com MySQL foi bloqueada pelo ambiente com erro 10013. Os testes por kernel usaram SQLite. Bloqueios concorrentes e a migração em MySQL ainda precisam de execução no ambiente final.
- O navegador integrado não conseguiu acessar o servidor local. A interface foi compilada, mas a inspeção visual e a navegação ponta a ponta ficaram pendentes.
- O envio SMTP externo depende da configuração do usuário. A recuperação foi testada com transporte de e-mail em log.
- A revisão manual de um documento real e upload de fotos pela interface precisam de conferência no ambiente final.

## Conferência antes da apresentação

1. Faça a instalação descrita em LEIA-ME.md com MySQL e confirme as três contas geradas.
2. Execute o roteiro de uso completo, incluindo documento e fotos.
3. Use duas sessões distintas para tentar reservar o mesmo número. Uma solicitação deve ser aceita e a outra deve receber conflito.
4. Aguarde o vencimento de uma reserva e confira liberação dos números e notificação.
5. Teste a recuperação de senha e confirme que uma sessão antiga perde acesso.
6. Confira a interface em tela pequena e a navegação por teclado.

Essas pendências representam os limites de validação desta entrega. Os resultados acima não são uma certificação de ausência de erros.
