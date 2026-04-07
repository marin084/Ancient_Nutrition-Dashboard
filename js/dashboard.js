// Validación: fecha/mes inicio no puede ser mayor que fin
            document.addEventListener('DOMContentLoaded', function () {
                const inputInicio = document.getElementById('mes_inicio') || document.getElementById('fecha_inicio');
                const inputFin = document.getElementById('mes_fin') || document.getElementById('fecha_fin');
                if (inputInicio && inputFin) {
                    inputInicio.addEventListener('change', function () {
                        if (inputFin.value && this.value > inputFin.value) {
                            inputFin.value = this.value;
                        }
                    });
                    inputFin.addEventListener('change', function () {
                        if (inputInicio.value && this.value < inputInicio.value) {
                            inputInicio.value = this.value;
                        }
                    });
                }
            });

            function exportTableCSV() {
                const table = document.querySelector('.table');
                if (!table) return;

                const rows = table.querySelectorAll('tr');
                const csvLines = [];

                rows.forEach(function (row, rowIndex) {
                    const cells = row.querySelectorAll('th, td');
                    const total = cells.length;
                    const rowData = [];

                    cells.forEach(function (cell, index) {
                        // Omitir el contenido normal de la última columna (Acciones)
                        if (index === total - 1) return;

                        // Limpiar el texto: quitar saltos de línea y comillas dobles
                        let text = cell.innerText.replace(/\n/g, ' ').replace(/\r/g, '').trim();
                        // Escapar comillas dobles duplicándolas
                        text = text.replace(/"/g, '""');
                        // Envolver en comillas si tiene coma, punto y coma o comillas
                        if (text.includes(',') || text.includes(';') || text.includes('"')) {
                            text = '"' + text + '"';
                        }
                        rowData.push(text);
                    });

                    // Añadir columna del Link de Factura en lugar de Acciones
                    if (rowIndex === 0) {
                        rowData.push('"Link Factura"');
                    } else {
                        const actionCell = cells[total - 1];
                        if (actionCell && actionCell.hasAttribute('data-factura-link')) {
                            const link = actionCell.getAttribute('data-factura-link');
                            rowData.push(link ? '"' + link + '"' : '""');
                        } else {
                            rowData.push('""');
                        }
                    }

                    csvLines.push(rowData.join(','));
                });

                // BOM UTF-8 para que Excel abra correctamente caracteres especiales
                const bom = '\uFEFF';
                const csvContent = bom + csvLines.join('\n');

                // Generar nombre de archivo con el rango activo
                const startVal = document.getElementById('mes_inicio')?.value || document.getElementById('fecha_inicio')?.value || '';
                const endVal = document.getElementById('mes_fin')?.value || document.getElementById('fecha_fin')?.value || '';
                const formaPago = document.getElementById('forma_pago')?.value || '';
                const fileName = 'contabilidad' + (startVal ? '_' + startVal : '') + (endVal && endVal !== startVal ? '_a_' + endVal : '') + (formaPago ? '_' + formaPago.replace(/\s+/g, '').toLowerCase() : '') + '.csv';

                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
document.addEventListener('DOMContentLoaded', function () {
                const dropdownBtn = document.getElementById('filterDropdownBtn');
                const dropdownMenu = document.getElementById('filterDropdownMenu');
                const selectAllCheckbox = document.getElementById('select_all_estados');
                const estadoCheckboxes = document.querySelectorAll('.estado-checkbox');

                // Toggle dropdown
                if (dropdownBtn && dropdownMenu) {
                    dropdownBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        dropdownBtn.classList.toggle('active');
                        dropdownMenu.classList.toggle('active');
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function (e) {
                        if (!dropdownMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
                            dropdownBtn.classList.remove('active');
                            dropdownMenu.classList.remove('active');
                        }
                    });

                    // Prevent dropdown from closing when clicking inside menu
                    dropdownMenu.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                }

                // Update "Select All" checkbox state based on individual checkboxes
                function updateSelectAllState() {
                    const allChecked = Array.from(estadoCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(estadoCheckboxes).some(cb => cb.checked);

                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    }
                }

                if (selectAllCheckbox) {
                    // Initialize select all state on page load
                    updateSelectAllState();

                    // Select All checkbox handler
                    selectAllCheckbox.addEventListener('change', function () {
                        estadoCheckboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                }

                // Individual checkbox handler
                if (estadoCheckboxes.length > 0) {
                    estadoCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', updateSelectAllState);
                    });
                }

                // User Hamburger Menu Toggle
                const hamburgerBtn = document.getElementById('hamburgerMenuBtn');
                const userDropdown = document.getElementById('userMenuDropdown');
                
                if (hamburgerBtn && userDropdown) {
                    hamburgerBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        if (userDropdown.style.display === 'none') {
                            userDropdown.style.display = 'block';
                        } else {
                            userDropdown.style.display = 'none';
                        }
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!userDropdown.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                            userDropdown.style.display = 'none';
                        }
                    });
                }
            });

    function openModal(type, dbId, pedidoDisplayId, triggerElement) {
        // Set order ID in title
        document.getElementById(type + '-pedido-id').textContent = pedidoDisplayId;

        // Populate specific fields
        if (type === 'pago' && triggerElement) {
            document.getElementById('pago-ref').textContent = triggerElement.getAttribute('data-ref') || '--';
            document.getElementById('pago-por').textContent = triggerElement.getAttribute('data-por') || '--';
            document.getElementById('pago-update').textContent = triggerElement.getAttribute('data-update') || '--';
        } else if (type === 'guia' && triggerElement) {
            // Populate guide information
            const numeroGuia = triggerElement.getAttribute('data-guia') || '--';
            const qrCcr = triggerElement.getAttribute('data-ccr') || '';

            document.getElementById('guia-numero').textContent = numeroGuia;
            document.getElementById('guia-por').textContent = triggerElement.getAttribute('data-por') || '--';
            document.getElementById('guia-update').textContent = triggerElement.getAttribute('data-update') || '--';

            // Setup download button for guide document
            setupDownloadButton('guia-download-btn', qrCcr);

        } else if (type === 'entregado' && triggerElement) {
            const comprobante = triggerElement.getAttribute('data-comprobante') || '--';
            const htmlData = triggerElement.getAttribute('data-html') || '';
            const qrCcr = triggerElement.getAttribute('data-ccr') || '';

            // document.getElementById('entregado-comprobante').textContent = comprobante; // Element removed
            document.getElementById('entregado-por').textContent = triggerElement.getAttribute('data-por') || '--';
            document.getElementById('entregado-update').textContent = triggerElement.getAttribute('data-update') || '--';

            // Priorizar HTML si existe
            const btnId = 'entregado-download-btn';
            if (htmlData && htmlData !== '--' && htmlData !== '') {
                if (htmlData.trim().startsWith('<')) {
                    // Es contenido HTML raw -> Blob
                    const btnDownload = document.getElementById(btnId);
                    const newBtn = btnDownload.cloneNode(true);
                    btnDownload.parentNode.replaceChild(newBtn, btnDownload);

                    newBtn.style.display = 'inline-block';
                    newBtn.onclick = function (e) {
                        e.preventDefault();
                        const blob = new Blob([htmlData], { type: 'text/html' });
                        const url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    };
                } else {
                    // Es una ruta -> downloadDocument
                    setupDownloadButton(btnId, htmlData);
                }
            } else {
                // Fallback a imagen de comprobante
                setupDownloadButton(btnId, comprobante);
            }

            // Mostrar botón de guía si existe
            setupDownloadButton('entregado-ccr-download-btn', qrCcr);
        }

        // Show modal
        document.getElementById('modal-' + type).style.display = 'block';
    }

    function setupDownloadButton(btnId, path) {
        const btnDownload = document.getElementById(btnId);
        if (path !== '--' && path !== '') {
            // Clone to clear previous events
            const newBtn = btnDownload.cloneNode(true);
            btnDownload.parentNode.replaceChild(newBtn, btnDownload);

            newBtn.style.display = 'inline-block';
            newBtn.onclick = function (e) {
                e.preventDefault();
                downloadDocument(path, btnId);
            };
        } else {
            btnDownload.style.display = 'none';
        }
    }

    function closeModal(type) {
        document.getElementById('modal-' + type).style.display = 'none';
    }


    function downloadDocument(path, btnId = null) {
        // Si ya es una URL completa (http...), abrirla directamente
        if (path.startsWith('http')) {
            window.open(path, '_blank');
            return;
        }

        let btn = null;
        if (btnId) {
            btn = document.getElementById(btnId);
        }
        // Fallback por compatibilidad
        if (!btn) btn = document.getElementById('guia-download-btn');

        let originalText = '';
        if (btn) {
            originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        }

        fetch('ajax_get_signed_url.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path: path })
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else if (data.signedUrl) {
                    window.open(data.signedUrl, '_blank');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al obtener el enlace de descarga.');
            })
            .finally(() => {
                if (btn) btn.innerHTML = originalText;
            });
    }


    function validarPago() {
        alert('Funcionalidad de validar pago pendiente...');
    }

    // --- GUIDE UPLOAD FORM HANDLING ---
    document.addEventListener('DOMContentLoaded', function () {
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('file-input');
        const uploadPlaceholder = document.getElementById('upload-placeholder');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const removePreviewBtn = document.getElementById('remove-preview');
        const guiaForm = document.getElementById('guia-upload-form');
        const uploadMessage = document.getElementById('upload-message');
        const submitBtn = document.getElementById('submit-btn');

        // Click to upload
        fileUploadArea.addEventListener('click', function (e) {
            if (!e.target.closest('.remove-preview')) {
                fileInput.click();
            }
        });

        // File selected
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Show preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImage.src = e.target.result;
                        uploadPlaceholder.style.display = 'none';
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For PDFs, just show that a file is selected
                    uploadPlaceholder.innerHTML = '<div class="camera-icon">📄</div><p class="upload-text">PDF seleccionado: ' + file.name + '</p>';
                }
            }
        });

        // Remove preview
        removePreviewBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fileInput.value = '';
            uploadPlaceholder.style.display = 'block';
            previewContainer.style.display = 'none';
            uploadPlaceholder.innerHTML = '<div class="camera-icon">📷</div><p class="upload-text">Toca para tomar foto o seleccionar imagen</p><p class="upload-subtext">Se convertirá automáticamente a WebP</p>';
        });

        // Form submission
        guiaForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(guiaForm);

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            uploadMessage.style.display = 'none';

            fetch('upload_guia.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        uploadMessage.className = 'upload-message success';
                        uploadMessage.textContent = data.message || '✓ Guía registrada exitosamente';
                        uploadMessage.style.display = 'block';

                        // Close modal after 2 seconds and reload
                        setTimeout(() => {
                            closeModal('guia');
                            window.location.reload();
                        }, 2000);
                    } else {
                        uploadMessage.className = 'upload-message error';
                        uploadMessage.textContent = '✗ ' + (data.error || 'Error al cargar la guía');
                        uploadMessage.style.display = 'block';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Cargar Comprobante';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    uploadMessage.className = 'upload-message error';
                    uploadMessage.textContent = '✗ Error de conexión. Intenta nuevamente.';
                    uploadMessage.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Cargar Comprobante';
                });
        });
    });


    // Close outside click
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // --- TOAST NOTIFICATION LOGIC ---
    function showToast(title, body) {
        // Create container if not exists
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 10px;';
            document.body.appendChild(container);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.style.cssText = `
                background: white;
                border-left: 4px solid var(--primary-color);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 4px;
                padding: 16px;
                min-width: 300px;
                transform: translateX(120%);
                transition: transform 0.3s ease-out;
                display: flex;
                align-items: start;
                gap: 12px;
            `;

        toast.innerHTML = `
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 16px; color: var(--dark-text);">${title}</h4>
                    <p style="margin: 0; font-size: 14px; color: var(--text-muted);">${body}</p>
                </div>
            `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });

        // Play sound
        try {
            // Sound: Local file
            const audio = new Audio('assets/mp3/notification.mp3');
            audio.play().catch(e => console.log('Audio error', e));
        } catch (e) { }

        // Native Notification fallback/accompaniment
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, { body: body, icon: "https://monteoracion.com/favicon.ico" });
        }

        // Remove after 5s
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Auto-refresh and Formatting
    document.addEventListener('DOMContentLoaded', function () {
        // Register SW
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('./sw.js');
        }

        // 1. Auto-refresh every 5 minutes (300,000 ms) - Continuous
        setInterval(function () {
            window.location.reload();
        }, 300000);

        // 2. Notification Permissions
        if ("Notification" in window) {
            if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
        }

        // 3. Check for New Orders
        const firstRow = document.querySelector('.table tbody tr');
        if (firstRow) {
            const firstCell = firstRow.querySelector('td:first-child');
            if (firstCell) {
                const latestOrderId = parseInt(firstCell.textContent.trim(), 10);
                const storageKey = 'lastKnownOrderId_' + currentUserId;
                const lastKnownOrder = parseInt(localStorage.getItem(storageKey), 10);

                if (!lastKnownOrder) {
                    // First visit or cleared cache: just store the current latest
                    localStorage.setItem(storageKey, latestOrderId);
                } else if (latestOrderId > lastKnownOrder) {
                    // Trigger Toast ONLY if we have a strictly newer order
                    showToast("¡Nuevo Pedido Recibido!", "Pedido #" + latestOrderId);

                    // Update storage
                    localStorage.setItem(storageKey, latestOrderId);
                }
                // If latestOrderId < lastKnownOrder (e.g. filter active), do nothing.
                // If latestOrderId == lastKnownOrder, do nothing.
            }
        }
    });