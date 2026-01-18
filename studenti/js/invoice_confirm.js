(function(){
    const container = document.getElementById('items-container');
    const addBtn = document.getElementById('add-item');
    const partsInput = document.getElementById('parts_amount');
    const laborInput = document.getElementById('labor_amount');
    const detailsInput = document.getElementById('details');
    const itemsJson = document.getElementById('items_json');

    if (!container) return; // safety

    function createRow(desc='', amt='', isLabor=false){
        const row = document.createElement('div');
        row.className = 'items-row';
        row.innerHTML = '<input type="text" class="item-desc" placeholder="Descriere" value="'+escapeAttr(desc)+'">'
            + '<input type="number" step="0.01" class="item-amt" placeholder="Sumă" value="'+(amt!==undefined?amt:'')+'">'
            + '<label style="display:flex; align-items:center; gap:6px;"><input type="checkbox" class="item-labor" '+(isLabor? 'checked':'')+'>Manoperă</label>'
            + '<button type="button" class="item-del btn btn-secondary">Șterge</button>';
        container.appendChild(row);
        row.querySelector('.item-del').addEventListener('click', function(){ row.remove(); computeSums(); });
        row.querySelector('.item-amt').addEventListener('input', computeSums);
        row.querySelector('.item-labor').addEventListener('change', computeSums);
        row.querySelector('.item-desc').addEventListener('input', updateDetails);
        return row;
    }

    function escapeAttr(s){ return (s+'').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function computeSums(){
        let items = Array.from(container.querySelectorAll('.items-row'));
        let parts = 0, labor = 0, itemsArr = [];

        if (items.length === 0) {
            // No item rows: preserve manual values entered by user
            parts = parseFloat(partsInput.value) || 0;
            labor = parseFloat(laborInput.value) || 0;
        } else {
            items.forEach(r=>{
                const d = r.querySelector('.item-desc').value.trim();
                const a = parseFloat(r.querySelector('.item-amt').value) || 0;
                const l = r.querySelector('.item-labor').checked;
                if (l) labor += a; else parts += a;
                if (d || a) itemsArr.push({desc:d, amount:a, labor:l});
            });
        }

        partsInput.value = parts.toFixed(2);
        laborInput.value = labor.toFixed(2);
        itemsJson.value = JSON.stringify(itemsArr);
        updateDetails();
    }

    function updateDetails(){
        const lines = Array.from(container.querySelectorAll('.items-row')).map(r=>{
            const d = r.querySelector('.item-desc').value.trim();
            const a = parseFloat(r.querySelector('.item-amt').value) || 0;
            const l = r.querySelector('.item-labor').checked;
            if (!d && !a) return null;
            return (l? '[Manoperă] ' : '[Piese] ') + d + ' - ' + a.toFixed(2) + ' RON';
        }).filter(Boolean);
        if (lines.length) detailsInput.value = lines.join('\n');
    }

    addBtn.addEventListener('click', ()=> createRow());

    function initFromServer(){
        try{
            const init = window.__INVOICE_INIT || {};
            const existingParts = parseFloat(init.existingParts || 0);
            const existingLabor = parseFloat(init.existingLabor || 0);
            const existingDetails = init.existingDetails || '';
            if (existingParts>0) createRow('Piese', existingParts, false);
            if (existingLabor>0) createRow('Manoperă', existingLabor, true);
            if (!existingParts && !existingLabor && existingDetails) createRow(existingDetails, '', false);
            computeSums();
        } catch(e){/* ignore */}
    }

    // compute before submit
    const form = document.querySelector('form');
    if (form) form.addEventListener('submit', function(){ computeSums(); });

    initFromServer();
})();
