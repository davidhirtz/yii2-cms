document.addEventListener('htmx:load', (event) => {
    const $container = (event as CustomEvent).detail.elt as HTMLElement;
    const targetSelector = '[data-id="parent"]';

    $container.querySelectorAll<HTMLSelectElement>('[data-id="tenant"]').forEach($dropdown => {
        const $target = $dropdown.closest('form')!.querySelector<HTMLSelectElement>(targetSelector);

        if ($target) {
            $dropdown.addEventListener('change', () => {
                const url = new URL(location.href);
                url.searchParams.set('tenant', $dropdown.value);

                $target.disabled = true;

                fetch(url)
                    .then(response => response.text())
                    .then(text => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(text, 'text/html');
                        const $newDropdown = doc.querySelector(targetSelector);

                        $target.innerHTML = $newDropdown?.innerHTML || '';
                        $target.disabled = false;

                        $target.closest<HTMLDivElement>('.form-row')!.hidden = $target.childElementCount <= 1;

                        $target.dispatchEvent(new Event('change'));
                    });
            });
        }
    });
});
//
// export default (
//     tenantDropdownSelector: string,
//     parentDropdownSelector: string,
// ) => {
//     const $tenantIdDropdown: HTMLSelectElement = document.querySelector(tenantDropdownSelector);
//     const $target: HTMLSelectElement = document.querySelector(parentDropdownSelector);
//
//     const updateDefaultParentDropdownValue = () => {
//         $target.options[0].dataset.value = $tenantIdDropdown.options[$tenantIdDropdown.selectedIndex].dataset.value;
//         $target.dispatchEvent(new Event('change'));
//     }
//
//     $tenantIdDropdown.addEventListener('change', function () {
//         const url = new URL(window.location.href);
//         url.searchParams.set('tenant', $tenantIdDropdown.value);
//
//         $target.disabled = true;
//
//         fetch(url)
//             .then(response => response.text())
//             .then(text => {
//                 const parser = new DOMParser();
//                 const doc = parser.parseFromString(text, 'text/html');
//                 const $newDropdown = doc.querySelector(parentDropdownSelector);
//
//                 $target.innerHTML = $newDropdown.innerHTML || '';
//                 $target.disabled = false;
//
//                 updateDefaultParentDropdownValue();
//             });
//     });
//
//     updateDefaultParentDropdownValue();
// }