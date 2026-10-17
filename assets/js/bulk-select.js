// Checkboxes belong to a separate form, so existing row forms stay valid HTML.
document.querySelectorAll('[data-select-all]').forEach(toggle => {
    const boxes = document.querySelectorAll('input[name="ids[]"][form="' + toggle.dataset.selectAll + '"]');
    toggle.addEventListener('change', () => boxes.forEach(box => { box.checked = toggle.checked; }));
    boxes.forEach(box => box.addEventListener('change', () => {
        const selected = Array.from(boxes).filter(item => item.checked).length;
        toggle.checked = boxes.length > 0 && selected === boxes.length;
        toggle.indeterminate = selected > 0 && selected < boxes.length;
    }));
});
