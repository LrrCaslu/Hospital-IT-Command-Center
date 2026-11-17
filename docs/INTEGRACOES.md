# Integrações externas — implementação e compatibilidade

## Princípios

Nenhum conector está validado com equipamentos reais neste pacote. Uma chamada de `Testar conexão` somente é considerada bem-sucedida se a API configurada responder com autenticação compatível. As interfaces oficiais variam conforme versões e tipo de implantação. **Não desative verificação TLS**: instale sua CA interna ou indique um bundle `.pem` confiável no formulário administrativo.

Todos os adaptadores implementam `test(Integration)` e `collect(Integration)`. Um resultado normalizado segue as chaves `assets`, `metrics`, `tickets` e `meta`. Para persistir ativos ou tickets integrados, associe a integração a uma unidade.

## MikroTik

- API utilizada: RouterOS **v7 REST API**, endpoint de leitura `GET /rest/system/resource`, com HTTPS e Basic Auth.
- Implementado: teste autenticado, dados de CPU, RAM, uptime, versão (metadata de coleta) e ativo roteador.
- Não implementado: histórico de tráfego WAN, interface/switchport, latência ICMP real, múltiplos links e monitoramento de perda de pacotes. Não os confunda com CPU/RAM.
- Documento: https://manual.mikrotik.com/docs/developer-guides/rest-api/
- Criar usuário somente leitura no RouterOS, evitar Internet pública e configurar certificado válido.

## GLPI

- API implementada: **GLPI REST V1**, `GET /apirest.php/initSession`, `GET /apirest.php/Ticket`, `GET /apirest.php/killSession`.
- Autenticação via cabeçalho `Authorization: user_token` e `App-Token` opcional.
- Implementado: tickets resumidos por status/prioridade. O GLPI permanece a fonte oficial; snapshots locais não alteram tickets.
- Falta: paginação completa, SLA por calendário/entidade, técnicos, categorias, associações complexas e GLPI REST V2.
- Documento: https://help.glpi-project.org/documentation/modules/configuration/general/api/api

## UniFi

- A **API oficial local de Network** disponibiliza documentação específica dentro de **Network → Integrações**; há diferenças entre consoles, versões e a API do Site Manager.
- Implementado: requisição HTTPS autenticada via `X-API-Key` a caminho **explicitamente configurado**, com normalização básica de lista `data`.
- **O endpoint não vem pré-preenchido**. Confirme a API oficial da versão do controlador e informe `devices_path`; validação do esquema e mapeamento adicional podem ser necessários.
- Não afirmar que todas as métricas wireless funcionam sem documentação do controlador real.
- Documento: https://help.ui.com/hc/en-us/articles/30076656117655-Getting-Started-with-the-Official-UniFi-API

## Zabbix

- API: JSON-RPC em `POST /api_jsonrpc.php` autenticado por Bearer token; método `host.get`.
- Implementado: inventário de hosts e estado da interface de monitoramento, caso fornecido pelo retorno oficial. Host habilitado **não** é automaticamente interpretado como online.
- Falta: `problem.get`, métricas históricas, triggers, severity, serviços dependentes e gráficos por item.
- Documento: https://www.zabbix.com/documentation/current/en/manual/api

## Wazuh

- API: `POST /security/user/authenticate`, seguida de `GET /agents` com token JWT.
- Implementado: teste de autenticação, lista resumida de agentes. Agente desconectado é mostrado com estado **desconhecido** no inventário, não como host fisicamente offline.
- Falta: indexador, vulnerabilidades, enriquecimento de alertas, correlação de segurança e tratamento de eventos sensíveis.
- Documento: https://documentation.wazuh.com/current/user-manual/api/reference.html

## Duplicati

- A superfície disponível muda conforme a **versão exata**, autenticação e API do servidor Duplicati.
- Implementado: adaptador HTTPS autenticado, com caminho de jobs **configurado** pelo administrador; teste de conexão real.
- **Não implementado**: mapeamento confiável de jobs, histórico de execuções e erros em todas as versões. Nenhuma informação é fabricada se a API não for conhecida.
- Exigir teste real de restauração: backup concluído não garante restaurabilidade.
- Consultar a documentação da instalação Duplicati em uso.

## Procedimento de ativação

1. Cadastre a URL HTTPS interna e as credenciais de uma conta **somente leitura**.
2. Vincule a integração à unidade, se desejar persistir ativos dessa origem.
3. Escolha `Integrado`, salve e clique em **Testar conexão**.
4. Verifique status e mensagens sanitizadas.
5. Habilite a coleta automática e execute `INICIAR_COLETOR_WINDOWS.bat` + `INICIAR_FILAS_WINDOWS.bat`.
6. Confira na tela de integrações o horário da última tentativa e sucesso; não assuma sucesso se o estado estiver desatualizado.

Sem a fonte real e a versão específica, nenhum dos seis conectores foi considerado certificado para produção.
