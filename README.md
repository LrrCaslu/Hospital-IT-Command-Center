# Hospital IT Command Center

Plataforma NOC hospitalar para **PHP 8.3+ / Laravel 12 / MySQL 8 / ServBay (Windows)**, com visual dark, controle de acesso, inventário, incidentes, alertas e base fictícia de seis unidades. Inclui estrutura de conectores para MikroTik, GLPI, UniFi, Zabbix, Wazuh e Duplicati.

> **Importante — status de entrega:** este é um **MVP avançado em código-fonte**, NÃO uma implantação de produção nem a conclusão integral de todos os 33 capítulos da especificação. As interfaces centrais, persistência, usuários demonstrativos, operações de incidentes, janelas de manutenção e integração inicial estão implementadas. **Não foi possível executar Composer, MySQL ou Laravel neste ambiente de geração** (Composer ausente, sem acesso ao Packagist e sem drivers PDO de banco); testes de execução e integração real devem ser feitos no ServBay. Não há `vendor/`, `node_modules/` nem `composer.lock`; execute a instalação de dependências na sua máquina. O layout essencial é servido de arquivos `public/assets/` **sem npm obrigatório**.

## Fluxo simples para quem ja cadastra os sites no ServBay

**O pacote NAO cria dominios ou sites no ServBay, NAO altera Apache e NAO abre o navegador.**

- `INICIAR_WINDOWS.bat`: instala dependencias se necessario, prepara `.env`, banco,
  migrations e seeders. Ao final, verifica as seis unidades e a conta admin.
- `PREPARAR_BANCO_WINDOWS.bat`: somente banco, migrations e seeders; para quem ja
  executou Composer e deseja apenas preparar os dados sem mexer no site.
- `VERIFICAR_BANCO_WINDOWS.bat`: valida MySQL, tabelas e dados de teste em modo
  leitura. Mostra resultado e gera `VERIFICACAO_BANCO.log`.
- Todas as configuracoes ja existentes em `.env` sao preservadas. **Nao usar
  `migrate:fresh`** sobre um banco que contenha dados reais.

Na sua tela do ServBay, o dominio pode ser `system.test`, e a versao do PHP pode
ser 8.4. A **raiz do site deve apontar para a subpasta `public`** do projeto:
`...\hospital-it-command-center\public`. Se o dominio for `system.test`,
ajuste `APP_URL=http://system.test` (ou HTTPS) em `.env`.

O instalador cria e verifica `bootstrap/cache` e `storage/framework/*` antes de rodar qualquer comando PHP do Laravel. A pasta `bootstrap/cache/.gitignore` vem no ZIP para que o diretorio nunca fique ausente apos extracao. O instalador registra `INSTALACAO_SERVBAY.log`. O instalador so confirma sucesso
quando Composer, geracao de chave, banco e seeders passam nas verificacoes.

## CORRECAO para `vendor/autoload.php` ausente e janela .bat que fecha

O ZIP **nao inclui `vendor/`**: o Laravel so funciona depois do Composer instalar as dependencias. Agora execute por **duplo clique `INICIAR_WINDOWS.bat`**, que nao fecha a janela antes de voce pressionar uma tecla e registra um `INSTALACAO_SERVBAY.log`. Ele instala o Composer localmente, quando nao o encontra no PATH, validando a assinatura SHA-384 do instalador oficial (acesso a internet necessario). Cria o banco MySQL se o usuario definido em `.env` tiver permissao. Se falhar, edite `.env` e repita. **Nao altere `public/index.php`.**

Se o Windows usar um PHP em `C:\php` em vez do ServBay, ative o suporte a linha de comando do ServBay e, no ServBay, selecione PHP 8.3 ou superior com `pdo_mysql`, `mbstring`, `openssl`, `xml` e `fileinfo`. O instalador tenta os comandos `php-8.4`, `php-8.3`, `php-8.5` e `php` e grava o caminho efetivo no log. A escolha de PHP do site no ServBay deve corresponder ao ambiente CLI.

O dominio deve apontar para a pasta `public`. O ZIP fornece a aplicacao fonte e scripts de instalacao, **nao garante inicializacao sem instalacao das dependencias**.

## Início rápido — Windows / ServBay / Apache

1. **Extraia o ZIP** em uma pasta simples, por exemplo `C:\ServBay\www\hospital-it-command-center`. Evite aninhar o projeto duas vezes. A pasta extraída deve conter `artisan`, `composer.json`, `app/` e `public/index.php`.
2. Abra o **ServBay**, ative **Apache**, **PHP 8.3+** e **MySQL 8+**. No Apache, habilite o módulo **mod_rewrite** e permita `.htaccess` (`AllowOverride All`, quando aplicável).
3. O instalador tenta criar automaticamente o banco `hospital_it_noc` (`utf8mb4_unicode_ci`) usando os dados do `.env`. Se a conta MySQL nao puder criar bancos, crie-o no gerenciador do ServBay ou execute `database/create_database.sql`. Verifique host, porta e senha no ServBay; **nao presuma** que `3306` ou `ServBay.dev` correspondam a sua instalacao.
4. Execute `INICIAR_WINDOWS.bat` por duplo clique, ou siga os comandos manuais abaixo.
5. Em **ServBay → Sites**, adicione `hospital-noc.test`, selecione **Apache** e configure **diretório raiz do site** exatamente para `...\hospital-it-command-center\public` (NÃO o diretório pai). Abra `http://hospital-noc.test` (ou `https://` se você habilitar HTTPS no domínio).
6. No Windows, execute `INICIAR_COLETOR_WINDOWS.bat` para atualizações simuladas; para fontes reais execute também `INICIAR_FILAS_WINDOWS.bat`. Mantenha as duas janelas abertas.

### Instalação manual

```bat
cd /d "C:\ServBay\www\hospital-it-command-center"
copy .env.example .env
rem Edite .env antes de rodar as migrations
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
```

A interface base já está compilada manualmente em `public/assets/noc.css` e `public/assets/noc.js` e é funcional sem Node.js. Para adicionar Chart.js/Alpine/Tailwind via Vite:

```bat
npm install
npm run build
```

**Se aparecer `vendor/autoload.php not found`, o Composer não concluiu.** Confira `php -v`, `composer --version`, extensões `pdo_mysql`, `openssl`, `mbstring`, `curl`, `fileinfo`, `xml`, `ctype` e `tokenizer`. Não remova nem modifique `public/index.php` para contornar esse erro.

### Domínio local

- Domínio de exemplo: `hospital-noc.test`
- Document root Apache: `...\hospital-it-command-center\public`
- O arquivo `public/.htaccess` utiliza `mod_rewrite` para encaminhar URLs ao Laravel.
- A chave de sessão e criptografia é gerada com `php artisan key:generate`.
- Se aparecer **500**: veja `storage/logs/laravel.log`, verifique `APP_KEY`, conexão MySQL, extensões PHP e `php artisan migrate:status`.
- Se aparecer **404**: verifique se o Document Root é `public`, se `mod_rewrite` está ativo e a configuração do VirtualHost.
- Se o **login falhar**: execute `php artisan db:seed --force`, confira usuários no banco e limpe cookies do domínio local. Nunca use `APP_DEBUG=true` em produção.

## Acessos demonstrativos

| Perfil | E-mail | Senha local |
|---|---|---|
| Superadministrador | `admin@hospital.local` | `Admin@123456` |
| Administrador TI | `ti@hospital.local` | `Ti@123456` |
| Analista NOC | `noc@hospital.local` | `Noc@123456` |
| Analista SOC | `soc@hospital.local` | `Soc@123456` |
| Gestor | `gestor@hospital.local` | `Gestor@123456` |
| Visualizador | `viewer@hospital.local` | `Viewer@123456` |

Essas contas são **inseguras para qualquer rede externa** e não devem existir em produção. Dados de pacientes **não** são necessários nem coletados.

## Rotinas operacionais

```bat
php artisan noc:collect --once
php artisan noc:collect --sleep=10
php artisan queue:work database --sleep=2 --tries=2 --timeout=55
php artisan schedule:run
php artisan noc:demo-reset --force
php artisan test
```

- A interface principal atualiza os indicadores por requisições assíncronas a cada **10 segundos**.
- No modo demonstrativo, o comando de coleta altera **CPU e RAM simuladas** e persiste novas amostras em banco. Sem o processo de coleta, a visualização continuará consultando os dados persistidos, mas eles não evoluirão.
- Cada integração configurada tem intervalo de coleta independente; `noc:collect` agenda apenas fontes que estão vencidas. O processamento das APIs é delegado aos Jobs da fila de banco.
- O modo de operação global é escolhido em `.env` por `NOC_MODE=demo` ou `NOC_MODE=integrated`. O modo integrado **nunca preenche lacunas com dados fictícios**.
- A reinicialização de dados demonstrativos é bloqueada se o banco contém ativos não demonstrativos ou conectores integrados.

## Proteção de dados e autenticação

- Middleware de autorização por permissão e checagem por unidade nos endpoints.
- Hash de senha pelo mecanismo padrão Laravel; sessões regeneradas no login.
- Limitação de tentativas de login, CSRF, validação dos formulários e logs de auditoria sem tokens.
- Credenciais dos conectores em cast Eloquent `encrypted:array`; criptografia via `APP_KEY`. Não perca a chave sem estratégia de recuperação.
- Verificação TLS sempre ativa. É possível configurar um certificado de autoridade interna, **sem** desligar a verificação.
- HTTP de monitoramento somente leitura. Nenhuma ação remota de reinicialização, firewall ou comando executável foi implementada.

Consulte `docs/ARQUITETURA.md`, `docs/INTEGRACOES.md`, `docs/COBERTURA_E_LIMITACOES.md` e `docs/MANUAL_RAPIDO.md`.

## Validação

O pacote fornece testes de Login, Permissão, Escopo, Dashboard, Incidentes, Alertas, Criptografia, manutenção, isolamento de demonstração e erro de API com `Http::fake()`. **Os testes foram escritos e submetidos à análise sintática de PHP, porém NÃO puderam ser executados nesta sandbox.** Para validá-los no Windows:

```bat
php artisan test
php artisan route:list
php artisan migrate:status
```

Para SQLite em memória nos testes, habilite a extensão `pdo_sqlite` no PHP usado pelo Composer/CLI. A aplicação operacional no ServBay utiliza MySQL.

## Direitos e integração

A tela GLPI trabalha com cópias locais das informações de tickets para análises; o GLPI continua a fonte original. O Zabbix considera **disponibilidade do agente/interface**, não apenas o estado “monitorado” do host. Os conectores UniFi e Duplicati exigem caminhos e mapeamento da versão da API instalada. Leia `docs/INTEGRACOES.md` antes de usá-los.

## Reparo do erro `PackageManifest.php line 179` / `bootstrap/cache`

O pacote anterior deixou de incluir o diretorio `bootstrap/cache`. O Composer podia
instalar `vendor/` mas falhava ao executar `artisan package:discover`, e as
etapas seguintes eram mostradas como `OK` mesmo com excecao. Nesta versao,
as pastas obrigatorias sao criadas e testadas para escrita **antes** do Composer;
os comandos reportam erros fatais de forma explicita, e `APP_KEY` e dados
persistidos sao verificados.

Se o Composer ja instalou `vendor` na instalacao anterior, utilize o **ZIP de
reparo** (arquivos para sobrepor no projeto existente) e execute
`INICIAR_WINDOWS.bat` novamente, sem apagar `.env`, `vendor` nem o banco.
