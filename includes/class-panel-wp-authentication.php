<?php
namespace PanelWPConnector\Authentication;

use PanelWPConnector\Core\PanelWPCore;

/**
 * Classe responsável pela autenticação e gerenciamento de chaves API
 * Fornece métodos para geração, validação e gerenciamento de chaves de API
 * 
 * @package PanelWPConnector\Authentication
 * @since 0.3.0
 */
class PanelWPAuthentication {
    /**
     * Instância do wpdb para operações de banco de dados
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Armazena temporariamente a chave de API gerada
     * @var array|null
     */
    private $chave_temporaria = null;

    /**
     * Construtor da classe
     * Inicializa conexão com o banco de dados
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Gera uma chave de API temporária
     * Cria uma chave única para uso temporário
     * 
     * @return array Detalhes da chave gerada
     */
    public function gerar_chave_api_temporaria() {
        try {
            // Gerar chave única
            $api_key = $this->gerar_chave_unica();

            // Validar chave gerada
            if (empty($api_key)) {
                throw new \Exception('Falha ao gerar chave API');
            }

            // Armazenar chave temporária
            $this->chave_temporaria = [
                'api_key' => $api_key,
                'gerada_em' => current_time('mysql'),
                'user_id' => null
            ];

            // Registrar log de geração
            error_log('PANEL WP: Chave API temporária gerada - ' . $api_key);

            return [
                'success' => true,
                'api_key' => $api_key,
                'message' => __('Chave API temporária gerada. Selecione um usuário para salvar.', 'panel-wp-connector')
            ];
        } catch (\Exception $e) {
            error_log('PANEL WP: Erro ao gerar chave API - ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('Erro ao gerar chave API temporária.', 'panel-wp-connector')
            ];
        }
    }

    /**
     * Gera chave única para API
     * Cria uma chave segura e aleatória
     * 
     * @return string Chave API gerada
     */
    private function gerar_chave_unica() {
        // Usar função nativa do WordPress para maior segurança
        return wp_generate_password(64, false);
    }

    /**
     * Salva a chave de API temporária para um usuário específico
     * Associa a chave gerada a um usuário do WordPress
     * 
     * @param int $user_id ID do usuário
     * @return array Resultado do salvamento
     */
    public function salvar_chave_api_temporaria($user_id) {
        try {
            // Validar se existe chave temporária
            if (!$this->chave_temporaria || !$this->chave_temporaria['api_key']) {
                throw new \Exception('Nenhuma chave temporária encontrada');
            }

            // Validar usuário
            $usuario = get_userdata($user_id);
            if (!$usuario) {
                throw new \Exception('Usuário inválido');
            }

            // Preparar dados para salvar
            $dados_chave = [
                'user_id' => $user_id,
                'api_key' => $this->chave_temporaria['api_key'],
                'created_at' => $this->chave_temporaria['gerada_em']
            ];

            // Salvar chave no banco de dados
            $resultado = $this->salvar_chave_no_banco($dados_chave);

            // Limpar chave temporária após salvar
            $this->chave_temporaria = null;

            return $resultado;
        } catch (\Exception $e) {
            error_log('PANEL WP: Erro ao salvar chave API - ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Salva chave no banco de dados
     * 
     * @param array $dados_chave Dados da chave API
     * @return array Resultado da operação
     */
    private function salvar_chave_no_banco($dados_chave) {
        $tabela = $this->wpdb->prefix . 'panel_wp_api_keys';

        $resultado = $this->wpdb->insert(
            $tabela, 
            [
                'user_id' => $dados_chave['user_id'],
                'api_key' => $dados_chave['api_key'],
                'created_at' => $dados_chave['created_at']
            ],
            ['%d', '%s', '%s']
        );

        if ($resultado === false) {
            throw new \Exception('Erro ao inserir chave no banco de dados');
        }

        return [
            'success' => true,
            'message' => __('Chave API salva com sucesso.', 'panel-wp-connector')
        ];
    }

    /**
     * Define manualmente a chave temporária
     * 
     * @param string $chave_api Chave API a ser definida
     */
    public function set_chave_temporaria($chave_api) {
        error_log('DEBUG (Authentication): Definindo chave temporária manualmente');
        error_log('DEBUG (Authentication): Chave recebida: ' . $chave_api);

        $this->chave_temporaria = [
            'api_key' => $chave_api,
            'gerada_em' => current_time('mysql'),
            'user_id' => null
        ];

        error_log('DEBUG (Authentication): Chave temporária definida: ' . print_r($this->chave_temporaria, true));
    }

    /**
     * Valida uma chave de API
     * 
     * @param string $api_key Chave de API para validar
     * @return int|false ID do usuário se válido, false caso contrário
     */
    public function validar_chave_api($api_key) {
        $user_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT user_id FROM {$this->wpdb->prefix}panel_wp_api_keys WHERE api_key = %s", 
                $api_key
            )
        );

        if ($user_id) {
            // Atualizar última utilização
            $this->wpdb->update(
                $this->wpdb->prefix . 'panel_wp_api_keys',
                ['last_used' => current_time('mysql')],
                ['api_key' => $api_key]
            );
        }

        return $user_id ? intval($user_id) : false;
    }

    /**
     * Revoga uma chave de API
     * 
     * @param string $api_key Chave de API para revogar
     * @return bool Sucesso na revogação
     */
    public function revogar_chave_api($api_key) {
        return (bool) $this->wpdb->delete(
            $this->wpdb->prefix . 'panel_wp_api_keys', 
            ['api_key' => $api_key]
        );
    }

    /**
     * Listar chaves de API de um usuário
     * 
     * @param int $user_id ID do usuário
     * @return array Chaves de API do usuário
     */
    public function listar_chaves_usuario($user_id) {
        try {
            // Verificar se a tabela existe
            $table_name = $this->wpdb->prefix . 'panel_wp_api_keys';
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

            if (!$table_exists) {
                error_log("Tabela de chaves de API não encontrada: {$table_name}");
                return [];
            }

            // Buscar chaves do usuário
            $chaves = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT api_key, created_at, last_used FROM {$table_name} WHERE user_id = %d", 
                    $user_id
                ),
                ARRAY_A
            );

            // Verificar erros na consulta
            if ($this->wpdb->last_error) {
                error_log("Erro ao buscar chaves de API: " . $this->wpdb->last_error);
                return [];
            }

            return $chaves ?: [];
        } catch (Exception $e) {
            error_log("Exceção ao listar chaves de API: " . $e->getMessage());
            return [];
        }
    }
}
