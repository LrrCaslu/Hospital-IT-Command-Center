# Arquitetura e modelo operacional

## Componentes

```
Apache (ServBay) → Laravel web.php → Controllers / Middleware RBAC
                                        ↓
                              Services / Eloquent
                                        ↓
MySQL 8+ ← Jobs de integração ← CollectorCommand (processo local)
                                        ↓
                              Conectores HTTPS somente leitura
                MikroTik / GLPI / UniFi / Zabbix / Wazuh / Duplicati

Dashboard Blade + Livewire (IncidentPulse) + JavaScript de atualização 10s
                   ↳ GET /api/dashboard (sessão autenticada)
```

A camada de apresentação não recebe credenciais; estas permanecem exclusivamente no backend. `routes/api.php` é atendida com sessão e verificação de permissão. Os comandos do coletor são independentes do ciclo de atualização visual.

### Arquivos relevantes

- `database/migrations/2026_11_16_000001_create_noc_tables.php`: DDL do domínio
- `database/seeders/DatabaseSeeder.php`: unidades, 72 ativos e 30 dias de métricas inteiramente fictícias
- `app/Services/DashboardService.php`: cálculo de indicadores e pontos de atenção
- `app/Services/AlertService.php`: criação, reabertura e deduplicação básica de alertas
- `app/Services/DemoSimulator.php`: simulação periódica de CPU e RAM
- `app/Services/Connectors`: contrato comum para APIs externas
- `app/Services/IntegrationSyncService.php`: coleta, normalização, persistência e logs sanitizados
- `app/Jobs/SyncIntegrationJob.php`: execução pelo worker
- `app/Console/Commands/CollectorCommand.php`: loop com intervalo de 10 segundos
- `app/Models/User.php` e `app/Http/Middleware/EnsurePermission.php`: segurança por perfil/unidade

## Escopo e identidade de ativos

Uma unidade é referenciada por `organizational_unit_id`. Identidades vindas de ferramentas externas são registradas em `asset_identifiers` com a chave única `(integration_id, external_id)`. O endereço IP **não** é identidade primária do ativo. A junção automática de duas ferramentas ao mesmo ativo físico não é tentada sem regras verificáveis; deve ser aprimorada antes de produção.

## Pontos de atenção

**Fórmula atualmente implementada:** `incidentes não resolvidos/encerrados + alertas ativos/reconhecidos sem incidente associado e não silenciados`, após filtrar unidades autorizadas. Um conjunto de alertas ligados ao mesmo incidente é contabilizado **uma vez** pelo incidente. Incidentes apenas reconhecidos continuam ativos. O cálculo usa o estado persistido atual, não o total bruto de notificações históricas.

## Disponibilidade exibida

A taxa de disponibilidade do dashboard é a **média simples do campo `availability_pct` das unidades autorizadas**; dados demonstrativos são fictícios. **Não se trata de cálculo de SLA auditável em produção**. É necessário construir medições por janelas e validação do tempo observado para disponibilidades reais.

## Coletas e envelhecimento

- `noc:collect` examina integrações habilitadas e despacha jobs ao atingir o intervalo configurado.
- `Cache::lock` reduz execução concorrente da mesma fonte.
- Coletor demonstrativo atualiza CPU/RAM em ativos online; não mascara um estado offline com falso sucesso.
- Integrações registram `last_attempt_at`, `last_success_at` e status. A interface de integrações marca dados integrados antigos como desatualizados.
- Timeout do cliente HTTP de 2 a 30s, sem redirecionamentos; novas tentativas limitadas.
- Uma falha na API marca a **integração** com erro, não cria um falso incidente de equipamento offline.

## Riscos e passos para escalar

O armazenamento de métricas é granular, com índices `asset_id + metric_key + sampled_at`; ainda **não** há agregação histórica por hora/dia, mecanismo de retenção, particionamento, ou limite de payload da origem para todas as APIs. Para produção, complementar retenção e observabilidade dos processos, health checks, backups do próprio MySQL, controle de rede de saída, MFA/SSO, gerenciamento de segredos e fluxos de aprovação de mudanças.
