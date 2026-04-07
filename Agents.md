# DETALLES DEL SISTEMA 
Este documento describe los detalles del sistema de gestión de pedidos.

## Roles del Sistema Dashboard
Aquí se definen los roles de usuario y sus responsabilidades dentro del sistema de gestión de pedidos. 
Estos roles determinan los estados de los pedidos que cada usuario puede ver y las acciones que puede realizar.

### Mapeo de Roles (id_rol)

#### 1. Administrador (Admin)
- **ID:** 1
- **Responsabilidad:** Acceso total al sistema.
- **Vista por defecto:** Todos los estados excepto `FINALIZADO`.
- **Acciones:** Puede realizar cualquier acción en el sistema, incluyendo gestión de usuarios y cambio de estados.

#### 2. Validador de pagos (Validator)
- **ID:** 2
- **Responsabilidad:** Verificar que los pagos se hayan realizado correctamente.
- **Estado principal:** `PENDIENTE DE PAGO`.
- **Acciones:** Validar comprobantes de pago para mover pedidos al siguiente estado.

#### 3. Creador de guías (Guide Creator)
- **ID:** 3
- **Responsabilidad:** Generar guías de envío para los pedidos listos.
- **Estado principal:** `PENDIENTE DE GUIA`.
- **Acciones:** Subir guías de transporte (PDF/Imagen) para pedidos validados.

#### 4. Entrega de paquetes (Delivery)
- **ID:** 4
- **Responsabilidad:** Gestionar entregas físicas en canales de retiro.
- **Estados principales:** 
  - `LISTO PARA EMPACAR` (cuando el `tipo_de_entrega` es `RETIRO EN CANAL*`).
  - `ENTREGADO` (para registrar pagos con datáfono sin referencia).
- **Acciones:** Firmar entregas, subir comprobantes de entrega.

#### 5. Ingreso de facturas (Invoices)
- **ID:** 5
- **Responsabilidad:** Procesar la facturación de los pedidos entregados.
- **Estado principal:** `ENTREGADO`.
- **Acciones:** Subir facturas del sistema contable al pedido.

#### 6. Contabilidad (Accounting)
- **ID:** 6
- **Responsabilidad:** Cierre de pedidos y auditoría.
- **Estado principal:** `FINALIZADO`.
- **Acciones:** Revisión final de transacciones.

#### 7. Callcenter
- **ID:** 7
- **Responsabilidad:** Atención al cliente y seguimiento.
- **Vista por defecto:** Cualquier pedido cuyo estado no sea `FINALIZADO`.

#### 8. Correos de Costa Rica
- **ID:** 8
- **Responsabilidad:** Operador logístico externo.
- **Estado principal:** `LISTO PARA EMPACAR` (cuando el `tipo_de_entrega` empieza con `ENVIO CORREOS`).
- **Acciones:** Visualizar pedidos listos para recolección por el operador.

---

### Flujo de Estados Típico
1. `PENDIENTE DE PAGO` -> (Validador) -> `PENDIENTE DE GUIA`
2. `PENDIENTE DE GUIA` -> (Creador Guías) -> `LISTO PARA EMPACAR`
3. `LISTO PARA EMPACAR` -> (Entrega/Correos) -> `ENTREGADO`
4. `ENTREGADO` -> (Facturación) -> `FINALIZADO`

---

### Arquitectura de Permisos (MVC)
El sistema del Dashboard utiliza una separación de responsabilidades:
- **Controlador (`dashboard.php`)**: Gestiona la autenticación, filtros base de rol, consulta a la base de datos e inicialización de Permisos.
- **Clase `Permissions.php` (`includes/Permissions.php`)**: Centraliza la lógica de quién puede ver qué. Elimina los chequeos directos por `$user_role_id` dispersos. Expone métodos como `canValidatePayment($estado)`, `showActionsColumn()`. Todas las nuevas validaciones de visibilidad deben implementarse aquí.
- **Vista (`dashboard_view.php`)**: Exclusivamente dibuja la interfaz de usuario en HTML llamando a los métodos públicos del objeto `$permissions`.
- **Assets (`css/style.css`, `js/dashboard.js`)**: Hojas de estilo y funcionalidad de cliente separados para un mantenimiento limpio.
