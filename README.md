# Panel WP Connector

## Descrição
Plugin WordPress para integração com sistema central de automação e gerenciamento de sites.

## Funcionalidades
- Autenticação segura de aplicativos gerenciadores
- Geração de chaves de API únicas
- Endpoints REST para:
  - Autenticação
  - Status do site
  - Execução de tarefas simples

## Endpoints

### Autenticação
- **URL**: `/wp-json/panel-wp/v1/authenticate`
- **Método**: POST
- **Cabeçalhos Necessários**: 
  - `X-Panel-App-Secret`: Chave secreta do aplicativo
- **Corpo da Requisição**:
  ```json
  {
    "app_id": "identificador_do_aplicativo",
    "app_name": "Nome do Aplicativo"
  }
  ```

### Status do Site
- **URL**: `/wp-json/panel-wp/v1/status`
- **Método**: GET
- **Cabeçalhos Necessários**:
  - `X-Panel-API-Key`: Chave de API gerada na autenticação

### Execução de Tarefas
- **URL**: `/wp-json/panel-wp/v1/execute-task`
- **Método**: POST
- **Cabeçalhos Necessários**:
  - `X-Panel-API-Key`: Chave de API gerada na autenticação
- **Tarefas Suportadas**:
  - `update_plugin`: Atualizar plugin
  - `clear_cache`: Limpar cache do site

## Segurança
- Chave secreta para validação de origem
- Geração de chaves de API únicas
- Armazenamento seguro de aplicativos autorizados

## Requisitos
- WordPress 5.0+
- PHP 7.4+

## Instalação
1. Faça o upload da pasta `panel-wp-connector` para o diretório `/wp-content/plugins/`
2. Ative o plugin no painel de administração do WordPress

## Configuração
Nenhuma configuração adicional é necessária após a instalação.

## Limitações
- As tarefas de execução são básicas e devem ser expandidas conforme necessário
- Recomenda-se implementações adicionais de segurança para ambientes de produção

## Contribuição
Contribuições são bem-vindas. Por favor, abra uma issue ou envie um pull request.

## Licença
[Especificar a licença]
