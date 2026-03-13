document.addEventListener('DOMContentLoaded', () => {

    // Request form: toggle fields based on selected type
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const typeFields = {
        topic: document.querySelector('.field-topic'),
        links: document.querySelector('.field-links'),
        files: document.querySelector('.field-files'),
    };

    function showTypeField(type) {
        Object.entries(typeFields).forEach(([key, el]) => {
            if (!el) return;
            el.style.display = key === type ? '' : 'none';
        });
    }

    if (typeRadios.length) {
        // Set initial state
        const checked = document.querySelector('input[name="type"]:checked');
        if (checked) showTypeField(checked.value);

        typeRadios.forEach(r => r.addEventListener('change', () => showTypeField(r.value)));
    }

});
