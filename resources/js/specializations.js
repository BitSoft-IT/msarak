const STORAGE_KEY = 'specializations-compare';

function getSelection() {
    try {
        return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) ?? [];
    } catch {
        return [];
    }
}

function setSelection(selection) {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(selection));
    renderBar();
}

function renderBar() {
    const selection = getSelection();
    const bar = document.getElementById('compare-bar');
    if (!bar) return;

    if (selection.length === 0) {
        bar.classList.add('hidden');
        return;
    }

    bar.classList.remove('hidden');
    document.getElementById('compare-bar-names').textContent =
        selection.map((item) => item.name).join(' مقابل ');

    const link = document.getElementById('compare-bar-link');
    if (selection.length === 2) {
        link.href = `/specializations/compare?first=${selection[0].id}&second=${selection[1].id}`;
        link.classList.remove('pointer-events-none', 'opacity-50');
    } else {
        link.classList.add('pointer-events-none', 'opacity-50');
    }
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('.compare-toggle');
    if (button) {
        let selection = getSelection();
        const { id, name, redirect } = button.dataset;
        const exists = selection.some((item) => item.id === id);

        if (exists) {
            selection = selection.filter((item) => item.id !== id);
        } else if (selection.length < 2) {
            selection.push({ id, name });
        } else {
            const replace = window.confirm(
                'لديك بالفعل تخصصان مختاران. هل تريد استبدال أولهما بهذا التخصص؟'
            );
            if (!replace) return;
            selection = [selection[1], { id, name }];
        }

        setSelection(selection);

        if (redirect && !exists) {
            window.location.href = redirect;
        }
        return;
    }

    if (event.target.id === 'compare-bar-clear') {
        setSelection([]);
    }
});

renderBar();