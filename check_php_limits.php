<?php
// Analisador de Configurações PHP para Conversão de Áudio em Massa
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Configurações PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .config-card { border-left: 4px solid #0d6efd; }
        .status-ok { border-left-color: #198754 !important; }
        .status-warning { border-left-color: #ffc107 !important; }
        .status-error { border-left-color: #dc3545 !important; }
        .config-value { font-family: 'Courier New', monospace; font-weight: bold; }
        .recommendation { background: linear-gradient(135deg, #e3f2fd, #f3e5f5); }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><i class="fas fa-cogs"></i> Análise de Configurações PHP</h2>
                        <small>Verificação de limites para conversão de áudio em massa</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <h3 class="mb-3"><i class="fas fa-sliders-h"></i> Configurações Atuais</h3>
                
                <?php
                $configs = [
                    'max_execution_time' => [
                        'name' => 'Tempo Máximo de Execução',
                        'description' => 'Tempo limite para scripts PHP executarem',
                        'optimal' => '0 (sem limite)',
                        'icon' => 'fa-clock'
                    ],
                    'memory_limit' => [
                        'name' => 'Limite de Memória',
                        'description' => 'Quantidade máxima de RAM que o script pode usar',
                        'optimal' => '≥ 512M',
                        'icon' => 'fa-memory'
                    ],
                    'max_input_vars' => [
                        'name' => 'Máximo de Variáveis POST/GET',
                        'description' => 'Número máximo de variáveis que podem ser enviadas',
                        'optimal' => '≥ 10000',
                        'icon' => 'fa-list'
                    ],
                    'max_input_time' => [
                        'name' => 'Tempo Máximo de Input',
                        'description' => 'Tempo limite para processar dados de entrada',
                        'optimal' => '-1 ou 0',
                        'icon' => 'fa-hourglass'
                    ],
                    'post_max_size' => [
                        'name' => 'Tamanho Máximo de POST',
                        'description' => 'Tamanho máximo dos dados enviados via POST',
                        'optimal' => '≥ 100M',
                        'icon' => 'fa-upload'
                    ],
                    'max_file_uploads' => [
                        'name' => 'Máximo de Uploads Simultâneos',
                        'description' => 'Número máximo de arquivos enviados simultaneamente',
                        'optimal' => '≥ 1000',
                        'icon' => 'fa-files'
                    ]
                ];

                foreach ($configs as $config_key => $config_info) {
                    $value = ini_get($config_key);
                    
                    // Determinar status baseado no valor
                    $status_class = 'status-ok';
                    $status_icon = 'fa-check-circle text-success';
                    $status_text = 'Adequado';
                    
                    // Lógica específica para cada configuração
                    switch ($config_key) {
                        case 'max_execution_time':
                            if ($value != '0') {
                                $status_class = 'status-warning';
                                $status_icon = 'fa-exclamation-triangle text-warning';
                                $status_text = 'Pode causar timeout';
                            }
                            break;
                        case 'memory_limit':
                            $memory_mb = intval($value);
                            if (stripos($value, 'g') !== false) {
                                $memory_mb *= 1024;
                            }
                            if ($memory_mb < 512) {
                                $status_class = 'status-error';
                                $status_icon = 'fa-times-circle text-danger';
                                $status_text = 'Insuficiente';
                            }
                            break;
                        case 'max_input_vars':
                            if ($value < 10000) {
                                $status_class = 'status-error';
                                $status_icon = 'fa-times-circle text-danger';
                                $status_text = 'Limitará arquivos';
                            }
                            break;
                        case 'max_file_uploads':
                            if ($value < 1000) {
                                $status_class = 'status-warning';
                                $status_icon = 'fa-exclamation-triangle text-warning';
                                $status_text = 'Pode limitar uploads';
                            }
                            break;
                    }
                ?>
                
                <div class="card config-card <?php echo $status_class; ?> mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <i class="fas <?php echo $config_info['icon']; ?> fa-2x text-primary"></i>
                            </div>
                            <div class="col-md-4">
                                <h5 class="mb-1"><?php echo $config_info['name']; ?></h5>
                                <small class="text-muted"><?php echo $config_key; ?></small>
                            </div>
                            <div class="col-md-3">
                                <span class="config-value fs-5"><?php echo $value ?: 'Não definido'; ?></span>
                            </div>
                            <div class="col-md-2">
                                <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                <small><?php echo $status_text; ?></small>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">Ideal: <?php echo $config_info['optimal']; ?></small>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted"><?php echo $config_info['description']; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php } ?>

            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-calculator"></i> Análise de Capacidade</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $max_input_vars = ini_get('max_input_vars');
                        $estimated_vars_per_file = 1;
                        $other_vars = 10;
                        $max_files_theoretical = $max_input_vars - $other_vars;
                        ?>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-list-ol"></i> Variáveis POST Disponíveis</h6>
                            <div class="bg-light p-2 rounded">
                                <strong><?php echo number_format($max_input_vars); ?></strong> variáveis
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-music"></i> Arquivos Simultâneos (Teórico)</h6>
                            <div class="bg-light p-2 rounded">
                                <strong><?php echo number_format($max_files_theoretical); ?></strong> arquivos
                            </div>
                            <small class="text-muted">Considerando 1 variável por arquivo + 10 variáveis do formulário</small>
                        </div>
                        
                        <div class="mb-3">
                            <?php if ($max_files_theoretical < 1000): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <strong>Limitação Detectada!</strong><br>
                                    <small>max_input_vars pode limitar o processamento de muitos arquivos.</small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <strong>Capacidade Adequada!</strong><br>
                                    <small>Configuração permite processar muitos arquivos simultaneamente.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm recommendation">
                    <div class="card-header bg-gradient">
                        <h4 class="mb-0"><i class="fas fa-lightbulb"></i> Configurações Recomendadas</h4>
                    </div>
                    <div class="card-body">
                        <h6>Para php.ini (Processamento Ilimitado):</h6>
                        <div class="bg-dark text-light p-3 rounded mb-3" style="font-family: 'Courier New', monospace; font-size: 0.9em;">
max_execution_time = 0<br>
memory_limit = 1G<br>
max_input_vars = 10000<br>
max_input_time = -1<br>
post_max_size = 100M<br>
max_file_uploads = 1000
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Nota:</strong><br>
                            <small>Após alterar o php.ini, reinicie o servidor web (Apache/Nginx) para aplicar as mudanças.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-line"></i> Cenários de Uso</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-music fa-3x text-primary mb-2"></i>
                                    <h5>Pequeno Volume</h5>
                                    <p class="text-muted">1-100 arquivos</p>
                                    <span class="badge bg-success">✓ Sempre Funciona</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-compact-disc fa-3x text-warning mb-2"></i>
                                    <h5>Médio Volume</h5>
                                    <p class="text-muted">100-1000 arquivos</p>
                                    <?php if ($max_files_theoretical >= 1000): ?>
                                        <span class="badge bg-success">✓ Configuração OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">⚠ Verificar Limites</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-database fa-3x text-info mb-2"></i>
                                    <h5>Grande Volume</h5>
                                    <p class="text-muted">1000+ arquivos</p>
                                    <?php if ($max_files_theoretical >= 5000): ?>
                                        <span class="badge bg-success">✓ Totalmente Suportado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✗ Ajustes Necessários</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="fas fa-tools"></i> Ações Recomendadas</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $actions_needed = [];
                        
                        if (ini_get('max_execution_time') != '0') {
                            $actions_needed[] = [
                                'priority' => 'high',
                                'action' => 'Definir max_execution_time = 0',
                                'reason' => 'Evitar timeout durante conversão de muitos arquivos'
                            ];
                        }
                        
                        if ($max_input_vars < 10000) {
                            $actions_needed[] = [
                                'priority' => 'high',
                                'action' => 'Aumentar max_input_vars para 10000',
                                'reason' => 'Permitir processamento de mais arquivos simultaneamente'
                            ];
                        }
                        
                        $memory_limit = ini_get('memory_limit');
                        $memory_mb = intval($memory_limit);
                        if (stripos($memory_limit, 'g') !== false) $memory_mb *= 1024;
                        if ($memory_mb < 512) {
                            $actions_needed[] = [
                                'priority' => 'medium',
                                'action' => 'Aumentar memory_limit para 1G',
                                'reason' => 'Garantir memória suficiente para operações em massa'
                            ];
                        }
                        
                        if (empty($actions_needed)) {
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-check-circle fa-2x float-start me-3 mt-1"></i>';
                            echo '<h5>Configuração Perfeita!</h5>';
                            echo '<p class="mb-0">Todas as configurações estão adequadas para processamento de grandes volumes de arquivos de áudio.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<i class="fas fa-exclamation-triangle fa-2x float-start me-3 mt-1"></i>';
                            echo '<h5>Ajustes Recomendados</h5>';
                            echo '<p>Para otimizar o processamento de grandes volumes:</p>';
                            echo '<ul class="mb-0">';
                            foreach ($actions_needed as $action) {
                                $badge_class = $action['priority'] == 'high' ? 'bg-danger' : 'bg-warning';
                                echo '<li><span class="badge ' . $badge_class . '">' . strtoupper($action['priority']) . '</span> ';
                                echo '<strong>' . $action['action'] . '</strong> - ' . $action['reason'] . '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Este relatório foi gerado em <?php echo date('d/m/Y H:i:s'); ?> | 
                <a href="?" class="text-decoration-none">Atualizar Análise</a>
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>