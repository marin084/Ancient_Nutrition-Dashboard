<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Pedidos</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="https://monteoracion.com/logo-192.png">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<div class="container">
    <header class="header">
        <div>
            <h1>Pedidos Ancient Nutrition</h1>
            <p>Bienvenido, <strong>
                    <?php echo htmlspecialchars($user_name); ?>
                </strong></p>
        </div>
        <div class="user-menu-container" style="position: relative;">
            <button class="hamburger-btn" id="hamburgerMenuBtn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--dark-text); padding: 5px 10px;">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-menu-dropdown" id="userMenuDropdown" style="display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 220px; z-index: 100; margin-top: 8px;">
                <a href="change_password.php" style="display: block; padding: 12px 16px; color: var(--dark-text); text-decoration: none; border-bottom: 1px solid #e5e7eb;"><i class="fas fa-key" style="width: 24px; color: var(--info-color);"></i> Cambiar Contraseña</a>
                <a href="logout.php" style="display: block; padding: 12px 16px; color: var(--danger-color); text-decoration: none;"><i class="fas fa-sign-out-alt" style="width: 24px;"></i> Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Filtro de Rango de Meses (todos los roles) -->
    
    <?php if ($permissions->canViewFilters()): ?>
    <div class="dashboard-filters-wrapper"
        style="display: flex; gap: 15px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 20px; padding: 14px 18px; background: white; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
        <form method="GET" action="dashboard.php" id="monthFilterForm">
            <!-- Preservar el filtro de estado si existe -->
            <?php if (!empty($_GET['filtro_estado']) && is_array($_GET['filtro_estado'])): ?>
            <?php foreach ($_GET['filtro_estado'] as $fe): ?>
            <input type="hidden" name="filtro_estado[]" value="<?php echo htmlspecialchars($fe); ?>">
            <?php
        endforeach; ?>
            <?php
    endif; ?>
            <div class="month-filter-bar">
                <span class="month-filter-label-prefix">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    Período:
                </span>
                <?php if ($permissions->canFilterByExactDate()): ?>
                <label for="fecha_inicio">Desde</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio"
                    value="<?php echo htmlspecialchars($fecha_inicio); ?>" max="<?php echo $now->format('Y-m-d'); ?>"
                    style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f9fafb;">
                <span class="month-sep">→</span>
                <label for="fecha_fin">Hasta</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>"
                    max="<?php echo $now->format('Y-m-d'); ?>"
                    style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f9fafb;">
                <span class="month-sep" style="margin-left: 10px;">|</span>
                <label for="forma_pago" style="margin-left: 5px;">Método de Pago:</label>
                <select id="forma_pago" name="forma_pago" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f9fafb; min-width: 150px;">
                    <option value="">Todos</option>
                    <?php foreach ($metodos_pago_disponibles as $metodo): ?>
                    <option value="<?php echo htmlspecialchars($metodo); ?>" <?php echo ($forma_pago === $metodo) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($metodo); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php
    else: ?>
                <label for="mes_inicio">Desde</label>
                <input type="month" id="mes_inicio" name="mes_inicio"
                    value="<?php echo htmlspecialchars($mes_inicio ?? ''); ?>"
                    max="<?php echo $default_mes_fin ?? ''; ?>">
                <span class="month-sep">→</span>
                <label for="mes_fin">Hasta</label>
                <input type="month" id="mes_fin" name="mes_fin" value="<?php echo htmlspecialchars($mes_fin ?? ''); ?>"
                    max="<?php echo $default_mes_fin ?? ''; ?>">
                <?php
    endif; ?>
                <button type="submit" class="month-apply-btn">
                    <i class="fas fa-check" style="color: white;"></i>
                    Aplicar
                </button>
                <?php
    if ($permissions->canFilterByExactDate()) {
        $mostrar_limpiar = ($fecha_inicio !== $default_fecha_inicio || $fecha_fin !== $default_fecha_fin || !empty($forma_pago));
    }
    else {
        $mostrar_limpiar = (($mes_inicio ?? '') !== ($default_mes_inicio ?? '') || ($mes_fin ?? '') !== ($default_mes_fin ?? ''));
    }
    if ($mostrar_limpiar):
?>
                <?php
        $clear_mes_url = 'dashboard.php';
        if (!empty($_GET['filtro_estado']) && is_array($_GET['filtro_estado'])) {
            $q_estados = [];
            foreach ($_GET['filtro_estado'] as $fe) {
                $q_estados[] = 'filtro_estado[]=' . urlencode($fe);
            }
            $clear_mes_url .= '?' . implode('&', $q_estados);
        }
?>
                <a href="<?php echo htmlspecialchars($clear_mes_url); ?>"
                    style="font-size:13px; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Limpiar
                </a>
                <?php
    endif; ?>
                <?php if ($is_contabilidad): ?>
                <button type="button" onclick="exportTableCSV()"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#059669;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.2s;"
                    onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    Exportar CSV
                </button>
                <?php
    endif; ?>
            </div>
        </form>
        

        <!-- Filtros (Solo para Administrador) -->
        <?php if ($permissions->isAdmin()): ?>

        

        <div style="margin-bottom: 0;">
            <form method="GET" action="dashboard.php" id="filterForm">
                <!-- Preservar el rango al filtrar por estado -->
                <?php if ($permissions->canFilterByExactDate()): ?>
                <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                <input type="hidden" name="forma_pago" value="<?php echo htmlspecialchars($forma_pago ?? ''); ?>">
                <?php
        else: ?>
                <input type="hidden" name="mes_inicio" value="<?php echo htmlspecialchars($mes_inicio ?? ''); ?>">
                <input type="hidden" name="mes_fin" value="<?php echo htmlspecialchars($mes_fin ?? ''); ?>">
                <?php
        endif; ?>
                <div class="filter-dropdown-container">

                    <button type="button" class="filter-dropdown-button" id="filterDropdownBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <span>Filtrar por Estado</span>
                        <?php
        $selected_count = isset($_GET['filtro_estado']) && is_array($_GET['filtro_estado']) ? count($_GET['filtro_estado']) : 0;
        if ($selected_count > 0):
?>
                        <span class="selected-count">
                            <?php echo $selected_count; ?>
                        </span>
                        <?php
        endif; ?>
                        <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <div class="filter-dropdown-menu" id="filterDropdownMenu">
                        <div class="filter-dropdown-header">Seleccionar Estados</div>

                        <!-- Scrollable Items Container -->
                        <div class="filter-dropdown-items">
                            <!-- Select All -->
                            <label class="filter-dropdown-item select-all">
                                <input type="checkbox" id="select_all_estados">
                                <div class="filter-checkbox-custom">
                                    <svg viewBox="0 0 12 12">
                                        <polyline points="2 6 5 9 10 3"></polyline>
                                    </svg>
                                </div>
                                <span class="filter-label-text">Todos los estados</span>
                            </label>

                            <!-- Individual Estados -->
                            <?php foreach ($estados_posibles as $estado): ?>
                            <?php
            $is_checked = isset($_GET['filtro_estado']) && is_array($_GET['filtro_estado']) && in_array($estado, $_GET['filtro_estado']);
?>
                            <label class="filter-dropdown-item">
                                <input type="checkbox" name="filtro_estado[]"
                                    value="<?php echo htmlspecialchars($estado); ?>" class="estado-checkbox" <?php echo
                                    $is_checked ? 'checked' : '' ; ?>>
                                <div class="filter-checkbox-custom">
                                    <svg viewBox="0 0 12 12">
                                        <polyline points="2 6 5 9 10 3"></polyline>
                                    </svg>
                                </div>
                                <span class="filter-label-text">
                                    <?php echo htmlspecialchars($estado); ?>
                                </span>
                            </label>
                            <?php
        endforeach; ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="filter-actions">
                            <button type="submit" class="filter-apply-btn">
                                <i class="fas fa-check"></i> Aplicar
                            </button>
                            <?php
        $is_default_admin = ($permissions->isAdmin() && isset($_GET['filtro_estado']) && is_array($_GET['filtro_estado']) &&
            count(array_diff($_GET['filtro_estado'], $estados_default_admin)) === 0 &&
            count(array_diff($estados_default_admin, $_GET['filtro_estado'])) === 0);

        if (!empty($_GET['filtro_estado']) && !$is_default_admin):
?>
                            <?php
            $clear_estado_url = 'dashboard.php';
            $p_meses = [];
            if (isset($_GET['mes_inicio'])) {
                $p_meses[] = 'mes_inicio=' . urlencode($_GET['mes_inicio']);
            }
            if (isset($_GET['mes_fin'])) {
                $p_meses[] = 'mes_fin=' . urlencode($_GET['mes_fin']);
            }
            if (isset($_GET['fecha_inicio'])) {
                $p_meses[] = 'fecha_inicio=' . urlencode($_GET['fecha_inicio']);
            }
            if (isset($_GET['fecha_fin'])) {
                $p_meses[] = 'fecha_fin=' . urlencode($_GET['fecha_fin']);
            }
            if (isset($_GET['forma_pago']) && $_GET['forma_pago'] !== '') {
                $p_meses[] = 'forma_pago=' . urlencode($_GET['forma_pago']);
            }
            if (!empty($p_meses)) {
                $clear_estado_url .= '?' . implode('&', $p_meses);
            }
?>
                            <button type="button" class="filter-clear-btn"
                                onclick="window.location.href='<?php echo htmlspecialchars($clear_estado_url); ?>'">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                            <?php
        endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- JavaScript for Dropdown and Select All functionality -->
        
        <?php
    endif; ?>
    </div>
    <?php
endif; ?>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <?php if ($is_contabilidad): ?>
                    <th>Fecha</th>
                    <th>Pedido</th>
                    <th>Nombre</th>
                    <th>Forma de Pago</th>
                    <th>Total</th>
                    <th>Referencia Pago</th>
                    <th>No. Factura</th>
                    <th>Acciones</th>
                    <?php
else: ?>
                    <th>Pedido</th>
                    <th>Nombre</th>
                    <?php if ($permissions->showCedulaColumn()): ?>
                    <th>Cédula</th>
                    <?php
    endif; ?>
                    <th>Estado</th>
                    <th>Fecha Pedido</th>
                    <?php if ($permissions->showPaymentMethodColumn()): ?>
                    <th>Forma de Pago</th>
                    <?php
    endif; ?>
                    <th>Teléfono</th>
                    <?php if ($permissions->showEmailAndDeliveryColumns()): ?>
                    <th>Correo Electrónico</th>
                    <th>Tipo de Entrega</th>
                    <th>Productos</th>
                    <?php
    endif; ?>
                    <th>Total</th>
                    <?php if ($permissions->showPaymentReferenceColumn()): ?>
                    <th>Referencia Pago</th>
                    <?php endif; ?>
                    <?php if ($permissions->showInvoiceNumberColumn()): ?>
                    <th>No. Factura</th>
                    <?php endif; ?>
                    <?php if ($permissions->showAddressColumn()): ?>
                    <th class="col-direccion">Dirección</th>
                    <?php
    endif; ?>
                    <?php if ($permissions->showActionsColumn()): ?>
                    <th>Acciones</th>
                    <?php
    endif; ?>
                    <?php
endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pedidos) && is_array($pedidos)): ?>
                <?php foreach ($pedidos as $pedido): ?>
                <?php
        // Determinar clase del badge
        $estado_class = 'badge-secondary'; // Default
        $estado_texto = strtoupper($pedido['estado'] ?? '');

        if (strpos($estado_texto, 'PENDIENTE') !== false) {
            $estado_class = 'badge-pendiente';
        }
        elseif (strpos($estado_texto, 'ENTREGADO') !== false) {
            $estado_class = 'badge-entregado';
        }
        elseif (strpos($estado_texto, 'GUIA') !== false) {
            $estado_class = 'badge-pendiente-guia';
        }
        elseif (strpos($estado_texto, 'EMPACAR') !== false) {
            $estado_class = 'badge-listo-empacar';
        }
        elseif (strpos($estado_texto, 'FINALIZADO') !== false) {
            $estado_class = 'badge-finalizado';
        }
        elseif (strpos($estado_texto, 'CANCELADO') !== false) {
            $estado_class = 'badge-cancelado';
        }
?>
                <tr>
                    <?php if ($is_contabilidad): ?>
                    <td>
                        <?php echo htmlspecialchars(substr(format_date_cr($pedido['fecha_pedido'] ?? '--'), 0, 10)); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['pedido'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['nombre'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['forma_de_pago'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo '₡' . number_format($pedido['total'] ?? 0, 2); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['referencia_pago'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['numero_factura'] ?? '--'); ?>
                    </td>
                    <?php
            $factura_link = '';
            if (!empty($pedido['factura_url'])) {
                $fp = $pedido['factura_url'];
                if (str_starts_with($fp, 'http')) {
                    $factura_link = $fp;
                }
                else {
                    if (!str_starts_with($fp, 'comprobantes/')) {
                        $fp = 'comprobantes/' . ltrim($fp, '/');
                    }
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/');
                    $factura_link = $baseUrl . '/download_file.php?path=' . urlencode($fp);
                }
            }
?>
                    <td data-factura-link="<?php echo htmlspecialchars($factura_link); ?>">
                        <?php if (!empty($pedido['factura_url'])): ?>
                        <?php
                $factura_path = $pedido['factura_url'];
                // Si no es URL completa y no tiene el bucket como prefijo, añadirlo
                if (!str_starts_with($factura_path, 'http') && !str_starts_with($factura_path, 'comprobantes/')) {
                    $factura_path = 'comprobantes/' . ltrim($factura_path, '/');
                }
?>
                        <button
                            onclick="downloadDocument('<?php echo htmlspecialchars($factura_path); ?>', 'factura-btn-<?php echo $pedido['id']; ?>')"
                            id="factura-btn-<?php echo $pedido['id']; ?>" class="btn btn-sm" title="Descargar Factura"
                            style="background-color: #7c3aed; color: #fff; padding: 5px 10px;">
                            <i class="fas fa-file-pdf"></i> Factura
                        </button>
                        <?php
            else: ?>
                        <span style="color: #9ca3af; font-size: 13px;">--</span>
                        <?php
            endif; ?>
                    </td>
                    <?php
        else: ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['pedido'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['nombre'] ?? '--'); ?>
                    </td>
                    <?php if ($user_role_id == 1): ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['cedula'] ?? '--'); ?>
                    </td>
                    <?php
            endif; ?>
                    <td><span class="badge <?php echo $estado_class; ?>">
                            <?php echo htmlspecialchars($pedido['estado'] ?? '--'); ?>
                        </span></td>
                    <td>
                        <?php echo htmlspecialchars(format_date_cr($pedido['fecha_pedido'] ?? '--')); ?>
                    </td>
                    <?php if ($user_role_id != 3): ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['forma_de_pago'] ?? '--'); ?>
                    </td>
                    <?php
            endif; ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['telefono'] ?? '--'); ?>
                    </td>
                    <?php if ($user_role_id != 2): ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['correo_electronico'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($pedido['tipo_de_entrega'] ?? '--'); ?>
                    </td>
                    <td>
                        <?php echo str_replace([',', '-'], '<br>', htmlspecialchars($pedido['productos'] ?? '--')); ?>
                    </td>
                    <?php
            endif; ?>
                    <td>
                        <?php echo '₡' . number_format($pedido['total'] ?? 0, 2); ?>
                    </td>
                    <?php if ($permissions->showPaymentReferenceColumn()): ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['referencia_pago'] ?? '--'); ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($permissions->showInvoiceNumberColumn()): ?>
                    <td>
                        <?php echo htmlspecialchars($pedido['numero_factura'] ?? '--'); ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($permissions->showAddressColumn()): ?>
                    <td class="col-direccion">
                        <?php echo htmlspecialchars($pedido['direccion'] ?? '--'); ?>
                    </td>
                    <?php
            endif; ?>
                    <?php if ($permissions->showActionsColumn()): ?>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <?php if ($permissions->canValidatePayment($pedido['estado'] ?? '')): ?>
                            <a href="verificar_pago.php?pedido=<?php echo urlencode($pedido['pedido']); ?>" class="btn btn-sm" title="Validar Pago" style="background-color: var(--primary-color); color: #fff; padding: 5px 8px;">Validar Pago</a>
                            <?php endif; ?>

                            <?php if ($permissions->canViewPaymentDetails() && !empty($pedido['referencia_pago'])): ?>
                            <button onclick="openModal('pago', '<?php echo $pedido['id']; ?>', '<?php echo $pedido['pedido']; ?>', this)" data-ref="<?php echo htmlspecialchars($pedido['referencia_pago'] ?? ''); ?>" data-por="<?php echo htmlspecialchars($pedido['referencia_pago_por'] ?? ''); ?>" data-update="<?php echo htmlspecialchars(format_date_cr($pedido['referencia_pago_update'] ?? '')); ?>" class="btn btn-sm" title="Ver Pago" style="background-color: var(--warning-color); color: #000; padding: 5px 8px;"><i class="fas fa-money-bill-wave"></i></button>
                            <?php endif; ?>

                            <?php if ($permissions->canAddGuide($pedido['estado'] ?? '', !empty($pedido['qr_ccr']))): ?>
                            <a href="agregar_guia.php?pedido=<?php echo urlencode($pedido['pedido']); ?>" class="btn btn-sm" title="Agregar Guía" style="background-color: var(--info-color); color: #000; padding: 5px 8px;">Agregar Guía</a>
                            <?php endif; ?>

                            <?php if ($permissions->canViewGuide(!empty($pedido['qr_ccr']))): ?>
                            <button onclick="openModal('guia', '<?php echo $pedido['id']; ?>', '<?php echo $pedido['pedido']; ?>', this)" data-ccr="<?php echo htmlspecialchars($pedido['qr_ccr'] ?? ''); ?>" data-guia="<?php echo htmlspecialchars($pedido['guia'] ?? ''); ?>" data-por="<?php echo htmlspecialchars($pedido['qr_ccr_por'] ?? ''); ?>" data-update="<?php echo htmlspecialchars(format_date_cr($pedido['qr_ccr_update'] ?? '')); ?>" class="btn btn-sm" title="<?php echo $permissions->isAdmin() ? 'Datos Guía' : 'Ver Guía'; ?>" style="background-color: var(--info-color); color: #000; padding: 5px 8px;"><i class="fas fa-truck"></i></button>
                            <?php endif; ?>

                            <?php if ($permissions->canUploadDeliveryProof()): ?>
                            <a href="comprobante_entrega.php?pedido=<?php echo urlencode($pedido['pedido']); ?>" class="btn btn-sm" title="Cargar Comprobante" style="background-color: var(--primary-color); color: #fff; padding: 5px 8px;"><i class="fas fa-camera"></i></a>
                            <?php endif; ?>

                            <?php if ($permissions->canSignDelivery($pedido['estado'] ?? '', $pedido['tipo_de_entrega'] ?? '')): ?>
                            <a href="firmar_entrega.php?pedido=<?php echo urlencode($pedido['pedido']); ?>" class="btn btn-sm" title="Firmar Entrega" style="background-color: var(--success-color); color: #fff; padding: 5px 8px;"><i class="fas fa-file-signature"></i></a>
                            <?php endif; ?>

                            <?php if ($permissions->canConfirmDelivery($pedido['estado'] ?? '', $pedido['tipo_de_entrega'] ?? '')): ?>
                            <a href="https://monteoracion.com/an/comprobante-entrega.html?pedido=<?php echo urlencode($pedido['pedido']); ?>&email=<?php echo $user_email; ?>" target="_blank" class="btn btn-sm" title="Confirmar Entrega" style="background-color: var(--success-color); color: #fff; padding: 5px 8px;">Confirmar Entrega</a>
                            <?php endif; ?>

                            <?php if ($permissions->canValidateDeliveryPayment($pedido['estado'] ?? '', $pedido['forma_de_pago'] ?? '')): ?>
                            <a href="verificar_pago_entregado.php?pedido=<?php echo urlencode($pedido['pedido']); ?>" class="btn btn-sm" title="Validar Pago" style="background-color: var(--primary-color); color: #fff; padding: 5px 8px;">Validar Pago</a>
                            <?php endif; ?>

                            <?php if ($permissions->canViewDeliveryDetails() && !empty($pedido['entregado_cliente_comprobante'])): ?>
                            <button onclick="openModal('entregado', '<?php echo $pedido['id']; ?>', '<?php echo $pedido['pedido']; ?>', this)" data-comprobante="<?php echo htmlspecialchars($pedido['entregado_cliente_comprobante'] ?? ''); ?>" data-html="<?php echo htmlspecialchars($pedido['html_hoja_entrega'] ?? ''); ?>" data-por="<?php echo htmlspecialchars($pedido['entregado_cliente_por'] ?? ''); ?>" data-update="<?php echo htmlspecialchars(format_date_cr($pedido['entregado_cliente_updated'] ?? '')); ?>" data-ccr="<?php echo htmlspecialchars($pedido['qr_ccr'] ?? ''); ?>" class="btn btn-sm" title="Datos Entrega" style="background-color: var(--success-color); color: #fff; padding: 5px 8px;"><i class="fas fa-check-circle"></i></button>
                            <?php endif; ?>

                            <?php if ($permissions->canEnterInvoice($pedido['estado'] ?? '', !empty($pedido['numero_factura']))): ?>
                            <a href="ingresar_factura.php?pedido=<?php echo urlencode($pedido['pedido']); ?>" class="btn btn-sm" title="Ingresar Factura" style="background-color: var(--success-color); color: #fff; padding: 5px 8px;">Ingresar Factura</a>
                            <?php endif; ?>

                            <?php if ($permissions->canViewInvoiceButton() && !empty($pedido['factura_url'])): ?>
                            <?php
                                $inv_path = $pedido['factura_url'];
                                if (!str_starts_with($inv_path, 'http') && !str_starts_with($inv_path, 'comprobantes/')) {
                                    $inv_path = 'comprobantes/' . ltrim($inv_path, '/');
                                }
                            ?>
                            <button
                                onclick="downloadDocument('<?php echo htmlspecialchars($inv_path); ?>', 'factura-btn-<?php echo $pedido['id']; ?>')"
                                id="factura-btn-<?php echo $pedido['id']; ?>" class="btn btn-sm" title="Ver Factura"
                                style="background-color: #7c3aed; color: #fff; padding: 5px 8px;">
                                <i class="fas fa-file-pdf"></i> Factura
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <?php
    $colspan = 11; // Default basic columns
    if ($permissions->showActionsColumn()) $colspan++;
    if (!$permissions->showPaymentMethodColumn()) $colspan--;
    if (!$permissions->showEmailAndDeliveryColumns()) $colspan -= 3;
    if (!$permissions->showAddressColumn()) $colspan--;
    if ($permissions->showCedulaColumn()) $colspan++;
    if ($permissions->showPaymentReferenceColumn()) $colspan++;
    if ($permissions->showInvoiceNumberColumn()) $colspan++;
?>
                    <td colspan="<?php echo $is_contabilidad ? 5 : $colspan; ?>" style="text-align: center;">No hay
                        pedidos para mostrar en esta categoría.</td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<!-- Modal Pago -->
<div id="modal-pago" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('pago')">&times;</span>
        <div class="modal-header">
            <h2>Detalle Pago - Pedido <span id="pago-pedido-id"></span></h2>
        </div>
        <div class="modal-body">
            <p><strong>Referencia:</strong> <span id="pago-ref"></span></p>
            <p><strong>Por:</strong> <span id="pago-por"></span></p>
            <p><strong>Actualización:</strong> <span id="pago-update"></span></p>
        </div>
    </div>
</div>

<!-- Modal Guia - Vista de Detalles -->
<div id="modal-guia" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('guia')">&times;</span>
        <div class="modal-header">
            <h2>Detalle Guía - Pedido <span id="guia-pedido-id"></span></h2>
        </div>
        <div class="modal-body">
            <p><strong>Número de Guía:</strong> <span id="guia-numero"></span></p>
            <p><strong>Por:</strong> <span id="guia-por"></span></p>
            <p><strong>Actualización:</strong> <span id="guia-update"></span></p>
            <hr>
            <div style="display: flex; gap: 10px;">
                <a id="guia-download-btn" href="#" target="_blank" class="btn btn-primary"
                    style="background-color: var(--info-color); border-color: var(--info-color); color: #000; display: none; flex: 1; text-align: center;">
                    Descargar Guía <i class="fas fa-truck"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Entregado -->
<div id="modal-entregado" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('entregado')">&times;</span>
        <div class="modal-header">
            <h2>Detalle Entrega - Pedido <span id="entregado-pedido-id"></span></h2>
        </div>
        <div class="modal-body">
            <p><strong>Por:</strong> <span id="entregado-por"></span></p>
            <p><strong>Actualización:</strong> <span id="entregado-update"></span></p>
            <hr>
            <div style="display: flex; gap: 10px;">
                <a id="entregado-download-btn" href="#" target="_blank" class="btn btn-primary"
                    style="background-color: var(--success-color); border-color: var(--success-color); color: #fff; display: none; flex: 1; text-align: center;">
                    Descargar Comprobante <i class="fas fa-external-link-alt"></i>
                </a>

                <!-- Sección opcional para guía en entregado -->
                <a id="entregado-ccr-download-btn" href="#" target="_blank" class="btn btn-secondary"
                    style="background-color: var(--info-color); border-color: var(--info-color); color: #000; display: none; flex: 1; text-align: center;">
                    Descargar Guía <i class="fas fa-truck"></i>
                </a>
            </div>
        </div>
    </div>
</div>


<script>
    const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? 'guest'); ?>;
</script>
<script src="js/dashboard.js"></script>
</body>

</html>