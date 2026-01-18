// Attach confirmation dialogs to forms/buttons using data-confirm attribute
(function(){
    document.addEventListener('submit', function(e){
        const form = e.target;
        if (form && form.dataset && form.dataset.confirm) {
            const msg = form.dataset.confirm;
            if (!confirm(msg)) {
                e.preventDefault();
            }
        }
    }, true);

    // For buttons with data-confirm, intercept click and confirm before submitting closest form
    document.addEventListener('click', function(e){
        const btn = e.target.closest('[data-confirm]');
        if (!btn) return;
        const msg = btn.dataset.confirm;
        if (!confirm(msg)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // If the element is a button inside a form, allow normal submit
        return true;
    });
})();
