<?php

class Permissions {
    private $roleId;

    public function __construct($roleId) {
        $this->roleId = (int) $roleId;
    }

    // General
    public function isAdmin() {
        return $this->roleId === 1;
    }

    // Filtros
    public function canViewFilters() {
        return !in_array($this->roleId, [4, 8]);
    }

    public function canFilterByExactDate() {
        return in_array($this->roleId, [1, 2, 6]);
    }

    public function canFilterByPaymentMethod() {
        return in_array($this->roleId, [1, 2, 6]);
    }

    // Exportación CSV (Contabilidad)
    public function isContabilidad() {
        return $this->roleId === 6;
    }

    // Columnas de la tabla
    public function showStatusColumn() { return true; }
    
    public function showCedulaColumn() {
        return $this->roleId === 1;
    }

    public function showPaymentMethodColumn() {
        return $this->roleId !== 3;
    }

    public function showEmailAndDeliveryColumns() {
        return $this->roleId !== 2;
    }

    public function showPaymentReferenceColumn() {
        return in_array($this->roleId, [1, 2, 6]);
    }

    public function showInvoiceNumberColumn() {
        return in_array($this->roleId, [1, 2, 6]);
    }

    public function canViewInvoiceButton() {
        return in_array($this->roleId, [1, 2, 5, 6]);
    }

    public function showAddressColumn() {
        return !in_array($this->roleId, [2, 4, 8]);
    }

    public function showActionsColumn() {
        return in_array($this->roleId, [1, 2, 3, 4, 5, 8]);
    }

    // Acciones de Botones (Lógica copiada de dashboard_view.php)
    
    public function canValidatePayment($estado) {
        $estado = strtoupper($estado);
        if (strpos($estado, 'PENDIENTE DE PAGO') !== false) {
            return in_array($this->roleId, [1, 2]);
        }
        return false;
    }

    public function canValidateDeliveryPayment($estado, $forma_pago) {
        // Para Datafono entregado (Delivery - 4)
        $estado = strtoupper($estado);
        $es_entregado = strpos($estado, 'ENTREGADO') !== false;
        $forma_pego_str = strtoupper($forma_pago);
        $es_datafono = (strpos($forma_pego_str, 'DATÁFONO') !== false || strpos($forma_pego_str, 'DATAFONO') !== false);
        
        return $this->roleId === 4 && $es_entregado && $es_datafono;
    }

    public function canViewPaymentDetails() {
        return $this->roleId === 1;
    }

    public function canAddGuide($estado, $hasGuide) {
        $estado = strtoupper($estado);
        $es_pendiente_guia = strpos($estado, 'PENDIENTE DE GUIA') !== false;
        if ($this->roleId === 1 || $this->roleId === 3) {
            return $es_pendiente_guia && !$hasGuide;
        }
        return false;
    }

    public function canViewGuide($hasGuide) {
        // Role 3 y Role 8 y Role 1 ven la guia si existe
        if (in_array($this->roleId, [1, 3, 8])) {
            return $hasGuide;
        }
        return false;
    }

    public function canUploadDeliveryProof() {
        // En Correos (8) es el boton de camara
        return $this->roleId === 8;
    }

    public function canSignDelivery($estado, $tipo_entrega) {
        $estado = strtoupper($estado);
        $tipo_entrega = strtoupper($tipo_entrega);
        $es_retiro = strpos($tipo_entrega, 'CANAL') !== false;
        $estado_listo = strpos($estado, 'LISTO PARA EMPACAR') !== false;

        if (in_array($this->roleId, [1, 4])) {
            return $es_retiro && $estado_listo;
        }
        return false;
    }

    public function canConfirmDelivery($estado, $tipo_entrega) {
        // Role 4 pero que NO sea retiro en canal y no este entregado
        $estado = strtoupper($estado);
        $tipo_entrega = strtoupper($tipo_entrega);
        $es_retiro = strpos($tipo_entrega, 'CANAL') !== false;
        $es_entregado = strpos($estado, 'ENTREGADO') !== false;

        if ($this->roleId === 4) {
            return !$es_retiro && !$es_entregado;
        }
        return false;
    }

    public function canViewDeliveryDetails() {
        return $this->roleId === 1;
    }

    public function canEnterInvoice($estado, $has_factura) {
        // Role 1 (y logicamente 5, Invoices, que podriamos incluir)
        $estado = strtoupper($estado);
        $es_entregado = strpos($estado, 'ENTREGADO') !== false;
        
        if (in_array($this->roleId, [1, 5])) {
            return !$has_factura && $es_entregado;
        }
        return false;
    }
}
