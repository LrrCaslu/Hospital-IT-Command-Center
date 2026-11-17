# Manual rápido do operador

## Acesso

Abra `http://hospital-noc.test` no ServBay e autentique-se. O dashboard exibe um selo **MODO DEMO** ou **MODO INTEGRADO**. O perfil determina quais menus e unidades aparecem.

## Operação diária

1. **Visão Geral** — acompanhe os indicadores e a tabela de unidades; os cards levam a páginas específicas. Os filtros de período/unidade são enviados ao servidor; a consulta periódica atualiza valores a cada 10 segundos.
2. **Unidades Hospitalares** — abra a unidade para conferir ativos, comunicação e alertas. Cadastros e mudanças requerem permissão de gerenciamento.
3. **Inventário** — consulte categoria, estado e última coleta. Cadastre ativos manuais quando autorizado; identificadores técnicos são omitidos na visualização menos privilegiada.
4. **Alertas** — reconheça, silencie por 30 minutos ou encerre manualmente. Encerrar alerta **não altera o estado do equipamento**.
5. **Manutenções** — agende janelas por unidade; novos alertas durante a janela são silenciados e preservados para auditoria.
6. **Incidentes** — crie ocorrências, investigue, registre causa-raiz, designe responsável e comente. Encerramento requer resolução prévia. Os eventos ficam auditados.
7. **Chamados, Segurança, Backups** — listagens de dados normalizados, filtradas pelas permissões. Backups que não foram testados para restauração são identificados separadamente.
8. **Integrações** — o administrador configura URLs, tokens, escopo e coleta. A sincronização manual e os testes exigem permissão de gerenciamento.
9. **Relatórios** — exporte CSV de incidentes com período. Use a impressão do navegador para salvar em PDF.
10. **Modo TV** — visão simplificada para monitores. A página se atualiza a cada 10 segundos, sendo uma visão distinta da atualização por indicadores do dashboard.

## Dúvidas comuns

- **Valores não mudam:** o coletor deve estar em execução para gerar dados novos; sem ele o dashboard só consulta o banco.
- **Integração em erro:** verifique URL, certificado CA, token, versão e o log da última tentativa. Não interprete erro da integração como indisponibilidade do roteador.
- **Uma unidade não aparece:** confirme a associação do usuário em Administração. O bloqueio vale também para URLs diretas e APIs.
- **Conta de teste não funciona:** rode `php artisan db:seed --force` na pasta certa e confirme o mesmo banco indicado no `.env`.
- **Não encontro um recurso da especificação:** consulte `COBERTURA_E_LIMITACOES.md` para saber o que está pronto e o que é etapa seguinte.
