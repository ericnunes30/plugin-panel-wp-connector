<?php
namespace PanelWPConnector\Routes;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use PanelWPConnector\Authentication\PanelWPAuthentication;
use PanelWPConnector\Core\PanelWPCore;

class PanelWPRestRoutes {
    private $authentication;
    private $core;

    public function __construct() {
        $this->authentication = new PanelWPAuthentication();
        $this->core = new PanelWPCore();
        
        add_action('rest_api_init', [$this, 'registrar_rotas']);
    }

    /**
     * Registra todas as rotas da API REST do Plugin
     */
    public function registrar_rotas() {
        // Adicionar headers CORS para todos os endpoints
        add_filter('rest_pre_serve_request', function($response) {
            header('Access-Control-Allow-Origin: http://localhost:3000');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: X-Api-Key, Content-Type');
            header('Access-Control-Allow-Credentials: true');
            return $response;
        });

        // Rota de Autenticação
        register_rest_route('panel-wp/v1', '/authenticate', [
            'methods' => ['POST', 'OPTIONS'],
            'callback' => [$this, 'autenticar_aplicativo'],
            'permission_callback' => '__return_true'
        ]);

        // Rota de Status do Site
        register_rest_route('panel-wp/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'obter_status_site'],
            'permission_callback' => '__return_true'
        ]);

        // Rota para Execução de Tarefas
        register_rest_route('panel-wp/v1', '/execute-task', [
            'methods' => 'POST',
            'callback' => [$this, 'executar_tarefa'],
            'permission_callback' => [$this, 'validar_chave_api']
        ]);

        // Rota para Informações do Sistema
        register_rest_route('panel-wp/v1', '/system-info', [
            'methods' => 'GET',
            'callback' => [$this, 'obter_informacoes_sistema'],
            'permission_callback' => [$this, 'validar_chave_api']
        ]);
    }

    /**
     * Valida a chave de API para rotas protegidas
     * 
     * @param WP_REST_Request $request Requisição da API
     * @return bool Indica se a chave de API é válida
     */
    public function validar_chave_api(WP_REST_Request $request) {
        $api_key = $request->get_header('X-API-KEY');
        
        if (!$api_key) {
            return new WP_Error(
                'sem_chave_api', 
                __('Chave de API não fornecida.', 'panel-wp-connector'), 
                ['status' => 401]
            );
        }

        $user_id = $this->authentication->validar_chave_api($api_key);
        
        return $user_id !== false;
    }

    /**
     * Autentica um novo aplicativo
     * 
     * @param WP_REST_Request $request Requisição da API
     * @return WP_REST_Response Resposta da autenticação
     */
    public function autenticar_aplicativo(WP_REST_Request $request) {
        // Obter a chave de API do cabeçalho ou do corpo da requisição
        $api_key = $request->get_header('X-Api-Key') ?? $request->get_param('api_key');
        
        if (!$api_key) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => __('Chave de API não fornecida.', 'panel-wp-connector')
            ], 401);
        }

        // Validar a chave de API
        $user_id = $this->authentication->validar_chave_api($api_key);
        
        if ($user_id === false) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => __('Chave de API inválida.', 'panel-wp-connector')
            ], 401);
        }

        // Obter informações do usuário
        $user = get_userdata($user_id);

        return new WP_REST_Response([
            'status' => 'success',
            'message' => __('Autenticação realizada com sucesso.', 'panel-wp-connector'),
            'user' => [
                'id' => $user->ID,
                'login' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'roles' => $user->roles
            ],
            'site' => [
                'name' => get_bloginfo('name'),
                'url' => get_site_url(),
                'description' => get_bloginfo('description'),
                'admin_email' => get_bloginfo('admin_email'),
                'language' => get_bloginfo('language'),
                'wordpress_version' => get_bloginfo('version'),
                'timezone' => wp_timezone_string()
            ]
        ], 200);
    }

    /**
     * Obtém o status básico do site
     * 
     * @return WP_REST_Response Status do site
     */
    public function obter_status_site() {
        return new WP_REST_Response([
            'status' => 'online',
            'wordpress_version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'timezone' => wp_timezone_string()
        ], 200);
    }

    /**
     * Executa uma tarefa específica
     * 
     * @param WP_REST_Request $request Requisição da API
     * @return WP_REST_Response Resultado da tarefa
     */
    public function executar_tarefa(WP_REST_Request $request) {
        $task = $request->get_param('task');
        $params = $request->get_param('params') ?? [];

        if (!$task) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => __('Tarefa não especificada.', 'panel-wp-connector')
            ], 400);
        }

        // Executa a tarefa usando o núcleo do plugin
        $resultado = $this->core->executar_tarefa($task, $params);

        return new WP_REST_Response($resultado, $resultado['status'] === 'success' ? 200 : 400);
    }

    /**
     * Obtém informações detalhadas do sistema
     * 
     * @return WP_REST_Response Informações do sistema
     */
    public function obter_informacoes_sistema() {
        return new WP_REST_Response([
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->obter_versao_mysql(),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'plugins_ativos' => $this->listar_plugins_ativos(),
            'tema_atual' => wp_get_theme()->get('Name')
        ], 200);
    }

    /**
     * Obtém a versão do MySQL
     * 
     * @return string Versão do MySQL
     */
    private function obter_versao_mysql() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
    }

    /**
     * Lista plugins ativos
     * 
     * @return array Lista de plugins ativos
     */
    private function listar_plugins_ativos() {
        $plugins = get_plugins();
        $ativos = get_option('active_plugins');
        
        return array_map(function($plugin_path) use ($plugins) {
            return $plugins[$plugin_path]['Name'];
        }, $ativos);
    }
}

// Inicializar as rotas
new PanelWPRestRoutes();
