# Bilhete — marketplace de campanhas

Projeto acadêmico com Svelte 5, Laravel 13 e MySQL 8.4. A interface se comunica com a API por JSON e cookies de sessão. O conteúdo da plataforma exige login, inclusive o perfil de organizador, visível a todos os usuários autenticados.

## Preparar o ambiente

Instale PHP 8.3 ou superior, Composer 2, Node.js 22.12 ou superior e MySQL 8.4. Habilite as extensões PHP pdo_mysql, mbstring, openssl, fileinfo, curl, dom, xml e zip. O ZIP inclui as dependências PHP e uma compilação da interface. Para recompilar ou desenvolver a interface, instale suas dependências com npm.

1. Abra esta pasta no VS Code.
2. No terminal, entre em `backend` e execute `php prepare.php`.
3. Crie um banco MySQL chamado `bilhete` e um usuário com acesso a esse banco. Configure DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD em `backend/.env`.
4. Execute os comandos a seguir na pasta `backend`.

```sh
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

A primeira execução do seeder exibe senhas aleatórias para `admin@bilhete.test`, `aurora@bilhete.test` e `alex@bilhete.test`. Guarde essas senhas. Os perfis são administrador, organizador e participante, respectivamente. O seeder preserva bancos que já possuem usuários. Os dados de demonstração são fictícios.

5. Abra outro terminal na pasta `frontend`.

```sh
npm install
npm run dev
```

6. Acesse `http://localhost:5173`. Use sempre esse endereço para manter cookies de sessão consistentes.

O front-end atual usa Svelte 5. Execute `npm run check` para verificar os componentes e `npm run build` para gerar a versão de produção em frontend/dist. O comando `npm run test:e2e` valida a interface no Edge instalado, com MySQL e armazenamento temporários; veja `ARQUITETURA.md` para os pré-requisitos e detalhes.
7. Em um terceiro terminal, na pasta `backend`, execute `php artisan schedule:work`. Esse processo libera reservas expiradas a cada minuto. A API também libera reservas vencidas ao consultar ou reservar números.

Se preferir MySQL em Docker, copie `.env.example` da raiz para `.env`, escolha duas senhas diferentes e execute `docker compose up -d`. Configure em `backend/.env` a mesma senha DB_PASSWORD, usuário bilhete, banco bilhete e porta 3306. O Compose contém somente o serviço de banco de dados.

## Migrações por tabela

Cada tabela possui seu próprio arquivo em `backend/database/migrations`, em ordem de dependência das chaves estrangeiras. O rollback executa a ordem inversa.

Em uma instalação nova, use `php artisan migrate --seed` normalmente. Se o banco já executou a antiga migração `2026_01_01_000000_create_marketplace`, faça backup do banco e execute `php database/adopt_split_migrations.php` na pasta `backend` antes do próximo `migrate`. Esse comando substitui somente o registro antigo na tabela de histórico pelas 12 migrações individuais, preservando o lote, as tabelas e os dados existentes. Ele não é necessário para bancos novos.

Os papéis são definidos pelo Model Role, pela tabela roles e pelo relacionamento users.role_id. Para atualizar uma instalação que ainda usa users.role textual, faça backup e execute `php artisan migrate`: as duas migrações de 13/11/2026 preservam os papéis existentes e substituem o texto pela chave estrangeira. O cadastro sempre recebe participante, e a aprovação de identidade altera para organizador. A API mantém o nome em role para o Svelte.

As permissões seguem o padrão Resource, Permission e PermissionService do ZIP. Para atualizar um banco existente, faça backup e execute `php artisan migrate --seed`: serão criadas resources e permissions e carregadas as associações iniciais. O runtime consulta as associações no banco em cada requisição protegida, sem cache de sessão. Reexecutar PermissionSeeder reaplica os grants padrão, inclusive os que foram revogados. Veja `ARQUITETURA.md` para o mapa e os testes.

A versão simplificada remove favoritos, avaliações, denúncias, destaque, notificações internas, atividades e fotos além da primeira. Para atualizar uma instalação com esses recursos, faça backup e execute `php artisan migrate`; não é necessário reexecutar os seeders para a remoção. As migrations preservam usuários, campanhas, reservas, números e permissões principais. O rollback restaura estruturas vazias, não os dados removidos. A campanha mantém até uma foto. O schema final tem nove tabelas além da tabela migrations.

## Simplificação de conta e campanhas — 20/11/2026

O perfil é somente para consulta e conserva os dados do cadastro. Edição de perfil, recuperação/redefinição de senha e pausa/retomada de campanhas foram removidas. Cadastro e login continuam disponíveis. As quatro migrations de 20/11 removem a tabela de tokens e o recurso profile.update com seus grants, e convertem campanhas antigas pausadas em ativas, preservando as verificações de período e reserva. Faça backup antes de executar php artisan migrate, sem seed em instalações existentes. O rollback recria a tabela de tokens vazia; não recupera tokens nem o estado pausado anterior.

## Roteiro de uso

1. Cadastre um participante com CPF válido e telefone com DDD. A validação do CPF verifica o formato e os dígitos verificadores.
2. Entre em Estúdio, preencha nome público e apresentação e envie um documento PDF, JPG ou PNG de até 5 MB.
3. Como administrador, abra Administração, Organizadores, baixe o documento e registre a decisão com justificativa.
4. Após aprovação, volte ao Estúdio para atualizar o papel exibido pela interface.
5. Crie uma campanha, informe prêmio, regulamento, período, categoria, quantidade e valor dos números. É possível enviar uma foto do prêmio de até 4 MB.
6. Envie o rascunho para aprovação. Como administrador, examine os dados e aprove ou rejeite com justificativa. Os números são gerados na aprovação.
7. Como participante, pesquise campanhas, escolha números e faça uma reserva. Confirme a participação em até 15 minutos pela Minha área.
8. Consulte o histórico das reservas, participações e resultados na Minha área.
9. O organizador pode cancelar campanhas. O cancelamento exige ausência de reservas vigentes e participações confirmadas. Rascunhos e campanhas rejeitadas podem ser editados.
10. Após o último dia da campanha, o organizador publica manualmente um número contemplado que tenha participação confirmada.

A confirmação de participação é uma operação acadêmica. Não há integração de cobrança ou processamento de pagamentos. A verificação de identidade é uma análise manual dos documentos pelo administrador. O resultado é informado pelo organizador, sem sorteio automático.

## Estrutura e requisitos do trabalho

O padrão de camadas segue o `laravel-base.zip` fornecido pelo usuário. Todos os módulos da API usam Models, Controllers, Requests, Policies, Services e Repositories, com injeção pelo construtor. Consulte `ARQUITETURA.md` para o mapa dos módulos, funções equivalentes ao exemplo e comandos de teste em SQLite e MySQL.

| Exigência do PDF | Implementação |
| --- | --- |
| Front-end desacoplado | Svelte em frontend e API Laravel em backend |
| Pelo menos cinco páginas | Login, cadastro, marketplace, campanha, organizador, área, perfil de consulta, estúdio e administração |
| Componentes e estado | Componentes Svelte, $state, $derived e $effect e consultas por API |
| Banco com pelo menos cinco tabelas | 9 tabelas de domínio e infraestrutura em migrações Laravel, além da tabela de migrações |
| Autenticação e papéis | Participante, organizador e administrador, com sessões Laravel |
| Autorização por requisição | Middleware de acesso, restrições por papel e verificação de propriedade |
| Organização em camadas | Controllers, Services, Repository, Model e migrações |

As senhas usam hash do Laravel. As consultas usam Query Builder e parâmetros. A interface escapa texto pelo Svelte. As mutações exigem token CSRF. Reservas utilizam transação, bloqueio da campanha e unicidade por campanha/número. Documentos ficam em armazenamento privado e seu download exige permissão administrativa. Não há mais histórico de atividades ou central de notificações internas; logs operacionais do Laravel continuam disponíveis.

Os arquivos de execução, documentos enviados, senhas locais, logs e banco de testes foram excluídos do ZIP. Execute prepare.php para criar as pastas necessárias.

## Verificação e limites da entrega

Veja `VERIFICACAO.md` para os testes executados e pendências. Após a reorganização em camadas, a suíte funcional passou em SQLite em memória e MySQL isolado. Duas reservas simultâneas para o mesmo número foram testadas no MySQL: uma foi aceita e outra recebeu conflito. O banco local da aplicação foi preservado.

A migração para Svelte passou em verificação de componentes, compilação e testes de navegação e formulários no Edge, incluindo fotos, documentos e perfis distintos. Foram geradas capturas em desktop e celular. Uma auditoria completa de acessibilidade ainda precisa de validação. A entrega não inclui publicação em servidor externo.

## Referências

- Enunciado fornecido pelo usuário, `dw_trabalho_final.pdf`.
- [Página da disciplina](https://www.gileduardo.com.br/ifpr/dw/).

