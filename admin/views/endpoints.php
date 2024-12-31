<?php
if (!defined('ABSPATH')) {
    exit;
}

$base_url = rest_url('panel-wp/v1/');
$endpoints = [
    'authenticate' => [
        'method' => 'POST',
        'description' => 'Registrar e autenticar um novo aplicativo',
        'params' => []
    ],
    'status' => [
        'method' => 'GET',
        'description' => 'Obter status básico do site',
        'params' => []
    ],
    'execute-task' => [
        'method' => 'POST',
        'description' => 'Executar uma tarefa específica',
        'params' => [
            'task' => 'Nome da tarefa a ser executada',
            'params' => 'Parâmetros da tarefa (opcional)'
        ]
    ],
    'system-info' => [
        'method' => 'GET',
        'description' => 'Recuperar informações detalhadas do sistema',
        'params' => []
    ]
];

?>

<div class="wrap panel-wp-wrapper panel-wp-endpoints-page">
    <div class="panel-wp-header panel-wp-endpoints-header">
        <h1 class="panel-wp-endpoints-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="panel-wp-endpoints-description"><?php _e('Integre facilmente aplicações externas com o seu site WordPress usando nossa API REST segura e simples.', 'panel-wp-connector'); ?></p>
        
        <div class="panel-wp-endpoint-url panel-wp-base-url">
            <span class="panel-wp-endpoint-url-text panel-wp-base-url-text"><?php echo esc_html($base_url); ?></span>
            <button class="panel-wp-button panel-wp-button-secondary panel-wp-base-url-button" onclick="navigator.clipboard.writeText('<?php echo esc_js($base_url); ?>')">
                <?php _e('Copiar URL Base', 'panel-wp-connector'); ?>
            </button>
        </div>
    </div>
    
    <div class="panel-wp-endpoints-container panel-wp-endpoints-grid">
        <?php foreach ($endpoints as $endpoint => $details): ?>
        <div class="panel-wp-endpoint-card panel-wp-endpoint-item panel-wp-endpoint-<?php echo esc_attr($endpoint); ?>">
            <div class="panel-wp-endpoint-header panel-wp-endpoint-header-<?php echo strtolower($details['method']); ?>">
                <span class="panel-wp-endpoint-method <?php echo strtolower($details['method']); ?> panel-wp-endpoint-method-<?php echo esc_attr($endpoint); ?>">
                    <?php echo esc_html($details['method']); ?>
                </span>
                <span class="panel-wp-endpoint-name panel-wp-endpoint-name-<?php echo esc_attr($endpoint); ?>"><?php echo esc_html($endpoint); ?></span>
            </div>
            
            <div class="panel-wp-endpoint-url panel-wp-endpoint-url-<?php echo esc_attr($endpoint); ?>">
                <span class="panel-wp-endpoint-url-text panel-wp-endpoint-url-text-<?php echo esc_attr($endpoint); ?>"><?php echo esc_html($base_url . $endpoint); ?></span>
                <button class="panel-wp-button panel-wp-button-secondary panel-wp-endpoint-copy-button panel-wp-endpoint-copy-button-<?php echo esc_attr($endpoint); ?>" onclick="navigator.clipboard.writeText('<?php echo esc_js($base_url . $endpoint); ?>')">
                    <?php _e('Copiar', 'panel-wp-connector'); ?>
                </button>
            </div>
            
            <div class="panel-wp-endpoint-description panel-wp-endpoint-description-<?php echo esc_attr($endpoint); ?>">
                <?php echo esc_html($details['description']); ?>
            </div>

            <?php if (!empty($details['params'])): ?>
            <div class="panel-wp-endpoint-params panel-wp-endpoint-params-<?php echo esc_attr($endpoint); ?>">
                <div class="panel-wp-endpoint-params-title panel-wp-endpoint-params-title-<?php echo esc_attr($endpoint); ?>"><?php _e('Parâmetros', 'panel-wp-connector'); ?></div>
                <ul class="panel-wp-endpoint-params-list panel-wp-endpoint-params-list-<?php echo esc_attr($endpoint); ?>">
                    <?php foreach ($details['params'] as $param => $desc): ?>
                    <li class="panel-wp-endpoint-param panel-wp-endpoint-param-<?php echo esc_attr($param); ?>">
                        <span class="param-name param-name-<?php echo esc_attr($param); ?>"><?php echo esc_html($param); ?></span>
                        <span class="param-description param-description-<?php echo esc_attr($param); ?>"><?php echo esc_html($desc); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="panel-wp-auth-card panel-wp-authentication-card">
        <h2 class="panel-wp-auth-title"><?php _e('Autenticação', 'panel-wp-connector'); ?></h2>
        <p class="panel-wp-auth-description"><?php _e('Todas as requisições devem incluir o cabeçalho:', 'panel-wp-connector'); ?></p>
        <code class="panel-wp-auth-code"><?php _e('X-Panel-WP-Key: sua_chave_api', 'panel-wp-connector'); ?></code>
        <p class="panel-wp-auth-details"><?php _e('Você pode gerar e gerenciar suas chaves de API na página de configurações.', 'panel-wp-connector'); ?></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const botoesCopiar = document.querySelectorAll('.panel-wp-button-secondary');
    
    botoesCopiar.forEach(botao => {
        botao.addEventListener('click', function(e) {
            e.preventDefault();
            const textoOriginal = this.textContent;
            this.textContent = '<?php _e('Copiado!', 'panel-wp-connector'); ?>';
            this.classList.add('copied');
            
            setTimeout(() => {
                this.textContent = textoOriginal;
                this.classList.remove('copied');
            }, 2000);
        });
    });
});</script>
