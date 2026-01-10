// Reads equipment data from DOM and provides filterEquipment() used by admin_intervention_new.php
(function(){
    function getEquipmentData() {
        const el = document.getElementById('equipment-data');
        if (!el) return [];
        try {
            const json = el.getAttribute('data-json');
            return JSON.parse(json || '[]');
        } catch (e) {
            console.error('Failed to parse equipment JSON', e);
            return [];
        }
    }

    const allEquipment = getEquipmentData();

    window.filterEquipment = function() {
        const clientIdEl = document.getElementById('clientSelect');
        const equipmentSelect = document.getElementById('equipmentSelect');
        if(!clientIdEl || !equipmentSelect) return;

        const clientId = clientIdEl.value;
        equipmentSelect.innerHTML = '<option value="">-- Alege Echipament --</option>';

        if (clientId) {
            const filtered = allEquipment.filter(item => item.client_id == clientId);
            if (filtered.length > 0) {
                filtered.forEach(eq => {
                    const option = document.createElement('option');
                    option.value = eq.id;
                    option.textContent = eq.model + ' (' + eq.serial_number + ')';
                    equipmentSelect.appendChild(option);
                });
            } else {
                equipmentSelect.innerHTML = '<option value="">Clientul nu are echipamente înregistrate</option>';
            }
        }
    };

})();
