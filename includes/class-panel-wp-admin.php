<?php
namespace PanelWPConnector\Admin;

/**
 * Classe responsável pela administração do Plugin Panel WP Connector
 * Gerencia menu administrativo, configurações e recursos do painel
 * @package PanelWPConnector\Admin
 * @since 0.3.0
 */
class PanelWPAdmin {
    /**
     * Nome da opção de configurações do plugin
     * @var string
     */
    private const PLUGIN_OPTIONS = 'panel_wp_connector_options';

    /**
     * Nome da opção de IPs autorizados
     * @var string
     */
    private const AUTHORIZED_IPS_OPTION = 'panel_wp_authorized_ips';

    /**
     * Instância única da classe (Singleton)
     * @var PanelWPAdmin|null
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     * Implementa o padrão Singleton para gerenciar a instância do admin
     * @return PanelWPAdmin Instância única da classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self(true);
        }
        return self::$instance;
    }

    /**
     * Construtor privado para implementar Singleton
     * Configura hooks e ações administrativas
     * @param bool $allow_instantiation Permite instanciação controlada
     * @throws \Exception Se tentativa de instanciar incorretamente
     */
    public function __construct($allow_instantiation = false) {
        // Verificar se a instanciação é permitida
        if (!$allow_instantiation && !self::$instance) {
            wp_die(
                __('Construtor privado. Use get_instance() para criar uma instância.', 'panel-wp-connector'),
                __('Erro de Instanciação', 'panel-wp-connector'),
                ['response' => 403]
            );
        }

        // Registrar hooks administrativos com prioridade alta
        $this->registrar_hooks_admin();
    }

    /**
     * Registra hooks e ações para o painel administrativo
     * @since 0.3.0
     * @return void
     */
    private function registrar_hooks_admin() {
        add_action('admin_menu', [$this, 'adicionar_menu_admin'], 999);
        add_action('admin_init', [$this, 'registrar_configuracoes']);
        add_action('admin_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('admin_init', [$this, 'registrar_configuracoes_ips_autorizados']);
        add_action('admin_post_salvar_ips_autorizados', [$this, 'salvar_ips_autorizados']);
    }

    /**
     * Adiciona menu administrativo no painel do WordPress
     * Cria entrada de menu personalizada para o Plugin Panel WP Connector
     * @since 0.3.0
     * @global array $submenu Lista de submenus do WordPress
     * @return void
     */
    public function adicionar_menu_admin() {
        global $submenu;
        $menu_slug = 'panel-wp-connector';

        // Limpar menus existentes para evitar duplicações
        $this->limpar_menus_existentes($submenu, $menu_slug);

        // Adicionar menu principal
        add_menu_page(
            __('Panel WP Connector', 'panel-wp-connector'), 
            __('Panel Connector', 'panel-wp-connector'), 
            'manage_options', 
            $menu_slug, 
            [$this, 'renderizar_pagina_admin'],
            'dashicons-admin-network',
            99
        );

        // Configurações
        add_submenu_page(
            $menu_slug,
            __('Configurações', 'panel-wp-connector'),
            __('Configurações', 'panel-wp-connector'),
            'manage_options',
            $menu_slug,
            [$this, 'renderizar_pagina_admin']
        );

        // Endpoints
        add_submenu_page(
            $menu_slug,
            __('Endpoints', 'panel-wp-connector'),
            __('Endpoints', 'panel-wp-connector'),
            'manage_options',
            'panel-wp-endpoints',
            [$this, 'renderizar_pagina_endpoints']
        );

        // Informações do Sistema
        add_submenu_page(
            $menu_slug,
            __('Informações do Sistema', 'panel-wp-connector'),
            __('Informações do Sistema', 'panel-wp-connector'),
            'manage_options',
            'panel-wp-system-info',
            [$this, 'renderizar_pagina_sistema']
        );
    }

    /**
     * Remove menus existentes para prevenir duplicações
     * @since 0.3.0
     * @param array &$submenu Lista global de submenus
     * @param string $menu_slug Slug do menu
     * @return void
     */
    private function limpar_menus_existentes(&$submenu, $menu_slug) {
        if (isset($submenu[$menu_slug])) {
            foreach ($submenu[$menu_slug] as $index => $item) {
                remove_submenu_page($menu_slug, $item[2]);
            }
            unset($submenu[$menu_slug]);
        }
    }

    /**
     * Registra configurações do plugin
     * Define opções de configuração e campos para o painel administrativo
     * @since 0.3.0
     * @return void
     */
    public function registrar_configuracoes() {
        register_setting(
            'panel_wp_connector_settings', 
            self::PLUGIN_OPTIONS, 
            [$this, 'sanitizar_configuracoes']
        );

        add_settings_section(
            'configuracoes_principais', 
            '', 
            '__return_false', 
            'panel-wp-connector'
        );

        add_settings_field(
            'log_autenticacoes', 
            __('Registrar Autenticações', 'panel-wp-connector'), 
            [$this, 'callback_campo_log_autenticacoes'], 
            'panel-wp-connector', 
            'configuracoes_principais'
        );

        add_settings_field(
            'usuario_autenticacao', 
            __('Usuário para Autenticação', 'panel-wp-connector'), 
            [$this, 'callback_campo_usuario_autenticacao'], 
            'panel-wp-connector', 
            'configuracoes_principais'
        );
    }

    /**
     * Renderiza página de configurações do plugin
     * Inclui template de configurações para o painel administrativo
     * @since 0.3.0
     * @return void
     */
    public function renderizar_pagina_admin() {
        require_once PANEL_WP_CONNECTOR_PATH . 'admin/views/configuracoes.php';
    }

    /**
     * Renderiza página de endpoints do plugin
     * Inclui template de endpoints para o painel administrativo
     * @since 0.3.0
     * @return void
     */
    public function renderizar_pagina_endpoints() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Incluir o template
        require_once PANEL_WP_CONNECTOR_PATH . 'admin/views/endpoints.php';
    }

    /**
     * Renderiza página de informações do sistema do plugin
     * Inclui template de informações do sistema para o painel administrativo
     * @since 0.3.0
     * @return void
     */
    public function renderizar_pagina_sistema() {
        require_once PANEL_WP_CONNECTOR_PATH . 'admin/views/sistema.php';
    }

    /**
     * Renderiza página de chaves API do plugin
     * Inclui template de chaves API para o painel administrativo
     * @since 0.3.0
     * @return void
     */
    public function renderizar_pagina_chaves_api() {
        require_once PANEL_WP_CONNECTOR_PATH . 'admin/views/chaves-api.php';
    }

    /**
     * Callback para campo de log de autenticações
     * Renderiza campo de checkbox para ativar/desativar log de autenticações
     * @since 0.3.0
     * @return void
     */
    public function callback_campo_log_autenticacoes() {
        $opcoes = get_option(self::PLUGIN_OPTIONS);
        $checked = !empty($opcoes['log_autenticacoes']) ? 'checked' : '';
        ?>
        <input 
            type="checkbox" 
            id="log_autenticacoes" 
            name="<?php echo self::PLUGIN_OPTIONS; ?>[log_autenticacoes]" 
            value="1" 
            <?php echo $checked; ?>
        />
        <label for="log_autenticacoes"><?php _e('Ativar registro de log para autenticações', 'panel-wp-connector'); ?></label>
        <?php
    }

    /**
     * Callback para campo de usuário de autenticação
     * Renderiza campo de seleção para escolher usuário de autenticação
     * @since 0.3.0
     * @return void
     */
    public function callback_campo_usuario_autenticacao() {
        $opcoes = get_option(self::PLUGIN_OPTIONS);
        $usuario_selecionado = isset($opcoes['usuario_autenticacao']) ? $opcoes['usuario_autenticacao'] : '';

        // Buscar usuários com papel de administrador
        $usuarios = get_users([
            'role__in' => ['administrator', 'editor'],
            'orderby' => 'display_name'
        ]);

        ?>
        <select 
            id="usuario_autenticacao" 
            name="<?php echo self::PLUGIN_OPTIONS; ?>[usuario_autenticacao]" 
            class="panel-wp-select"
        >
            <option value=""><?php _e('Selecione um usuário', 'panel-wp-connector'); ?></option>
            <?php foreach ($usuarios as $usuario): ?>
                <option 
                    value="<?php echo esc_attr($usuario->ID); ?>"
                    <?php selected($usuario->ID, $usuario_selecionado); ?>
                >
                    <?php echo esc_html($usuario->display_name . ' (' . $usuario->user_login . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Usuário que será utilizado para realizar autenticações automáticas do sistema.', 'panel-wp-connector'); ?></p>
        <?php
    }

    /**
     * Sanitiza configurações do plugin
     * Valida e sanitiza opções de configuração para o painel administrativo
     * @since 0.3.0
     * @param array $input Opções de configuração
     * @return array Opções de configuração sanitizadas
     */
    public function sanitizar_configuracoes($input) {
        $nova_configuracao = [];
        $nova_configuracao['log_autenticacoes'] = !empty($input['log_autenticacoes']) ? 1 : 0;
        
        // Validar e sanitizar o usuário de autenticação
        if (!empty($input['usuario_autenticacao'])) {
            $usuario = get_user_by('ID', intval($input['usuario_autenticacao']));
            $nova_configuracao['usuario_autenticacao'] = $usuario ? $usuario->ID : '';
        }
        
        return $nova_configuracao;
    }

    /**
     * Registra assets para o painel administrativo
     * Inclui CSS e JavaScript para o painel administrativo
     * @since 0.3.0
     * @param string $hook Hook do WordPress
     * @return void
     */
    public function registrar_assets($hook) {
        // Debug: Imprimir informações importantes
        error_log('Panel WP Debug - Hook atual: ' . $hook);
        error_log('Panel WP Debug - PANEL_WP_CONNECTOR_URL: ' . PANEL_WP_CONNECTOR_URL);
        
        // Definir páginas do plugin
        $plugin_pages = [
            'toplevel_page_panel-wp-connector',
            'panel-connector_page_panel-wp-endpoints',
            'panel-connector_page_panel-wp-system-info'
        ];
        
        // Verificar se estamos em uma página do plugin
        if (in_array($hook, $plugin_pages) || strpos($hook, 'panel-wp-connector') !== false) {
            error_log('Panel WP Debug - Carregando CSS na página: ' . $hook);
            
            // Caminho do CSS
            $css_url = PANEL_WP_CONNECTOR_URL . 'admin/css/panel-wp-admin.css';
            error_log('Panel WP Debug - URL do CSS: ' . $css_url);
            
            // Registrar e enfileirar o CSS
            wp_register_style(
                'panel-wp-admin-styles',
                $css_url,
                [],
                PANEL_WP_CONNECTOR_VERSION
            );
            
            wp_enqueue_style('panel-wp-admin-styles');
            error_log('Panel WP Debug - CSS enfileirado com sucesso');
        }
    }

    /**
     * Registra configurações de IPs autorizados
     * Define opções de configuração para IPs autorizados
     * @since 0.3.0
     * @return void
     */
    public function registrar_configuracoes_ips_autorizados() {
        register_setting(
            'panel_wp_authorized_ips_group', 
            'panel_wp_authorized_ips', 
            [$this, 'sanitizar_ips_autorizados']
        );
    }

    /**
     * Sanitiza IPs autorizados
     * Valida e sanitiza IPs autorizados
     * @since 0.3.0
     * @param array $input IPs autorizados
     * @return array IPs autorizados sanitizados
     */
    public function sanitizar_ips_autorizados($input) {
        $nova_configuracao = [
            'ips' => [],
            'urls' => []
        ];

        // Sanitizar IPs
        if (!empty($input['ips'])) {
            // Verificar se já é um array ou uma string
            $ips = is_array($input['ips']) ? $input['ips'] : explode("\n", $input['ips']);
            
            foreach ($ips as $ip) {
                $ip_sanitizado = filter_var(trim($ip), FILTER_VALIDATE_IP);
                if ($ip_sanitizado) {
                    $nova_configuracao['ips'][] = $ip_sanitizado;
                }
            }
        }

        // Sanitizar URLs
        if (!empty($input['urls'])) {
            // Verificar se já é um array ou uma string
            $urls = is_array($input['urls']) ? $input['urls'] : explode("\n", $input['urls']);
            
            foreach ($urls as $url) {
                $url_sanitizada = filter_var(trim($url), FILTER_VALIDATE_URL);
                if ($url_sanitizada) {
                    $nova_configuracao['urls'][] = $url_sanitizada;
                }
            }
        }

        return $nova_configuracao;
    }

    /**
     * Salva IPs autorizados
     * Salva IPs autorizados no banco de dados
     * @since 0.3.0
     * @return void
     */
    public function salvar_ips_autorizados() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para realizar esta ação.', 'panel-wp-connector'));
        }

        // Verificar nonce
        check_admin_referer('panel_wp_authorized_ips_action', '_wpnonce');

        // Log de depuração
        error_log('Salvando IPs autorizados - Input recebido: ' . print_r($_POST, true));

        // Recuperar e sanitizar input
        $input = isset($_POST['panel_wp_authorized_ips']) ? $_POST['panel_wp_authorized_ips'] : [];
        
        // Log de depuração do input
        error_log('Input processado: ' . print_r($input, true));

        $configuracao_sanitizada = $this->sanitizar_ips_autorizados($input);

        // Log de configuração sanitizada
        error_log('Configuração sanitizada: ' . print_r($configuracao_sanitizada, true));

        // Salvar configuração
        $resultado = update_option('panel_wp_authorized_ips', $configuracao_sanitizada);

        // Log de resultado do salvamento
        error_log('Resultado do salvamento: ' . ($resultado ? 'Sucesso' : 'Falha'));

        // Redirecionar de volta com mensagem de sucesso
        wp_redirect(
            add_query_arg(
                'message', 
                'ips_salvos', 
                admin_url('admin.php?page=panel-wp-connector')
            )
        );
        exit;
    }
}
