# Cobertura da especificação e limitações verificáveis

## Entregue no código-fonte

| Área | Estado | Detalhes |
|---|---|---|
| Laravel, Blade, autenticação, MySQL | Implementado; não executado aqui | Laravel 12, migrations, hash, 6 contas |
| Design dark responsivo | Implementado; não renderizado aqui | Sidebar, header, dashboards, tabelas, login, TV |
| Livewire, Alpine, Tailwind, Chart.js, Vite | Configurado | Componente Livewire ativo; JS/CSS essenciais pré-incluídos; build npm opcional |
| Six unidades | Seeder implementado | Dados 100% fictícios |
| Dashboard e filtros | Implementado | Indicadores calculados do MySQL e JSON a cada 10 s |
| Inventário de ativos | Implementado | Cadastro, listagem, filtro, identificadores externos e transferência por endpoint |
| Alertas | Implementado parcial | Reconhecimento, silêncio, resolução e dedupe por chave |
| Incidentes | Implementado parcial | Atribuição, comentário, status, causa-raiz e eventos |
| Modo demonstração | Implementado parcial | 30 dias de amostras, variação de CPU/RAM; cenários adicionais pendentes |
| Contas e escopo por unidade | Implementado | Roles/permissions e checagens em controlador |
| Integrações | Adaptadores iniciais | Verificar compatibilidade e mapeamento por versão |
| Filas e coletor | Implementado; não executado aqui | Job, intervalos independentes, comandos Windows |
| Relatórios | Parcial | CSV de incidentes e impressão; outros relatórios pendentes |
| Auditoria | Parcial | Ações principais registradas, sem payload de senhas |
| Notificações | Parcial | Centro de leitura, emissão em novas ocorrências e leitura; preferências avançadas pendentes |

## Não entregar como implementado nem certificado

- Correlação avançada de falhas WAN/AP/servidores, topologia e dependências automáticas de serviços.
- SLO/SLA reais por calendário, disponibilidade calculada por série temporal, tempos médios por grupo/serviço e relatórios completos.
- Teste de restaurabilidade, verificação detalhada de Duplicati e métricas históricas reais de todas as ferramentas.
- Inventário bidirecional e reconciliação automática do mesmo ativo em múltiplas fontes.
- Gráficos detalhados por MikroTik, UniFi, Zabbix e Wazuh, coleta de eventos profundos, vulnerabilidades, desempenho e network flow.
- Janelas de manutenção estão incluídas para silenciar novos alertas; faltam regras completas de escalonamento e notificações Teams/Telegram/e-mail.
- Dashboard com personalização persistente, busca global indexada e filtros persistentes entre páginas.
- MFA, SSO, política de sessões avançada, trilhas de auditoria imutáveis e testes de penetração.
- Retenção automática, agregação histórica, jobs especializados de dedupe/reconciliação e monitoração avançada de fila/worker.
- Testes executados de Browser/Apache/MySQL/Laravel no ambiente real; API testada com a versão real de cada ferramenta.

## Verificações possíveis neste ambiente de geração

- `php -l` aplicado aos arquivos PHP do pacote: **sem erros sintáticos**.
- `node --check` aplicado ao JavaScript próprio e arquivos de configuração Vite: **sem erros sintáticos**.
- Verificação estrutural de arquivos, rotas referenciadas e empacotamento ZIP.

**Não foram executados** `composer install`, `php artisan`, migrations no MySQL, `npm install`, `npm run build`, PHPUnit, testes com navegador e conexões às seis APIs: o ambiente não possui Composer, acesso a repositórios de dependências nem driver PDO de banco. O usuário deverá executar os testes do repositório no ServBay antes de considerar o sistema pronto.
