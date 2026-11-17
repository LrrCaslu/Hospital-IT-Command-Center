# Segurança e proteção de dados — notas de implantação

Este código **não é certificado para produção hospitalar**. Não utilize dados pessoais de pacientes nem informações de prontuários em cadastros, comentários ou testes.

- Restrinja a plataforma ao segmento administrativo/TI, preferencialmente atrás de VPN e controle de acesso de rede.
- Substitua as senhas demonstrativas; não publique o banco MySQL ou o Apache diretamente na Internet.
- Configure `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL=https://...` e cookies seguros sob HTTPS antes de implantar fora do ambiente local.
- A `APP_KEY` criptografa segredos e sessões. Faça gerenciamento seguro dessa chave, sem colocá-la no Git, em tickets ou backups desprotegidos.
- Crie usuários distintos de API com permissões mínimas e **somente leitura**; ative validação de certificados com CA interna confiável.
- O administrador que configura endpoints das integrações pode acessar serviços HTTPS internos a partir do servidor. Para produção, implante **restrições de egress**, lista de destinos aprovados e revisão de SSRF para a rede local.
- Separe os perfis SOC/NOC/gestores. Em futuras versões, criptografe ou oculte dados técnicos detalhados também nas trilhas de auditoria e no banco.
- Configurações sensíveis só podem ser alteradas por `integrations.manage` e `settings.manage`. O código protege telas e endpoints; testes de penetração e uma revisão independente ainda são necessários.
- Sanitização dos logs dos conectores: mensagens de erro públicas contêm a categoria da falha, mas não incluem resposta bruta da API nem credenciais.
- Backups do banco e de `APP_KEY`, retenção, rotatividade de logs, expiração de tokens, MFA/SSO e testes de recuperação são pré-requisitos para produção; não estão automatizados neste MVP.
